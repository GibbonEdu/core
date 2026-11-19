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
 * Fetches the remote version list and picks the next zip to install.
 *
 * @version v30
 * @since   v30
 */
class VersionCatalog
{
    public const CATALOG_URL = 'https://gibbonedu.org/services/version/versionCheck.php';

    // Only these hosts may appear in package URLs
    protected $allowedHosts = [
        'gibbonedu.org',
        'www.gibbonedu.org',
    ];

    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Load the catalog JSON for the given version. Returns [] on failure.
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
            if (!is_array($decoded) || empty($decoded)) {
                return [];
            }

            // Expect a version map, not a plain list
            $firstKey = array_key_first($decoded);
            if (is_null($firstKey) || is_int($firstKey)) {
                return [];
            }

            return $decoded;
        } catch (\Throwable $e) {
            error_log('VersionCatalog: '.$e->getMessage());
            return [];
        }
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

        // Walk versions in order so hops stay sequential
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
            if ($checksum == '' || $filePath == '' || !$this->isHostAllowed($filePath)) {
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
     * Check the package URL is on an allowed gibbonedu.org host.
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

        return in_array(strtolower($parts['host']), $this->allowedHosts);
    }
}
