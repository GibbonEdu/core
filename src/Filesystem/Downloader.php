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
 * Downloads, verifies, and extracts a sequential update zip.
 *
 * @version v30
 * @since   v30
 */
class Downloader
{
    public const BASE_DIR = 'backups/autoUpdate';
    public const MAX_PACKAGE_BYTES = 104857600; // 100 MB

    public const ERROR_INVALID = 'invalid';
    public const ERROR_DOWNLOAD = 'download';
    public const ERROR_VERIFY = 'verify';
    public const ERROR_EXTRACT = 'extract';
    public const ERROR_WRITE = 'write';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var VersionCatalog
     */
    protected $versionCatalog;

    /**
     * @var string
     */
    protected $absolutePath;

    public function __construct(Session $session, VersionCatalog $versionCatalog)
    {
        $this->session = $session;
        $this->versionCatalog = $versionCatalog;
        $this->absolutePath = rtrim(str_replace('\\', '/', (string) $session->get('absolutePath')), '/');
    }

    /**
     * Download, verify, and extract a release; return relative file paths.
     *
     * @param array $release
     * @return string[]
     */
    public function run(array $release) : array
    {
        $zipPath = $this->download($release);
        $this->verify($zipPath, (string) ($release['checksum'] ?? ''));
        $files = $this->extract($zipPath);
        $this->deleteIfExists($zipPath);
        
        return $files;
    }

    /**
     * Download the release zip to backups/autoUpdate/package.zip.
     *
     * @param array $release
     * @return string Absolute path to package.zip
     */
    public function download(array $release) : string
    {
        $filePath = trim((string) ($release['filePath'] ?? ''));
        if ($filePath == '' || !$this->versionCatalog->isHostAllowed($filePath)) {
            $this->fail(self::ERROR_INVALID);
        }
        if (!class_exists(ZipArchive::class)) {
            $this->fail(self::ERROR_WRITE);
        }

        $zipPath = $this->ensureBaseDirectory().'/package.zip';
        $this->deleteIfExists($zipPath);

        try {
            $response = (new Client([
                'connect_timeout' => 10,
                'timeout'         => 300,
            ]))->request('GET', $filePath, [
                'headers'     => ['Referer' => $this->session->get('absoluteURL').'/index.php'],
                'sink'        => $zipPath,
                'http_errors' => false,
                'allow_redirects' => [
                    'max'       => 5,
                    'protocols' => ['http', 'https'],
                    'on_redirect' => function ($request, $response, $uri) {
                        if (!$this->versionCatalog->isHostAllowed((string) $uri)) {
                            $this->fail(self::ERROR_INVALID);
                        }
                    },
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $this->fail(self::ERROR_DOWNLOAD, $zipPath);
            }
        } catch (\RuntimeException $e) {
            $this->deleteIfExists($zipPath);
            throw $e;
        } catch (\Throwable $e) {
            $this->fail(self::ERROR_DOWNLOAD, $zipPath, $e);
        }

        $size = is_file($zipPath) ? filesize($zipPath) : false;
        if ($size === false || $size < 1) {
            $this->fail(self::ERROR_DOWNLOAD, $zipPath);
        }
        if ($size > self::MAX_PACKAGE_BYTES) {
            $this->fail(self::ERROR_INVALID, $zipPath);
        }
        // Reject non-zip payloads
        if (@file_get_contents($zipPath, false, null, 0, 2) !== 'PK') {
            $this->fail(self::ERROR_DOWNLOAD, $zipPath);
        }

        return $zipPath;
    }

    /**
     * Check the zip SHA-256 against the catalog checksum.
     *
     * @param string $zipPath
     * @param string $checksum
     */
    public function verify(string $zipPath, string $checksum)
    {
        if (!is_file($zipPath) || trim($checksum) == '' || !hash_equals(strtolower(trim($checksum)), (string) hash_file('sha256', $zipPath))) {
            $this->fail(self::ERROR_VERIFY, $zipPath);
        }
    }

    /**
     * Extract safe zip entries into staging and return relative paths.
     *
     * @param string $zipPath
     * @return string[]
     */
    public function extract(string $zipPath) : array
    {
        $staging = $this->getStagingPath();
        $this->wipeDirectory($staging);

        if ((!is_dir($staging) && !mkdir($staging, 0755, true)) || ($stagingReal = realpath($staging)) === false) {
            $this->fail(self::ERROR_WRITE, $zipPath);
        }

        $zip = new ZipArchive();
        if (!is_file($zipPath) || $zip->open($zipPath) !== true) {
            $this->fail(self::ERROR_EXTRACT, $zipPath);
        }

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $entryName = (string) $zip->getNameIndex($i);
                $name = str_replace('\\', '/', $entryName);

                if ($name == '' || substr($name, -1) == '/' || strpos($name, '__MACOSX/') === 0 || substr($name, -9) == '.DS_Store') {
                    continue;
                }

                $relative = $this->assertSafeRelativePath($name);
                if ($zip->extractTo($stagingReal, $entryName) !== true) {
                    $this->fail(self::ERROR_EXTRACT, $zipPath);
                }

                $extracted = realpath($stagingReal.'/'.$relative);
                if ($extracted === false || !$this->isUnder($extracted, $stagingReal)) {
                    $this->fail(self::ERROR_INVALID, $zipPath);
                }
            }
        } catch (\Throwable $e) {
            $this->wipeDirectory($staging);
            $this->deleteIfExists($zipPath);
            throw $e;
        } finally {
            $zip->close();
        }

        try {
            // Flat zip, or one wrapper folder
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
                        $this->fail(self::ERROR_EXTRACT, $zipPath);
                    }
                }
                @rmdir($wrap);
            }

            $files = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($stagingReal, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }
                $full = str_replace('\\', '/', $fileInfo->getPathname());
                $root = rtrim(str_replace('\\', '/', $stagingReal), '/');
                $files[] = $this->assertSafeRelativePath(ltrim(substr($full, strlen($root)), '/'));
            }
        } catch (\Throwable $e) {
            $this->wipeDirectory($staging);
            $this->deleteIfExists($zipPath);
            throw $e;
        }

        if (empty($files) || !in_array('version.php', $files, true)) {
            $this->wipeDirectory($staging);
            $this->fail(self::ERROR_EXTRACT, $zipPath);
        }

        return $files;
    }

    /**
     * Whether $path is inside $root
     *
     * @param string $path
     * @param string $root
     * @return bool
     */
    protected function isUnder(string $path, string $root) : bool
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/');

        return strpos($path, $root.'/') === 0;
    }

    /**
     * Return the absolute staging path under backups/autoUpdate.
     *
     * @return string
     */
    public function getStagingPath() : string
    {
        return $this->absolutePath.'/'.self::BASE_DIR.'/staging';
    }

    /**
     * Normalize a zip path and reject unsafe or blocked targets.
     *
     * @param string $relative
     * @return string
     */
    protected function assertSafeRelativePath(string $relative) : string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $lower = strtolower($relative);

        // Same idea as Page::isAddressValid: block traversal and sensitive roots.
        if ($relative == ''
            || strpos($relative, "\0") !== false
            || strpos($relative, '..') !== false
            || (strlen($relative) > 1 && $relative[1] == ':' && ctype_alpha($relative[0]))
            || $lower == 'config.php'
            || strpos($lower, 'uploads/') === 0
            || strpos($lower, 'backups/') === 0
            || strpos($lower, '.git/') === 0
            || strpos($lower, 'vendor/') === 0
        ) {
            $this->fail(self::ERROR_INVALID);
        }

        return $relative;
    }

    /**
     * Ensure backups/autoUpdate exists, is writable, and is not web-accessible.
     *
     * @return string
     */
    protected function ensureBaseDirectory() : string
    {
        $baseDir = $this->absolutePath.'/'.self::BASE_DIR;

        if ($this->absolutePath == '' || !is_dir($this->absolutePath) || (!is_dir($baseDir) && !mkdir($baseDir, 0755, true)) || !is_writable($baseDir)) {
            $this->fail(self::ERROR_WRITE);
        }

        if (!is_file($baseDir.'/.htaccess')) {
            file_put_contents($baseDir.'/.htaccess', "Deny from all\n");
        }
        if (!is_file($baseDir.'/index.html')) {
            file_put_contents($baseDir.'/index.html', '');
        }

        return $baseDir;
    }

    /**
     * Clean up a partial zip, log the error code, and throw exception.
     *
     * @param string $code
     * @param string $zipPath
     * @param \Throwable|null $previous
     */
    protected function fail(string $code, string $zipPath = '', ?\Throwable $previous = null)
    {
        $this->deleteIfExists($zipPath);
        error_log('Downloader: '.$code.($previous ? ' '.$previous->getMessage() : ''));
        throw new \RuntimeException($code, 0, $previous);
    }

    /**
     * Delete a file if the path exists.
     *
     * @param string $path
     */
    protected function deleteIfExists(string $path)
    {
        if ($path != '' && is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Delete all contents under a directory; leave the directory itself.
     *
     * @param string $dir
     */
    protected function wipeDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $fileInfo->isDir() ? rmdir($fileInfo->getPathname()) : unlink($fileInfo->getPathname());
        }
    }
}
