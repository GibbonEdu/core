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
use Gibbon\Services\Format;

class ClassTeachers extends DataSource
{
    public function getSchema()
    {
        $gender = rand(0, 99) > 50 ? 'female' : 'male';

        return [
            0 => [ 
                'title'         => ['title', $gender],
                'surname'       => ['lastName'],
                'firstName'     => ['firstName', $gender],
                'preferredName' => ['sameAs', 'firstName'],
                'officialName'  => ['sameAs', 'firstName surname'],
                'fullName'      => ['sameAs', 'title firstName surname'],
                'email'         => ['sameAs', 'firstName.surname@example.com'],
            ],
        ];
    }

    public function getData($ids = [])
    {
        $data = array('gibbonCourseClassID' => $ids['gibbonCourseClassID']);
        $sql = "SELECT gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName
                FROM gibbonCourseClass
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID = gibbonCourseClass.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID
                AND gibbonCourseClassPerson.role='Teacher'
                AND gibbonCourseClassPerson.reportable = 'Y'
                AND gibbonPerson.status='Full'";

        $result = $this->db()->select($sql, $data);
        $values = array();

        if ($result->rowCount() > 0) {
            $values = $result->fetchAll();

            foreach ($values as $key => &$value) {
                $value['fullName'] = Format::name($value['title'], substr($value['preferredName'], 0, 1).'.', $value['surname'], 'Staff', false, false);
            }
        }

        return $values;
    }
}
