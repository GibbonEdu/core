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

namespace Gibbon\Module\Reports\Sources;

use Gibbon\Module\Reports\DataSource;

class CustomFields extends DataSource
{
    public function getSchema()
    {
        return [
            'Field Name'   => ['sentence'],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID']];
        $sql = "SELECT gibbonPerson.fields
                FROM gibbonStudentEnrolment 
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID";

        $fieldData = $this->db()->selectOne($sql, $data);
        $personFields = json_decode($fieldData ?? '', true);

        $sql = "SELECT name, gibbonCustomFieldID FROM gibbonCustomField WHERE active='Y' AND context='User' AND activePersonStudent=1";
        $fields = $this->db()->select($sql)->fetchKeyPair();
        
        $personFields = array_map(function ($id) use ($personFields) {
            return $personFields[$id] ?? '';
        }, $fields);

        return $personFields;
    }
}
