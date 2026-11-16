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
use GuzzleHttp\Exception\GuzzleException;
use Gibbon\Contracts\Services\Session;

/**
 * Fetches the remote Gibbon version catalog and selects the next sequential release.
 *
 * @version v30
 * @since   v30
 */
class VersionCatalog
{
    public const CATALOG_URL = 'https://gibbonedu.org/services/version/versionCheck.php';

    /**
     * @var string[]
     */
    protected $allowedHosts = [
        'gibbonedu.org',
        'www.gibbonedu.org',
    ];

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Fetch the version catalog for the given installed version.
     *
     * @param string $currentVersion
     * @return array Empty array on failure or when the server returns no versions.
     */
    public function fetch(string $currentVersion) : array
    {
        $currentVersion = trim($currentVersion);
        if ($currentVersion == '') {
            return [];
        }

        try {
            $client = new Client([
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
            if (!is_array($decoded) || $decoded === []) {
                return [];
            }

            // Server returns [] when version is missing; a usable catalog is an object map.
            $firstKey = array_key_first($decoded);
            if (is_null($firstKey) || is_int($firstKey)) {
                return [];
            }

            return $decoded;
        } catch (GuzzleException $e) {
            error_log('VersionCatalog: '.$e->getMessage());
            return [];
        } catch (\Throwable $e) {
            error_log('VersionCatalog: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Return the next sequential downloadable release after $currentVersion.
     *
     * Stable installs: Legacy/Current only (up to Current).
     * Cutting Edge: Legacy/Current/Development (Development hops allowed).
     *
     * @param string $currentVersion
     * @param bool $cuttingEdge
     * @return array|null Release entry, or null when up to date / unavailable.
     */
    public function getNextRelease(string $currentVersion, bool $cuttingEdge = false) : ?array
    {
        $currentVersion = trim($currentVersion);
        $catalog = $this->fetch($currentVersion);
        if (empty($catalog)) {
            return null;
        }

        $allowedStatuses = $cuttingEdge
            ? ['legacy', 'current', 'development']
            : ['legacy', 'current'];

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

            $status = strtolower(trim((string) ($entry['status'] ?? '')));
            if (!in_array($status, $allowedStatuses, true)) {
                continue;
            }

            $checksum = trim((string) ($entry['checksum'] ?? ''));
            $filePath = trim((string) ($entry['filePath'] ?? ''));
            if ($checksum == '' || $filePath == '' || !$this->isHostAllowed($filePath)) {
                // Unpackaged or unsafe URL — do not skip to a later version.
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
     * Whether a package URL points at an allowlisted gibbonedu.org host.
     *
     * @param string $url
     * @return bool
     */
    public function isHostAllowed(string $url) : bool
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme != 'http' && $scheme != 'https') {
            return false;
        }

        return in_array(strtolower($parts['host']), $this->allowedHosts, true);
    }
}
