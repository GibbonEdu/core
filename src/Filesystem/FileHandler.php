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
use Gibbon\FileUploader;

/**
 * File Handler Class
 *
 * @version	v31
 * @since	v31
 */
class FileHandler implements FileHandlerInterface
{
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
     * Resolve a stored upload path to a real file inside the installation uploads directory.
     */
    public static function resolveStoredPath(string $root, string $filePath): ?string
    {
        $filePath = str_replace('\\', '/', trim($filePath));
        if ($filePath === '' || str_contains($filePath, "\0") || str_contains($filePath, ':')) {
            return null;
        }

        $segments = explode('/', ltrim($filePath, '/'));
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || str_starts_with($segment, '.')) {
                return null;
            }
        }

        if (($segments[0] ?? '') !== 'uploads') {
            return null;
        }

        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, FileUploader::getIllegalFileExtensions(), true)) {
            return null;
        }

        $root = rtrim(str_replace('\\', '/', $root), '/');
        $realPath = self::realPathInside($root.'/'.implode('/', $segments), $root.'/uploads');

        return !empty($realPath) && is_file($realPath) ? $realPath : null;
    }

    /**
     * Real path when it is the directory itself or a file inside it, otherwise null.
     */
    public static function realPathInside(string $path, string $directory): ?string
    {
        $path = realpath(str_replace('\\', '/', $path));
        $directory = realpath(str_replace('\\', '/', $directory));
        if ($path === false || $directory === false) {
            return null;
        }

        $pathCompare = rtrim(str_replace('\\', '/', $path), '/');
        $directoryCompare = rtrim(str_replace('\\', '/', $directory), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $pathCompare = strtolower($pathCompare);
            $directoryCompare = strtolower($directoryCompare);
        }

        if ($pathCompare !== $directoryCompare && !str_starts_with($pathCompare, $directoryCompare.'/')) {
            return null;
        }

        return str_replace('\\', '/', $path);
    }

    /**
     * {@inheritdoc}
     */
    public function recordFileUpload(array $metaData, string $foreignTable, int|string $foreignTableID, string $foreignColumn)
    {
        $relativePath = (string) ($metaData['filePath'] ?? '');
        $realPath = self::resolveStoredPath((string) $this->session->get('absolutePath'), $relativePath);
        if ($realPath === null) {
            return false;
        }

        $metaData['filePath'] = str_replace('\\', '/', trim($relativePath));
        $metaData['absolutePath'] = $realPath;

        // Begin database transaction
        $this->db->beginTransaction();

        $oldFile = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn);
        $oldFilePath = null;

        if (empty($oldFile)) {
            // Insert record into gibbonFile table
            $gibbonFileID = $this->insertAndUpdateFile($metaData);

            // If recordFileUpload fails, rollback and return false
            if (empty($gibbonFileID)) {
                $this->db->rollBack();
                return false;
            }

            // Call recordFilePointer with the gibbonFileID
            $data = [
            'gibbonFileID' => $gibbonFileID,
            'foreignTable' => $foreignTable,
            'foreignTableID' => $foreignTableID,
            'foreignColumn' => $foreignColumn
            ];

            $gibbonFilePointerID = $this->filePointerGateway->insert($data);

            // If pointer insertion fails, rollback transaction and return false
            if (empty($gibbonFilePointerID)) {
                $this->db->rollBack();
                return false;
            }
        } else {
            $gibbonFileID = $this->insertAndUpdateFile($metaData, $oldFile['gibbonFileID']);

            // If update fails, rollback and return false
            if (empty($gibbonFileID)) {
                $this->db->rollBack();
                return false;
            }

            // Store old file path for deletion after transaction commits
            $oldFilePath = self::resolveStoredPath((string) $this->session->get('absolutePath'), (string) ($oldFile['filePath'] ?? ''));
            if ($oldFilePath === $realPath) {
                $oldFilePath = null;
            }
        }

        // All operations succeeded, commit the transaction
        $this->db->commit();

        // Delete old file only after successful commit (for updates only)
        if (!empty($oldFilePath)) {
            unlink($oldFilePath);
        }

        // Return $gibbonFileID
        return $gibbonFileID;
    }

    protected function insertAndUpdateFile(array $metaData, $gibbonFileID = null)
    {
        $relativePath = str_replace('\\', '/', trim((string) ($metaData['filePath'] ?? '')));
        $realPath = self::resolveStoredPath((string) $this->session->get('absolutePath'), $relativePath);
        if ($realPath === null) {
            return false;
        }

        // Calculate SHA-256 checksum
        $checksum = hash_file('sha256', $realPath);
        if ($checksum === false) {
            return false;
        }

        $metaData['filePath'] = $relativePath;

        // Build data array with all fields
        $data = [
            'filePath' => $metaData['filePath'] ?? '',
            'fileName' => $metaData['fileName'] ?? '',
            'fileExtension' => $metaData['fileExtension'] ?? '',
            'fileSize' => $metaData['fileSize'] ?? '',
            'mimeType' => $metaData['mimeType'] ?? '',
            'gibbonPersonIDOwner' => $metaData['gibbonPersonIDOwner'] ?? '',
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
        $filePointer = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn);
            
        if (empty($filePointer)) {
            $this->db->rollBack();
            return false;
        }

        $gibbonFileID = $filePointer['gibbonFileID'];
        $filePath = $filePointer['filePath'];
            
        // Delete the pointer
        if (!$this->filePointerGateway->delete($filePointer['gibbonFilePointerID'])) {
            $this->db->rollBack();
            return false;
        }

        // Check if the file has any other pointers
        $pointersExist = $this->filePointerGateway->countPointersByFileID($gibbonFileID)->fetch();
        $pointerCount = $pointersExist['count'];
        $absolutePath = null;

        if ($pointerCount == 0) {
            // Delete the record from gibbonFile table
            if (!$this->fileGateway->delete($gibbonFileID)) {
                $this->db->rollBack();
                return false;
            }
                
            // Store file path for deletion after transaction commits
            $absolutePath = self::resolveStoredPath((string) $this->session->get('absolutePath'), (string) $filePath);
        }

        // All operations succeeded, commit the transaction
        $this->db->commit();

        // Delete physical file only after successful commit (if no other pointers existed)
        if ($pointerCount == 0 && !empty($absolutePath)) {
            unlink($absolutePath);
        }
        
        return true;
    }
}
