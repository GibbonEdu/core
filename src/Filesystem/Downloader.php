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
 * Downloads, verifies, and extracts a sequential core update patch zip.
 *
 * @version v30
 * @since   v30
 */
class Downloader
{
    public const BASE_DIR = 'backups/autoUpdate';
    public const MAX_PACKAGE_BYTES = 104857600; // 100 MB

    /** Catalog checksum algorithm (gibbonedu.org publishes 32-hex MD5 of the zip). */
    public const CHECKSUM_ALGO = 'md5';

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
     * Download → verify → extract.
     *
     * @param array $release
     * @return string[] Relative file paths from the patch.
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

        return $zipPath;
    }

    /**
     * @param string $zipPath
     * @param string $checksum
     */
    public function verify(string $zipPath, string $checksum)
    {
        $checksum = strtolower(trim($checksum));
        $actual = (is_file($zipPath) && $checksum != '') ? hash_file(self::CHECKSUM_ALGO, $zipPath) : false;

        if ($actual === false || !hash_equals($checksum, strtolower($actual))) {
            $this->fail(self::ERROR_VERIFY, $zipPath);
        }
    }

    /**
     * @param string $zipPath
     * @return string[]
     */
    public function extract(string $zipPath) : array
    {
        $staging = $this->getStagingPath();
        $this->wipeDirectory($staging);

        if ((!is_dir($staging) && !mkdir($staging, 0755, true)) || ($stagingReal = realpath($staging)) === false) {
            $this->fail(self::ERROR_WRITE);
        }

        $zip = new ZipArchive();
        if (!is_file($zipPath) || $zip->open($zipPath) !== true) {
            $this->fail(self::ERROR_EXTRACT);
        }

        $files = [];

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $entryName = (string) $zip->getNameIndex($i);
                $name = str_replace('\\', '/', $entryName);

                if ($name == '' || substr($name, -1) == '/' || strpos($name, '__MACOSX/') === 0 || substr($name, -9) == '.DS_Store') {
                    continue;
                }

                $relative = $this->assertSafeRelativePath($name);
                if ($zip->extractTo($stagingReal, $entryName) !== true) {
                    $this->fail(self::ERROR_EXTRACT);
                }

                $extracted = realpath($stagingReal.'/'.$relative);
                if ($extracted === false || strpos($extracted, $stagingReal.DIRECTORY_SEPARATOR) !== 0) {
                    $this->fail(self::ERROR_INVALID);
                }

                $files[] = $relative;
            }
        } catch (\Throwable $e) {
            $this->wipeDirectory($staging);
            throw $e;
        } finally {
            $zip->close();
        }

        if (empty($files) || !in_array('version.php', $files, true)) {
            $this->wipeDirectory($staging);
            $this->fail(self::ERROR_EXTRACT);
        }

        return $files;
    }

    /**
     * @return string
     */
    public function getStagingPath() : string
    {
        return $this->absolutePath.'/'.self::BASE_DIR.'/staging';
    }

    /**
     * @param string $relative
     * @return string
     */
    protected function assertSafeRelativePath(string $relative) : string
    {
        $relative = (string) preg_replace('#/+#', '/', ltrim(str_replace('\\', '/', $relative), '/'));
        $lower = strtolower($relative);

        if ($relative == ''
            || strpos($relative, "\0") !== false
            || preg_match('#(^|/)\.\.(/|$)|^([a-zA-Z]:)#', $relative)
            || $lower == 'config.php'
            || preg_match('#^(uploads|backups|\.git|vendor)/#', $lower)
        ) {
            $this->fail(self::ERROR_INVALID);
        }

        return $relative;
    }

    /**
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
     * @param string $path
     */
    protected function deleteIfExists(string $path)
    {
        if ($path != '' && is_file($path)) {
            unlink($path);
        }
    }

    /**
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
