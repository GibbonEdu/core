<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Filesystem;

use GuzzleHttp\Client;
use Gibbon\Contracts\Services\Session;

/**
 * Fetches the remote version list and picks the next version zip to install.
 *
 * @version v30
 * @since   v30
 */
class VersionCatalog
{
    public const CATALOG_URL = 'https://gibbonedu.org/services/version/versionCheck.php';

    // Only these hosts may appear in package URLs, each with the path it must start with.
    // GitHub hosts everyone's code, so they are limited to the GibbonEdu organisation.
    protected $allowedHosts = [
        'gibbonedu.org'       => '/',
        'www.gibbonedu.org'   => '/',
        'github.com'          => '/GibbonEdu/',
        'codeload.github.com' => '/GibbonEdu/',
    ];

    // GitHub redirects release downloads here. Anyone can publish files on this host,
    // so it is only accepted as a redirect from an allowed host, never as the first URL.
    protected $redirectHosts = [
        'release-assets.githubusercontent.com' => '/',
    ];

    protected $session;

    protected $catalogVersion = '';
    protected $catalog = [];

    /**
     * Create a catalog reader for the current install.
     *
     * @param Session $session
     * @return void
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Create the HTTP client used to fetch the catalog.
     *
     * @param array $options
     * @return Client
     */
    protected function createClient(array $options) : Client
    {
        return new Client($options);
    }

    /**
     * Get all versions stored in the catalog.
     *
     * @return array
     */
    public function getCatalog() : array
    {
        return $this->catalog;
    }

    /**
     * Get the version string of the currently loaded catalog.
     *
     * @return string
     */
    public function getCatalogVersion() : string
    {
        return $this->catalogVersion;
    }

    /**
     * Load the catalog JSON for the given version. Returns [] on failure. 
     * Caches the result so multiple calls for the same version do not 
     * go to the remote server.
     *
     * @param string $currentVersion
     * @return array
     */
    public function fetch(string $currentVersion) : array
    {
        $currentVersion = trim($currentVersion);
        if ($currentVersion == '') {
            return [];
        }

        if ($currentVersion == $this->catalogVersion && !empty($this->catalog)) {
            return $this->catalog;
        }

        try {
            $client = $this->createClient([
                'connect_timeout' => 10,
                'timeout'         => 30,
            ]);

            $response = $client->request('GET', self::CATALOG_URL, [
                'headers' => [
                    'Referer' => $this->session->get('absoluteURL').'/index.php',
                ],
                'query' => [
                    'version' => $currentVersion,
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() != 200) {
                error_log('VersionCatalog: catalog HTTP '.$response->getStatusCode());
                return [];
            }

            $decoded = json_decode((string) $response->getBody(), true);
            if (!is_array($decoded) || empty($decoded)) {
                return [];
            }

            // Expect a version map, not a plain list
            $firstKey = array_key_first($decoded);
            if (is_null($firstKey) || is_int($firstKey)) {
                return [];
            }

            foreach ($decoded as $version => $entry) {
                $parsedChangelog = $this->getChangeLogFromString((string) ($entry['changelog'] ?? ''));
                $decoded[$version]['changelog'] = $parsedChangelog[$version]['changelog'] ?? [];
            }

            $this->catalogVersion = $currentVersion;
            $this->catalog = $decoded;

            return $decoded;
        } catch (\Throwable $e) {
            error_log('VersionCatalog: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Get the release entry for a specific version, or null if not found.
     *
     * @param string $version
     * @return array|null
     */
    public function getRelease(string $version) : ?array
    {
        $version = trim($version);
        if ($version == '') {
            return null;
        }

        $catalog = $this->fetch($version);
        if (empty($catalog)) {
            return null;
        }

        $entry = $catalog[$version] ?? null;
        if (!is_array($entry)) {
            return null;
        }

        $entryVersion = trim((string) ($entry['version'] ?? $version));
        if ($entryVersion == '') {
            return null;
        }

        $status = strtolower(trim($entry['status'] ?? ''));
        if (!in_array($status, ['legacy', 'current', 'development'])) {
            return null;
        }

        $checksum = trim($entry['checksum'] ?? '');
        $filePath = trim($entry['filePath'] ?? '');
        if ($checksum == '' || $filePath == '' || !$this->isHostAllowed($filePath)) {
            return null;
        }

        $entry['version'] = $entryVersion;
        $entry['checksum'] = $checksum;
        $entry['filePath'] = $filePath;
        $entry['status'] = $status;

        return $entry;
    }

    /**
     * Next downloadable release after $currentVersion, or null if none.
     *
     * @param string $currentVersion
     * @param bool   $cuttingEdge
     * @return array|null
     */
    public function getNextRelease(string $currentVersion, bool $cuttingEdge = false)
    {
        $currentVersion = trim($currentVersion);
        $catalog = $this->fetch($currentVersion);
        if (empty($catalog)) {
            return null;
        }

        // Cutting edge can take Development; stable cannot
        $allowedStatuses = $cuttingEdge
            ? ['legacy', 'current', 'development']
            : ['legacy', 'current'];

        // Check versions oldest first, so the next release after the current one is found
        $versions = array_keys($catalog);
        usort($versions, 'version_compare');

        foreach ($versions as $version) {
            $entry = $catalog[$version] ?? null;
            if (!is_array($entry)) {
                continue;
            }

            $entryVersion = trim((string) ($entry['version'] ?? $version));
            if ($entryVersion == '' || version_compare($entryVersion, $currentVersion, '<=')) {
                continue;
            }

            $status = strtolower(trim($entry['status'] ?? ''));
            if (!in_array($status, $allowedStatuses)) {
                continue;
            }

            $checksum = trim($entry['checksum'] ?? '');
            $filePath = trim($entry['filePath'] ?? '');
            if ((!$cuttingEdge && $checksum == '') || $filePath == '' || !$this->isHostAllowed($filePath)) {
                // Next version has no package yet — stop here
                return null;
            }

            $entry['version'] = $entryVersion;
            $entry['checksum'] = $checksum;
            $entry['filePath'] = $filePath;
            $entry['status'] = $status;

            return $entry;
        }

        return null;
    }

    /**
     * The Development release for the installed version, used to check for newer commits on the same
     * version. Returns null unless the catalog lists the version as Development with an allowed package URL.
     *
     * @param string $currentVersion
     * @return array|null
     */
    public function getCuttingEdgeRelease(string $currentVersion): ?array
    {
        $currentVersion = trim($currentVersion);
        if ($currentVersion === '') {
            return null;
        }

        $entry = $this->fetch($currentVersion)[$currentVersion] ?? null;
        if (!is_array($entry) || strtolower(trim($entry['status'] ?? '')) !== 'development') {
            return null;
        }

        $filePath = trim($entry['filePath'] ?? '');
        if ($filePath === '' || !$this->isHostAllowed($filePath)) {
            return null;
        }

        $entry['version'] = $currentVersion;
        $entry['checksum'] = '';
        $entry['filePath'] = $filePath;
        $entry['status'] = 'development';

        return $entry;
    }

    /**
     * Ask GitHub for the newest commit on the branch that a Development package is archived from.
     *
     * @param array $release A Development release, from getCuttingEdgeRelease() or getNextRelease().
     * @return string The full commit SHA, in lower case.
     * @throws \RuntimeException When the branch or its commit cannot be read.
     */
    public function getCuttingEdgeCommit(array $release): string
    {
        $url = parse_url((string) ($release['filePath'] ?? ''));
        if (!is_array($url)
            || strtolower($url['host'] ?? '') !== 'github.com'
            || preg_match('#^/(GibbonEdu/[^/]+)/archive/refs/heads/([^/]+)\.zip$#i', $url['path'] ?? '', $match) !== 1) {
            throw new \RuntimeException('The Development package is not a GibbonEdu branch archive.');
        }

        try {
            $response = $this->createClient(['connect_timeout' => 10, 'timeout' => 30])->request(
                'GET',
                'https://api.github.com/repos/'.$match[1].'/commits/'.rawurlencode(rawurldecode($match[2])),
                ['headers' => ['Accept' => 'application/vnd.github+json'], 'http_errors' => false]
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not ask GitHub for the latest commit: '.$e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('GitHub returned HTTP '.$response->getStatusCode().' for the branch commit.');
        }

        $body = json_decode((string) $response->getBody(), true);
        $sha = strtolower((string) (is_array($body) ? ($body['sha'] ?? '') : ''));
        if (preg_match('/^[0-9a-f]{40}$/', $sha) !== 1) {
            throw new \RuntimeException('GitHub did not return a commit SHA for the branch.');
        }

        return $sha;
    }

    /**
     * Check the package URL is https, on an allowed host, and under the path allowed for that host.
     *
     * @param string $url
     * @param bool $isRedirect Whether the URL is a redirect from an allowed URL, not the first one.
     * @return bool
     */
    public function isHostAllowed(string $url, bool $isRedirect = false) : bool
    {
        $parts = parse_url($url);
        if ($parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return false;
        }

        $hosts = $isRedirect ? $this->allowedHosts + $this->redirectHosts : $this->allowedHosts;
        $requiredPath = $hosts[strtolower($parts['host'])] ?? null;
        if ($requiredPath === null) {
            return false;
        }

        // Dot segments, encoded dots and slashes, and backslashes could step out of the allowed path
        $path = $parts['path'] ?? '/';
        if (preg_match('#(^|/)\.\.?(/|$)|%2e|%2f|%5c|\\\\#i', $path) === 1) {
            return false;
        }

        return stripos($path, $requiredPath) === 0;
    }

    /**
     * Merge changelog entries into the catalog, newest version first.
     *
     * @param array $catalog
     * @return void
     */
    public function mergeCatalog(array $catalog) : void
    {
        foreach ($catalog as $version => $entry) {
            if (!is_string($version) || !is_array($entry)) {
                continue;
            }

            $this->catalog[$version] = !empty($this->catalog[$version])
                ? array_merge($this->catalog[$version], $entry)
                : $entry;
        }

        uksort($this->catalog, 'version_compare');
        $this->catalog = array_reverse($this->catalog, true);
    }

    /**
     * Parse the root CHANGELOG.txt file into version and category entries.
     *
     * @param string|null $filePath
     * @param int $limit The maximum number of versions to return.
     * @return array<string, array<string, string[]>>
     */
    public function getChangeLogFromFile($filePath = null, int $limit = 10) : array
    {
        if ($filePath === null) {
            $filePath = $this->session->get('absolutePath').'/CHANGELOG.txt';
        }

        if (!is_string($filePath) || !is_readable($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        return array_slice($this->parseChangeLog($lines), 0, $limit, true);
    }

    /**
     * Parse changelog text in the same format as CHANGELOG.txt.
     *
     * @param string $content
     * @return array
     */
    public function getChangeLogFromString(string $content) : array
    {
        if (empty($content)) {
            return [];
        }

        $lines = preg_split('/\R/', $content);

        return $this->parseChangeLog($lines);
    }

    /**
     * Parse changelog lines into versions, categories and entries.
     *
     * @param array $lines
     * @return array
     */
    protected function parseChangeLog(array $lines): array
    {
        $changelog = [];
        $version = null;
        $category = null;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (preg_match('/^(v\d+\.\d+\.\d+(?: and earlier)?)$/', $trimmedLine, $matches)) {
                $version = trim($matches[1], 'v');
                $category = null;
                $changelog[$version] = ['version' => $version, 'changelog' => [], 'status' => 'Legacy'];
                continue;
            }

            if ($version === null || $trimmedLine === '' || preg_match('/^-+$/', $trimmedLine)) {
                continue;
            }

            if (preg_match('/^ {4}(\S.*)$/', $line, $matches)) {
                $category = $matches[1];
                $changelog[$version]['changelog'][$category] = [];
                continue;
            }

            if ($category === null || !preg_match('/^ {8,}(\S.*)$/', $line, $matches)) {
                continue;
            }

            $entries = &$changelog[$version]['changelog'][$category];
            $entry = $matches[1];
            $lastEntry = array_key_last($entries);

            if ($lastEntry !== null && preg_match('/\s$/', $entries[$lastEntry])) {
                $entries[$lastEntry] = rtrim($entries[$lastEntry]).' '.$entry;
            } else {
                $entries[] = $entry;
            }

            unset($entries);
        }

        return $changelog;
    }
}
