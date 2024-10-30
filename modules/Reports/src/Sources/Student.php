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

class Student extends DataSource
{
    public function getSchema()
    {
        $gender = rand(0, 99) > 50 ? 'female' : 'male';
        return [
            'gibbonPersonID'     => ['randomNumber', 8],
            'surname'            => ['lastName'],
            'firstName'          => ['firstName', $gender],
            'preferredName'      => ['sameAs', 'firstName'],
            'officialName'       => ['sameAs', 'firstName surname'],
            'image_240'          => $gender == 'female' ? 'modules/Reports/img/placeholder-female.jpg' : 'modules/Reports/img/placeholder-male.jpg',
            'dob'                => ['date', 'Y-m-d'],
            'email'              => ['safeEmail'],
            'nameInCharacters'   => 'TEST',
            'studentID'          => ['randomNumber', 8],
            'dayType'            => ['randomElement', ['Full Day', 'Half Day']],

            '#'                  => ['randomDigit'], // Random Year Group Number
            '%'                  => ['randomDigit'], // Random Form Group Number

            'gibbonYearGroupID'  => 0,
            'yearGroupName'      => ['sameAs', 'Year #'],
            'yearGroupNameShort' => ['sameAs', 'Y0#'],

            'gibbonFormGroupID'  => 0,
            'formGroupName'      => ['sameAs', 'Y0#.%'],
            'formGroupNameShort' => ['sameAs', 'Y0#.%'],
        ];
    }

    public function getData($ids = [])
    {
        $data = ['gibbonStudentEnrolmentID' => $ids['gibbonStudentEnrolmentID']];
        $sql = "SELECT 
                gibbonPerson.gibbonPersonID,
                gibbonPerson.surname,
                gibbonPerson.firstName,
                gibbonPerson.preferredName,
                gibbonPerson.officialName,
                gibbonPerson.image_240,
                gibbonPerson.dob,
                gibbonPerson.email,
                gibbonPerson.nameInCharacters,
                gibbonPerson.studentID,
                gibbonPerson.dayType,
                gibbonPerson.gender,
                gibbonYearGroup.gibbonYearGroupID,
                gibbonYearGroup.name as yearGroupName,
                gibbonYearGroup.nameShort as yearGroupNameShort,
                gibbonFormGroup.gibbonFormGroupID,
                gibbonFormGroup.name as formGroupName,
                gibbonFormGroup.nameShort as formGroupNameShort
                FROM gibbonStudentEnrolment 
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                WHERE gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID";

        return $this->db()->selectOne($sql, $data);
    }
}
