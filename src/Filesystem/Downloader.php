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

use ZipArchive;
use GuzzleHttp\Client;
use Gibbon\Contracts\Services\Session;

/**
 * Downloads, verifies, and extracts an update zip into staging.
 *
 * @version v30
 * @since   v30
 */
class Downloader
{
    public const BASE_DIR = 'backups/fileUpdate';
    public const MAX_PACKAGE_BYTES = 209715200; // 200 MB
    public const MAX_EXTRACTED_BYTES = 1073741824; // 1 GiB, total size of all files in the package

    // Short stage codes shown on the update page when something fails
    public const ERROR_INVALID = 'invalid';
    public const ERROR_DOWNLOAD = 'download';
    public const ERROR_VERIFY = 'verify';
    public const ERROR_EXTRACT = 'extract';
    public const ERROR_WRITE = 'write';

    protected $session;
    protected $versionCatalog;
    protected $absolutePath;
    protected $progressCallback;
    protected $lastProgress;
    protected int $maxExtractedBytes = self::MAX_EXTRACTED_BYTES;
    private Filesystem $filesystem;
    private ?string $baseDirectory = null;

    /**
     * @param Session $session
     * @param VersionCatalog $versionCatalog
     * @param Filesystem $filesystem
     * @return void
     */
    public function __construct(Session $session, VersionCatalog $versionCatalog, Filesystem $filesystem)
    {
        $this->session = $session;
        $this->versionCatalog = $versionCatalog;
        $this->absolutePath = rtrim(str_replace('\\', '/', $session->get('absolutePath')), '/');
        $this->filesystem = $filesystem;
    }

    /**
     * Set an explicit backup base directory.
     *
     * Explicit paths are retained and are never replaced by the preferred
     * directory selected from the application root.
     *
     * @param string $baseDirectory
     * @return void
     */
    public function setBaseDirectory(string $baseDirectory): void
    {
        $this->baseDirectory = rtrim(str_replace('\\', '/', $baseDirectory), '/');
    }

    /**
     * Return the backup base directory selected for this downloader.
     *
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDirectory ?? $this->filesystem->getSecureDirectory($this->absolutePath, self::BASE_DIR);
    }

    /**
     * Set a callback to receive progress notifications.
     *
     * @param callable|null $progressCallback
     * @return void
     */
    public function setProgressCallback(?callable $progressCallback): void
    {
        $this->progressCallback = $progressCallback;
        $this->lastProgress = null;
    }

    /**
     * Get the filesystem paths that require write permissions.
     *
     * @return string[]
     */
    public function getPermissionPaths()
    {
        $baseDir = $this->getBaseDirectory();

        return [
            $baseDir,
            $baseDir.'/staging',
            $baseDir.'/previousVersionBackup',
        ];
    }

    /**
     * Verify, download, check and extract a release. Returns the relative paths of the files to install.
     *
     * @param array $release
     * @return array
     */
    public function run(array $release) : array
    {
        $this->lastProgress = null;
        $this->reportProgress(0);
        $this->verifyGithubDigest($release);
        $zipPath = $this->download($release);
        $this->verify($zipPath, $release['checksum'] ?? '');
        $files = $this->extract($zipPath);
        $this->checkVersion((string) ($release['version'] ?? ''), $zipPath);
        $this->filesystem->deleteIfExists($zipPath);
        $this->reportProgress(100);

        return $files;
    }

    /**
     * Check the package is the version the catalog promised, so a wrong or stale
     * package is not installed. Clears staging and the zip when it is not.
     *
     * @param string $expected The version from the catalog.
     * @param string $zipPath
     * @return void
     */
    protected function checkVersion(string $expected, string $zipPath) : void
    {
        $staging = $this->getBaseDirectory().'/staging';
        $found = $this->filesystem->readVersion($staging.'/version.php');

        if ($expected === '' || $found !== $expected) {
            $this->filesystem->wipeDirectory($staging);
            $this->fail(self::ERROR_INVALID, 'Package version '.($found ?? 'unknown').' does not match the catalog version '.($expected ?: 'unknown'), $zipPath);
        }
    }

    /**
     * A released version hosted as a GitHub release file must have the checksum GitHub
     * reports for that file, so the catalog on gibbonedu.org cannot be the only source for it.
     * Runs before the download, so a package that does not verify is never fetched. Other
     * downloads, and Development versions, are left to the catalog checksum alone.
     *
     * @param array $release
     * @return void
     */
    protected function verifyGithubDigest(array $release) : void
    {
        $url = parse_url((string) ($release['filePath'] ?? ''));
        if (strtolower($release['status'] ?? '') === 'development'
            || strtolower($url['host'] ?? '') !== 'github.com'
            || preg_match('#^/(GibbonEdu/[^/]+)/releases/download/([^/]+)/([^/]+)$#i', $url['path'] ?? '', $match) !== 1) {
            return;
        }

        $releaseInfo = null;
        try {
            $response = $this->createClient(['connect_timeout' => 10, 'timeout' => 30])->request(
                'GET',
                'https://api.github.com/repos/'.$match[1].'/releases/tags/'.rawurlencode(rawurldecode($match[2])),
                ['headers' => ['Accept' => 'application/vnd.github+json'], 'http_errors' => false]
            );
            $releaseInfo = $response->getStatusCode() === 200 ? json_decode((string) $response->getBody(), true) : null;
        } catch (\Throwable $e) {
            $this->fail(self::ERROR_VERIFY, 'Could not ask GitHub for the release checksum: '.$e->getMessage());
        }

        $digest = '';
        foreach ((is_array($releaseInfo) ? $releaseInfo['assets'] ?? [] : []) as $asset) {
            if (($asset['name'] ?? '') === rawurldecode($match[3])) {
                $digest = strtolower((string) ($asset['digest'] ?? ''));
            }
        }

        $checksum = strtolower(trim((string) ($release['checksum'] ?? '')));
        if ($checksum === '' || !hash_equals('sha256:'.$checksum, $digest)) {
            $this->fail(self::ERROR_VERIFY, 'GitHub checksum "'.$digest.'" does not match the catalog checksum "'.$checksum.'" for '.($release['filePath'] ?? ''));
        }
    }

    /**
     * Create the HTTP client used to ask GitHub for a release checksum.
     *
     * @param array $options
     * @return Client
     */
    protected function createClient(array $options) : Client
    {
        return new Client($options);
    }

    /**
     * Download the zip into the backup directory as package.zip.
     *
     * @param array $release
     * @return string
     */
    public function download(array $release) : string
    {
        $filePath = trim($release['filePath'] ?? '');
        if ($filePath == '' || !$this->versionCatalog->isHostAllowed($filePath)) {
            $this->fail(self::ERROR_INVALID, 'Invalid filePath in version catalog: '.$filePath);
        }
        if (!class_exists(ZipArchive::class)) {
            $this->fail(self::ERROR_WRITE, 'ZipArchive class not available');
        }

        $zipPath = $this->ensureBaseDirectory().'/package.zip';
        $this->filesystem->deleteIfExists($zipPath);

        $tooLarge = false;
        $refusedRedirect = null;

        try {
            $response = $this->createClient([
                'connect_timeout' => 10,
                'timeout'         => 300,
            ])->request('GET', $filePath, [
                'headers'     => ['Referer' => $this->session->get('absoluteURL').'/index.php'],
                'sink'        => $zipPath, // write straight to disk
                // Refuse a package that declares itself too large before any of it is written
                'on_headers'  => function ($response) use (&$tooLarge) {
                    if ((int) $response->getHeaderLine('Content-Length') > self::MAX_PACKAGE_BYTES) {
                        $tooLarge = true;
                        throw new \RuntimeException('Package is too large');
                    }
                },
                'progress'    => function ($downloadTotal, $downloadedBytes) {
                    if ($downloadTotal > 0) {
                        $this->reportProgressRange((int) $downloadedBytes, (int) $downloadTotal, 0, 55);
                    }
                },
                'http_errors' => false,
                'allow_redirects' => [
                    'max'       => 5,
                    'protocols' => ['https'],
                    // Redirects must stay on the hosts the version catalog allows
                    'on_redirect' => function ($request, $response, $uri) use (&$refusedRedirect) {
                        if (!$this->versionCatalog->isHostAllowed((string) $uri, true)) {
                            $refusedRedirect = (string) $uri;
                            throw new \RuntimeException('Redirect to disallowed host.');
                        }
                    },
                ],
            ]);

        } catch (\Throwable $e) {
            // The callbacks above record why they refused a response, so the reason is known here
            if ($tooLarge) {
                $this->fail(self::ERROR_INVALID, 'Content-Length exceeds max size of '.self::MAX_PACKAGE_BYTES.' bytes', $zipPath);
            }
            if ($refusedRedirect !== null) {
                $this->fail(self::ERROR_INVALID, 'Redirect to disallowed host: '.$refusedRedirect, $zipPath);
            }
            $this->fail(self::ERROR_DOWNLOAD, 'Download failed: '.$e->getMessage(), $zipPath);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->fail(self::ERROR_DOWNLOAD, 'HTTP status '.$response->getStatusCode().' from '.$filePath, $zipPath);
        }

        $size = is_file($zipPath) ? filesize($zipPath) : false;
        if ($size === false || $size < 1) {
            $this->fail(self::ERROR_DOWNLOAD, 'Downloaded file is empty or missing', $zipPath);
        }
        if ($size > self::MAX_PACKAGE_BYTES) {
            $this->fail(self::ERROR_INVALID, 'Downloaded file exceeds max size of '.self::MAX_PACKAGE_BYTES.' bytes', $zipPath);
        }
        // Reject non-zip payloads
        if (@file_get_contents($zipPath, false, null, 0, 2) !== 'PK') {
            $this->fail(self::ERROR_DOWNLOAD, 'Downloaded file is not a zip archive', $zipPath);
        }

        $this->reportProgress(55);

        return $zipPath;
    }

    /**
     * Check the zip SHA-256 against the catalog checksum.
     *
     * @param string $zipPath
     * @param string $checksum
     * @return void
     */
    public function verify(string $zipPath, string $checksum)
    {
        $checksum = strtolower(trim($checksum));
        if (!is_file($zipPath) || $checksum == '' || !hash_equals($checksum, hash_file('sha256', $zipPath))) {
            $this->fail(self::ERROR_VERIFY, 'Checksum mismatch: expected '.$checksum.', got '.(is_file($zipPath) ? hash_file('sha256', $zipPath) : 'missing file'), $zipPath);
        }

        $this->reportProgress(60);
    }

    /**
     * Unzip into staging and return the relative file list.
     *
     * @param string $zipPath
     * @return array
     */
    public function extract(string $zipPath) : array
    {
        $staging = $this->getBaseDirectory().'/staging';

        // Anything left in staging by an earlier run would be installed along with this package
        if (!$this->filesystem->wipeDirectory($staging)) {
            $this->fail(self::ERROR_WRITE, 'Failed to clear the staging directory: '.$staging, $zipPath);
        }

        if (!$this->filesystem->ensureDirectory($staging) || ($stagingReal = realpath($staging)) === false) {
            $this->fail(self::ERROR_WRITE, 'Failed to create staging directory: '.$staging, $zipPath);
        }

        $zip = new ZipArchive();
        if (!is_file($zipPath) || $zip->open($zipPath) !== true) {
            $this->fail(self::ERROR_EXTRACT, 'Failed to open zip archive: '.$zipPath, $zipPath);
        }

        $entryWork = [];
        $totalWork = 0;
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $entryName = (string) $zip->getNameIndex($i);
            if ($this->shouldSkipEntry($entryName)) {
                continue;
            }

            $stat = $zip->statIndex($i);
            $entryWork[$i] = max(1, (int) ($stat === false ? 0 : ($stat['size'] ?? 0)));
            $totalWork += $entryWork[$i];
        }

        // The update needs room for the staged files plus a backup of the files they replace
        $freeSpace = $this->filesystem->getFreeSpace($stagingReal);
        if ($totalWork > $this->maxExtractedBytes || ($freeSpace !== null && $freeSpace < $totalWork * 2)) {
            $zip->close();
            $this->fail(
                $totalWork > $this->maxExtractedBytes ? self::ERROR_INVALID : self::ERROR_WRITE,
                'Package expands to '.$totalWork.' bytes, limit '.$this->maxExtractedBytes.', free space '.($freeSpace ?? 'unknown'),
                $zipPath
            );
        }

        $completedWork = 0;

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $entryName = $zip->getNameIndex($i);
                $name = str_replace('\\', '/', $entryName);

                // Skip folders and macOS junk
                if ($this->shouldSkipEntry($name)) {
                    continue;
                }

                if (!$this->filesystem->isSafeRelativePath($name)) {
                    $this->fail(self::ERROR_INVALID, 'Unsafe relative path: '.$name, $zipPath);
                }
                $relative = $name;
                if ($zip->extractTo($stagingReal, $entryName) !== true) {
                    $this->fail(self::ERROR_EXTRACT, 'Failed to extract entry: '.$entryName, $zipPath);
                }

                // Make sure the file stayed inside staging
                $extracted = realpath($stagingReal.'/'.$relative);
                if ($extracted === false || !$this->filesystem->isInside($extracted, $stagingReal)) {
                    $this->fail(self::ERROR_INVALID, 'Extracted file is outside staging: '.$entryName, $zipPath);
                }

                $completedWork += $entryWork[$i] ?? 1;
                $this->reportProgressRange($completedWork, $totalWork, 60, 95);
            }

            $this->reportProgress(95);
        } catch (\Throwable $e) {
            $this->filesystem->wipeDirectory($staging);
            $this->filesystem->deleteIfExists($zipPath);
            throw $e;
        } finally {
            $zip->close();
        }

        try {
            // Flat zip, or one wrapper folder (e.g. gibbon/)
            $entries = array_values(array_filter(scandir($stagingReal) ?: [], function ($name) {
                return $name != '.' && $name != '..' && $name != '__MACOSX';
            }));
            if (count($entries) == 1 && is_dir($stagingReal.'/'.$entries[0])) {
                $wrap = $stagingReal.'/'.$entries[0];
                foreach (scandir($wrap) ?: [] as $item) {
                    if ($item == '.' || $item == '..') {
                        continue;
                    }
                    if (!@rename($wrap.'/'.$item, $stagingReal.'/'.$item)) {
                        $this->fail(self::ERROR_EXTRACT, 'Failed to move extracted file from wrapper: '.$item, $zipPath);
                    }
                }
                @rmdir($wrap);
            }

            // Build the relative path list for FileUpdater
            $files = [];
            $indexedFiles = 0;
            $totalFiles = count($entryWork);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($stagingReal, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }
                $full = str_replace('\\', '/', $fileInfo->getPathname());
                $root = rtrim(str_replace('\\', '/', $stagingReal), '/');
                $relative = ltrim(substr($full, strlen($root)), '/');
                if (!$this->filesystem->isSafeRelativePath($relative)) {
                    $this->fail(self::ERROR_INVALID, 'Unsafe relative path: '.$relative, $zipPath);
                }
                $files[] = $relative;
                ++$indexedFiles;
                $this->reportProgressRange($indexedFiles, $totalFiles, 95, 99);
            }
        } catch (\Throwable $e) {
            $this->filesystem->wipeDirectory($staging);
            $this->filesystem->deleteIfExists($zipPath);
            throw $e;
        }

        // Need version.php to count as a real package
        if (empty($files) || !in_array('version.php', $files)) {
            $this->filesystem->wipeDirectory($staging);
            $this->fail(self::ERROR_EXTRACT, 'Extracted package is missing version.php', $zipPath);
        }

        $this->reportProgress(100);

        return $files;
    }

    /**
     * Skip empty entries, folders, and macOS junk.
     *
     * @param string $name
     * @return bool
     */
    protected function shouldSkipEntry(string $name) : bool
    {
        return $name == ''
            || substr($name, -1) == '/'
            || strpos($name, '__MACOSX/') === 0
            || substr($name, -9) == '.DS_Store';
    }

    /**
     * Report progress as a percentage within a range.
     *
     * @param int $completed
     * @param int $total
     * @param int $start
     * @param int $end
     * @return void
     */
    protected function reportProgressRange(int $completed, int $total, int $start, int $end) : void
    {
        if ($total <= 0) {
            $this->reportProgress($end);
            return;
        }

        $completed = min(max($completed, 0), $total);
        $progress = $start + (int) floor(($end - $start) * ($completed / $total));
        $this->reportProgress($progress);
    }

    /**
     * Report progress as a percentage.
     *
     * @param int $progress
     * @return void
     */
    protected function reportProgress(int $progress) : void
    {
        $progress = min(max($progress, 0), 100);
        if ($this->progressCallback === null || ($this->lastProgress !== null && $progress <= $this->lastProgress)) {
            return;
        }

        $this->lastProgress = $progress;
        ($this->progressCallback)($progress);
    }

    /**
     * Ensure the base directory exists and is not web-accessible.
     *
     * @return string
     */
    protected function ensureBaseDirectory() : string
    {
        $baseDir = $this->getBaseDirectory();

        if (empty($this->absolutePath)
            || !is_dir($this->absolutePath)
            || is_link($baseDir)
            || !$this->filesystem->ensureDirectory($baseDir)
            || !is_writable($baseDir)) {
            $this->fail(self::ERROR_WRITE, 'Failed to create or write to the backup directory.');
        }

        // Block web access: .htaccess for Apache, empty index.html if listing is enabled
        if (!is_file($baseDir.'/.htaccess') && @file_put_contents($baseDir.'/.htaccess', $this->getHtaccessDefaults()) === false) {
            $this->fail(self::ERROR_WRITE, 'Failed to protect the backup directory.');
        }
        if (!is_file($baseDir.'/index.html') && @file_put_contents($baseDir.'/index.html', '') === false) {
            $this->fail(self::ERROR_WRITE, 'Failed to protect the backup directory.');
        }

        return $baseDir;
    }

    /**
     * The .htaccess contents that keep the backup directory closed to the web: Require all
     * denied on Apache 2.4 (mod_authz_core), and Deny from all on Apache 2.2.
     *
     * @return string
     */
    private function getHtaccessDefaults() : string
    {
        return "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
    }

    /**
     * Remove a partial zip, log the error, and throw.
     *
     * @param string $code
     * @param string $message
     * @param string $zipPath
     * @return never
     * @throws \RuntimeException
     */
    protected function fail(string $code, string $message = '', string $zipPath = ''): never
    {
        $this->filesystem->deleteIfExists($zipPath);
        error_log('Downloader: '.$code.' '.$message);
        throw new \RuntimeException($code);
    }
}
