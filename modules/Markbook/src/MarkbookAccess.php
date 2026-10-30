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

namespace Gibbon\Module\Markbook;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Departments\DepartmentGateway;
use Gibbon\Domain\Markbook\MarkbookColumnGateway;
use Gibbon\Domain\Timetable\CourseClassGateway;
use Gibbon\Support\Facades\Access;

/**
 * Markbook Access
 *
 * @package Gibbon\Module\Markbook
 */
class MarkbookAccess
{
    private Session $session;
    private CourseClassGateway $courseClassGateway;
    private MarkbookColumnGateway $markbookColumnGateway;
    private DepartmentGateway $departmentGateway;

    public function __construct(
        Session $session,
        CourseClassGateway $courseClassGateway,
        MarkbookColumnGateway $markbookColumnGateway,
        DepartmentGateway $departmentGateway
    ) {
        $this->session = $session;
        $this->courseClassGateway = $courseClassGateway;
        $this->markbookColumnGateway = $markbookColumnGateway;
        $this->departmentGateway = $departmentGateway;
    }

    /**
     * Check whether the current user can edit this class for the given markbook route.
     *
     * @param string $gibbonCourseClassID
     * @param string $routePath Route name
     * @return bool
     */
    public function allows($gibbonCourseClassID, string $routePath): bool
    {
        if ($gibbonCourseClassID == '' || $routePath == '') {
            return false;
        }

        $action = Access::get('Markbook', $routePath);
        if (!$action->allows()) {
            return false;
        }

        $class = $this->courseClassGateway->getCourseClass($gibbonCourseClassID);
        if (empty($class)) {
            return false;
        }

        if ($action->allowsAny('Edit Markbook_everything', 'Edit Markbook_multipleClassesAcrossSchool', 'Manage Weightings_everything')) {
            return true;
        }

        $gibbonPersonID = $this->session->get('gibbonPersonID');
        if (!empty($this->courseClassGateway->getCourseClassByPerson($gibbonCourseClassID, $gibbonPersonID))) {
            return true;
        }

        return $action->allows('Edit Markbook_multipleClassesInDepartment')
            && $this->isDepartmentEditor($class['gibbonDepartmentID'] ?? '', $gibbonPersonID);
    }

    /**
     * Load a column when the current user can edit its class.
     *
     * @param string $gibbonMarkbookColumnID
     * @param string $gibbonCourseClassID
     * @param string $routePath
     * @return array Column row, or an empty array when access is denied
     */
    public function getColumn($gibbonMarkbookColumnID, $gibbonCourseClassID, string $routePath): array
    {
        $column = $this->fetchColumn($gibbonMarkbookColumnID, $gibbonCourseClassID);

        if (empty($column) || !$this->allows($column['gibbonCourseClassID'], $routePath)) {
            return [];
        }

        return $column;
    }

    /**
     * Load a column for an edit or delete, including the column creator and grouped columns.
     *
     * @param string $gibbonMarkbookColumnID
     * @param string $gibbonCourseClassID
     * @param string $routePath
     * @return array Column row, or an empty array when access is denied
     */
    public function getColumnDefinition($gibbonMarkbookColumnID, $gibbonCourseClassID, string $routePath): array
    {
        $column = $this->fetchColumn($gibbonMarkbookColumnID, $gibbonCourseClassID);
        if (empty($column)) {
            return [];
        }

        $action = Access::get('Markbook', $routePath);
        $gibbonPersonID = $this->session->get('gibbonPersonID');
        $createdByPerson = ($column['gibbonPersonIDCreator'] ?? '') == $gibbonPersonID;

        if (!$this->allows($column['gibbonCourseClassID'], $routePath) && !$createdByPerson) {
            return [];
        }

        if (($column['groupingID'] ?? '') != '' && !$createdByPerson && !$action->allowsAny('Edit Markbook_everything', 'Edit Markbook_multipleClassesAcrossSchool', 'Edit Markbook_multipleClassesInDepartment')) {
            return [];
        }

        return $column;
    }

    /**
     * Fetch a markbook column and confirm it belongs to the requested class.
     *
     * @param string $gibbonMarkbookColumnID
     * @param string $gibbonCourseClassID
     * @return array Column row, or an empty array when it does not match
     */
    protected function fetchColumn($gibbonMarkbookColumnID, $gibbonCourseClassID): array
    {
        $column = $this->markbookColumnGateway->getByID($gibbonMarkbookColumnID);
        if (empty($column) || ($gibbonCourseClassID != '' && $column['gibbonCourseClassID'] != $gibbonCourseClassID)) {
            return [];
        }

        return $column;
    }

    /**
     * Check whether this person edits the department as a coordinator or curriculum teacher.
     *
     * @param string $gibbonDepartmentID
     * @param string $gibbonPersonID
     * @return bool
     */
    protected function isDepartmentEditor($gibbonDepartmentID, $gibbonPersonID): bool
    {
        if ($gibbonDepartmentID == '' || $gibbonPersonID == '') {
            return false;
        }

        $roles = ['Coordinator', 'Assistant Coordinator', 'Teacher (Curriculum)'];

        return !$this->departmentGateway->selectMemberOfDepartmentByRole($gibbonDepartmentID, $gibbonPersonID, $roles)->isEmpty();
    }
}
