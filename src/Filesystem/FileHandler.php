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
        if (!is_file($metaData['absolutePath'] ?? '')) {
            return false;
        }

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

            if (($oldFile['filePath'] ?? '') !== ($metaData['filePath'] ?? '')) {
                $oldFilePath = $oldFile['filePath'];
            }
        }

        // All operations succeeded, commit the transaction
        $this->db->commit();

        if ($oldFilePath !== null) {
            $this->unlinkIfUnused($oldFilePath);
        }

        // Return $gibbonFileID
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
        $this->db->beginTransaction();

        $filePointer = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn);
        if (empty($filePointer) || !$this->filePointerGateway->delete($filePointer['gibbonFilePointerID'])) {
            $this->db->rollBack();
            return false;
        }

        $gibbonFileID = $filePointer['gibbonFileID'];
        $filePath = $filePointer['filePath'];
        $pointersExist = $this->filePointerGateway->countPointersByFileID($gibbonFileID)->fetch();
        $pointerCount = (int) ($pointersExist['count'] ?? 0);

        if ($pointerCount == 0 && !$this->fileGateway->delete($gibbonFileID)) {
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();

        if ($pointerCount == 0) {
            $this->unlinkIfUnused($filePath);
        }

        return true;
    }

    /**
     * Stage an editor upload in gibbonFile without creating a pointer
     * @param array $metaData File metadata array
     * @return string|false gibbonFileID on success, false on failure
     */
    public function stageEditorUpload(array $metaData)
    {
        $filePath = $metaData['filePath'] ?? '';
        $existing = $this->fileGateway->getByFilePath($filePath);

        if (!empty($existing['gibbonFileID'])) {
            return (string) (int) $existing['gibbonFileID'];
        }

        $storedPath = $this->getStoredFilePath($filePath);
        if ($storedPath === null || $storedPath !== realpath($metaData['absolutePath'] ?? '')) {
            return false;
        }

        $gibbonFileID = $this->insertAndUpdateFile($metaData);
        if (empty($gibbonFileID)) {
            $this->unlinkIfUnused($filePath);
            return false;
        }

        return $gibbonFileID;
    }

    /**
     * A staged editor file is still in use when another record, pointer, or HTML field stores its path.
     */
    public function isEditorFileUsed(string $filePath, int|string $gibbonFileID): bool
    {
        if (!empty($this->fileGateway->getOtherFileByPath($filePath, $gibbonFileID))) {
            return true;
        }

        return $this->fileGateway->isFilePathInText($filePath);
    }

    /**
     * Remove one unused editor file. The scan records HTML use. The disk file stays when another gibbonFile row still stores the path.
     */
    public function deleteUnusedEditorFile(int|string $gibbonFileID): bool
    {
        $file = $this->fileGateway->getByID($gibbonFileID);
        if (empty($file['filePath'])) {
            return false;
        }

        $pointersExist = $this->filePointerGateway->countPointersByFileID($gibbonFileID)->fetch();
        $pointerCount = (int) ($pointersExist['count'] ?? 0);
        if ($pointerCount > 0) {
            return false;
        }

        if (!$this->fileGateway->delete($gibbonFileID)) {
            return false;
        }

        $this->unlinkIfUnused($file['filePath']);

        return true;
    }

    protected function unlinkIfUnused(string $filePath): void
    {
        if (!empty($this->fileGateway->getByFilePath($filePath))) {
            return;
        }

        $absolutePath = $this->getStoredFilePath($filePath);
        if ($absolutePath !== null) {
            unlink($absolutePath);
        }
    }

    protected function getStoredFilePath(string $filePath): ?string
    {
        if ($filePath === '' || str_contains($filePath, "\0") || str_contains($filePath, '..')) {
            return null;
        }

        $root = realpath((string) $this->session->get('absolutePath'));
        if ($root === false) {
            return null;
        }

        $absolutePath = realpath($root.'/'.ltrim($filePath, '/'));
        if ($absolutePath === false || !is_file($absolutePath) || !str_starts_with($absolutePath, $root.'/')) {
            return null;
        }

        return $absolutePath;
    }
}
