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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Filesystem\FileHandler as FileHandlerInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\FileGateway;
use Gibbon\Domain\System\FilePointerGateway;

/**
 * File Handler Class
 *
 * @version	v31
 * @since	v31
 */
class FileHandler implements FileHandlerInterface
{
    private const BLOCKED_EXTENSIONS = ['js', 'htm', 'html', 'css', 'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'asp', 'jsp', 'py', 'svg'];

    protected Connection $db;
    protected Session $session;
    protected FileGateway $fileGateway;
    protected FilePointerGateway $filePointerGateway;
    
    public function __construct(Connection $db, Session $session, FileGateway $fileGateway, FilePointerGateway $filePointerGateway)
    {
        $this->db = $db;
        $this->session = $session;
        $this->filePointerGateway = $filePointerGateway;
        $this->fileGateway = $fileGateway;
    }

    /**
     * {@inheritdoc}
     */
    public function recordFileUpload(array $metaData, string $foreignTable, int|string $foreignTableID, string $foreignColumn)
    {
        $absolutePath = $metaData['absolutePath'] ?? '';
        $filePath = $metaData['filePath'] ?? '';
        $storedPath = $this->resolveStoredPath($filePath);
        if ($storedPath === null || realpath((string) $absolutePath) !== $storedPath) {
            return false;
        }

        $this->db->beginTransaction();

        $oldFile = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn, true);

        if (empty($oldFile)) {
            $gibbonFileID = $this->resolveFileRecord($metaData);

            if (empty($gibbonFileID)) {
                $this->db->rollBack();
                return false;
            }

            $gibbonFilePointerID = $this->filePointerGateway->insert([
                'gibbonFileID' => $gibbonFileID,
                'foreignTable' => $foreignTable,
                'foreignTableID' => $foreignTableID,
                'foreignColumn' => $foreignColumn,
            ]);

            if (empty($gibbonFilePointerID)) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();

            $previousPath = $metaData['previousFilePath'] ?? '';
            if (is_string($previousPath) && $previousPath !== '' && $previousPath !== $filePath) {
                $this->unlinkIfUnused($previousPath);
            }

            return $gibbonFileID;
        }

        if (($oldFile['filePath'] ?? '') === $filePath) {
            $gibbonFileID = $this->insertAndUpdateFile($metaData, $oldFile['gibbonFileID']);
            if (empty($gibbonFileID)) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return $gibbonFileID;
        }

        $oldFileID = (int) $oldFile['gibbonFileID'];
        $oldFilePath = $oldFile['filePath'] ?? '';
        $pointerID = $oldFile['gibbonFilePointerID'];
        $sharedWithOtherLocations = $this->pointerCount($oldFileID, true) > 1;

        if ($sharedWithOtherLocations) {
            $gibbonFileID = $this->resolveFileRecord($metaData);
            if (empty($gibbonFileID) || !$this->filePointerGateway->update($pointerID, ['gibbonFileID' => $gibbonFileID])) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return $gibbonFileID;
        }

        $existingNewFile = $this->fileGateway->getByFilePath($filePath, true) ?: [];
        if (!empty($existingNewFile['gibbonFileID']) && (int) $existingNewFile['gibbonFileID'] !== $oldFileID) {
            if (!$this->filePointerGateway->update($pointerID, ['gibbonFileID' => $existingNewFile['gibbonFileID']])) {
                $this->db->rollBack();
                return false;
            }
            $removed = $this->fileGateway->deleteIfUnreferenced($oldFileID);

            $this->db->commit();
            if ($removed > 0) {
                $this->unlinkIfUnused($oldFilePath);
            }
            return $existingNewFile['gibbonFileID'];
        }

        $gibbonFileID = $this->insertAndUpdateFile($metaData, $oldFileID);
        if (empty($gibbonFileID)) {
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        $this->unlinkIfUnused($oldFilePath);

        return $gibbonFileID;
    }

    protected function insertAndUpdateFile(array $metaData, $gibbonFileID = null)
    {
        // Calculate SHA-256 checksum
        $checksum = hash_file('sha256', $metaData['absolutePath']);
        if ($checksum === false) {
            return false;
        }

        // Build data array with all fields
        $data = [
            'filePath' => $metaData['filePath'] ?? '',
            'fileName' => $metaData['fileName'] ?? '',
            'fileExtension' => $metaData['fileExtension'] ?? '',
            'fileSize' => $metaData['fileSize'] ?? '',
            'mimeType' => $metaData['mimeType'] ?? '',
            'gibbonPersonIDOwner' => !empty($metaData['gibbonPersonIDOwner']) ? $metaData['gibbonPersonIDOwner'] : null,
            'uploadedAt' => date('Y-m-d H:i:s'),
            'checksum' => $checksum
        ];

        if (empty($gibbonFileID)) {
            // Insert record into gibbonFile table
            $gibbonFileID = $this->fileGateway->insert($data);
            
            if (empty($gibbonFileID)) {
                return false;
            }
        } else {
            if (!$this->fileGateway->update($gibbonFileID, $data)) {
                return false;
            }
        }

        return $gibbonFileID;
    }

    /**
     * Delete a file and its pointer
     * @param string $foreignTable Name of the foreign table
     * @param int|string $foreignTableID Primary key value in the foreign table
     * @param string $foreignColumn Column name storing the file path
     * @return bool True on success, false on failure
     */
    public function deleteFile(string $foreignTable, int|string $foreignTableID, string $foreignColumn)
    {
        // Begin database transaction
        $this->db->beginTransaction();

        // Find the pointer and get the gibbonFileID and filePath
        $filePointer = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn, true);
            
        if (empty($filePointer)) {
            $this->db->rollBack();
            return false;
        }

        $unusedPath = $this->detachPointer($filePointer);
        if ($unusedPath === false) {
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();

        if (is_string($unusedPath) && $unusedPath !== '') {
            $this->unlinkIfUnused($unusedPath);
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function linkExistingFile(string $foreignTable, int|string $foreignTableID, string $foreignColumn, string $filePath)
    {
        if ($foreignTable === '' || $foreignTableID === '' || $foreignColumn === '' || $filePath === '') {
            return false;
        }

        $existingPointer = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn);
        if (!empty($existingPointer) && ($existingPointer['filePath'] ?? '') === $filePath) {
            return true;
        }

        $absolutePath = $this->resolveStoredPath($filePath);
        if ($absolutePath === null) {
            return false;
        }
        $onDisk = is_file($absolutePath);
        $metaData = [
            'absolutePath' => $absolutePath,
            'filePath' => $filePath,
            'fileName' => basename($filePath),
            'fileExtension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'fileSize' => $onDisk ? (filesize($absolutePath) ?: 0) : 0,
            'mimeType' => ($onDisk && function_exists('mime_content_type')) ? (mime_content_type($absolutePath) ?: '') : '',
            'gibbonPersonIDOwner' => $this->session->get('gibbonPersonID') ?? '',
        ];

        if (!empty($existingPointer)) {
            if (!$onDisk) {
                return false;
            }

            return $this->recordFileUpload($metaData, $foreignTable, $foreignTableID, $foreignColumn);
        }

        $this->db->beginTransaction();
        $gibbonFileID = $this->resolveFileRecord($metaData);
        if (empty($gibbonFileID)) {
            $this->db->rollBack();
            return false;
        }

        $gibbonFilePointerID = $this->filePointerGateway->insert([
            'gibbonFileID' => $gibbonFileID,
            'foreignTable' => $foreignTable,
            'foreignTableID' => $foreignTableID,
            'foreignColumn' => $foreignColumn,
        ]);

        if (empty($gibbonFilePointerID)) {
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFilesForRecord(string $foreignTable, int|string|array $foreignTableID): void
    {
        $ids = array_values(array_unique(array_filter((array) $foreignTableID, function ($id) {
            return is_scalar($id) && !is_bool($id) && $id !== '';
        })));

        if ($foreignTable === '' || $ids === []) {
            return;
        }

        $paths = [];
        foreach (array_chunk($ids, 200) as $chunk) {
            $pointers = $this->filePointerGateway->selectByRecordIDs($foreignTable, $chunk)->fetchAll();
            if (empty($pointers)) {
                continue;
            }

            $this->db->beginTransaction();
            foreach ($pointers as $pointer) {
                $unusedPath = $this->detachPointer($pointer);
                if ($unusedPath === false) {
                    $this->db->rollBack();
                    return;
                }
                if (is_string($unusedPath) && $unusedPath !== '') {
                    $paths[$unusedPath] = true;
                }
            }
            $this->db->commit();
        }

        foreach (array_keys($paths) as $path) {
            $this->unlinkIfUnused($path);
        }
    }

    /**
     * Drop one pointer and its file row when nothing else references that row.
     * Returns the path to unlink, null when the file is still in use, or false on failure.
     *
     * @return string|null|false
     */
    protected function detachPointer(array $filePointer)
    {
        if (empty($filePointer['gibbonFilePointerID']) || !$this->filePointerGateway->delete($filePointer['gibbonFilePointerID'])) {
            return false;
        }

        if ($this->fileGateway->deleteIfUnreferenced($filePointer['gibbonFileID']) > 0) {
            return $filePointer['filePath'] ?? '';
        }

        return null;
    }

    /**
     * Reuse a gibbonFile row when this path is already tracked, otherwise insert one.
     *
     * @return int|false
     */
    protected function resolveFileRecord(array $metaData)
    {
        $existingFile = $this->fileGateway->getByFilePath($metaData['filePath'] ?? '', true);
        if (!empty($existingFile['gibbonFileID'])) {
            return $existingFile['gibbonFileID'];
        }

        if (empty($metaData['absolutePath']) || !is_file($metaData['absolutePath'])) {
            return false;
        }

        return $this->insertAndUpdateFile($metaData);
    }

    protected function pointerCount(int $gibbonFileID, bool $lock = false): int
    {
        $row = $this->filePointerGateway->countPointersByFileID($gibbonFileID, $lock)->fetch();

        return (int) ($row['count'] ?? 0);
    }
    
    protected function unlinkIfUnused(string $filePath): void
    {
        $absolutePath = $this->resolveStoredPath($filePath);
        if ($absolutePath === null) {
            return;
        }

        $root = realpath((string) $this->session->get('absolutePath'));
        $relativePath = $root ? ltrim(substr($absolutePath, strlen($root)), '/') : $filePath;
        if ($this->fileGateway->getByFilePath($filePath) || ($relativePath !== $filePath && $this->fileGateway->getByFilePath($relativePath))) {
            return;
        }

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    /**
     * Real path of a stored relative path. Refuses traversal, links outside this install, and script files.
     */
    protected function resolveStoredPath(string $filePath): ?string
    {
        $filePath = str_replace('\\', '/', $filePath);
        if ($filePath === '' || str_contains($filePath, "\0") || str_contains($filePath, '..')) {
            return null;
        }

        $root = realpath((string) $this->session->get('absolutePath'));
        if ($root === false) {
            return null;
        }

        $real = realpath($root.'/'.ltrim($filePath, '/'));
        if ($real === false || !is_file($real) || !str_starts_with($real, $root.'/')) {
            return null;
        }

        $base = strtolower(basename($real));
        $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if ($base === '.htaccess' || $base === '.user.ini' || $base === 'web.config' || str_starts_with($base, '.') || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return null;
        }

        return $real;
    }
}
