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

namespace Gibbon\Contracts\Filesystem;

/**
 * File Handler Interface
 *
 * @version	v31
 * @since	v31
 */
interface FileHandler
{
      /**
     * Record a file upload with pointer in a transaction
     * @param array $metaData Array with file metadata
     * @param int $gibbonPersonIDOwner gibbonPersonID of uploader
     * @param string|null $foreignTable Name of the foreign table (nullable)
     * @param int|null $foreignTableID Primary key value in the foreign table (nullable)
     * @param string $foreignColumn Column name storing the file path
     * @return int|false gibbonFileID on success, false on failure
     */
    public function recordFileUpload(array $metaData, string $foreignTable, int|string $foreignTableID, string $foreignColumn);

    /**
     * Delete a file
     * @param string|null $foreignTable Name of the foreign table (nullable)
     * @param int|null $foreignTableID Primary key value in the foreign table (nullable)
     * @param string $foreignColumn Column name storing the file path
     * @return bool
     */
    public function deleteFile(string $foreignTable, int|string $foreignTableID, string $foreignColumn);
}
