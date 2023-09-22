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

use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Domain\Library\LibraryTypeGateway;

/**
 * Library Fields migration - moves all serialized data into json data.
 */
class LibraryFields extends Migration
{
    protected $libraryGateway;
    protected $libraryTypeGateway;

    public function __construct(LibraryGateway $libraryGateway, LibraryTypeGateway $libraryTypeGateway)
    {
        $this->libraryGateway = $libraryGateway;
        $this->libraryTypeGateway = $libraryTypeGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Migrate library item fields
        $items = $this->libraryGateway->selectBy([], ['gibbonLibraryItemID', 'fields'])->fetchAll(); 

        foreach ($items as $item) {
            if (empty($item['fields'])) continue;
            if (substr($item['fields'], 0, 2) != 'a:') continue;

            $fieldData = unserialize($item['fields']);
            $partialFail &= !$this->libraryGateway->update($item['gibbonLibraryItemID'], [
                'fields' => !empty($fieldData) ? json_encode($fieldData) : '',
            ]);
        }

        // Migrate library type fields
        $types = $this->libraryTypeGateway->selectBy([], ['gibbonLibraryTypeID', 'fields'])->fetchAll(); 

        foreach ($types as $type) {
            if (empty($type['fields'])) continue;
            if (substr($type['fields'], 0, 2) != 'a:') continue;

            $fieldData = unserialize($type['fields']);
            $partialFail &= !$this->libraryTypeGateway->update($type['gibbonLibraryTypeID'], [
                'fields' => !empty($fieldData) ? json_encode($fieldData) : '',
            ]);
        }

        return !$partialFail;
    }
}
