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
        // Begin database transaction
        $this->db->beginTransaction();

        // Validate file exists at filePath
        if (!file_exists($metaData['absolutePath'])) {
            return false;
        }

        $oldFile = $this->filePointerGateway->getFileAndPointerID($foreignTable, $foreignTableID, $foreignColumn);

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
            $oldFilePath = $this->session->get('absolutePath') . '/' . $oldFile['filePath'];
        }

        // All operations succeeded, commit the transaction
        $this->db->commit();

        // Delete old file only after successful commit (for updates only)
        if (!empty($oldFilePath) && file_exists($oldFilePath)) {
            unlink($oldFilePath);
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

        if ($pointerCount == 0) {
            // Delete the record from gibbonFile table
            if (!$this->fileGateway->delete($gibbonFileID)) {
                $this->db->rollBack();
                return false;
            }
                
            // Store file path for deletion after transaction commits
            $absolutePath = $this->session->get('absolutePath') . '/' . $filePath;
        }

        // All operations succeeded, commit the transaction
        $this->db->commit();

        // Delete physical file only after successful commit (if no other pointers existed)
        if ($pointerCount == 0 &&!empty($absolutePath) && file_exists($absolutePath)) {
            unlink($absolutePath);
        }
        
        return true;
    }

    ////-/-/--/-/-/-/-/ FOR PHASE-2 /-/-/-/--/--//

    //  /**
    //  * Verify file integrity by comparing stored checksum with recalculated checksum
    //  *
    //  * @param int $gibbonFileID The file record ID to verify
    //  * @return bool True if checksums match, false if mismatch or file missing
    //  */

    // public function verifyFileIntegrity($gibbonFileID)
    // {
        
    //     $file = $this->fileGateway->getByID($gibbonFileID);
        
    //     if (empty($file)) {
    //         return false;
    //     }

    //     $storedChecksum = $file['checksum'];
    //     $filePath = $file['filePath'];
        
    //     // Construct absolute path from stored relative filePath
    //     $absolutePath = $this->session->get('absolutePath') . '/' . $filePath;
        
    //     // Check if file exists at absolute path
    //     if (!file_exists($absolutePath)) {
    //         return false;
    //     }
        
    //     // Recalculate checksum from file at absolute path
    //     $calculatedChecksum = hash_file('sha256', $absolutePath);
        
    //     if (empty($calculatedChecksum)) {
    //         return false;
    //     }
        
    //     // Compare stored and calculated checksums
    //     return $storedChecksum === $calculatedChecksum;
    // }

    //  /**
    //  * Query all file records where the file no longer exists on the filesystem
    //  *
    //  * @return array of records
    //  */
    // public function selectOrphanedFileRecords()
    // {
    //     // Query all records from gibbonFile
    //     $allFiles = $this->fileGateway->selectAllFileRecords();

    //     // Filter to records where file does not exist
    //     $orphanedRecords = [];
    //     foreach ($allFiles as $file) {
    //         $fullPath = $this->session->get('absolutePath'). '/' . $file['filePath'];
    //         if (!file_exists($fullPath)) {
    //             $orphanedRecords[] = $file;
    //         }
    //     }

    //     return $orphanedRecords;
    // }
}
