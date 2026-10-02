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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Students\FirstAidGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * FirstAidPage
 * 
 * Displays first aid incident records for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class FirstAidPage extends ProfilePage
{
    private StudentGateway $studentGateway;
    private FirstAidGateway $firstAidGateway;

    public function __construct(
        Session $session,
        StudentGateway $studentGateway,
        FirstAidGateway $firstAidGateway
    ) {
        parent::__construct($session);
        $this->studentGateway = $studentGateway;
        $this->firstAidGateway = $firstAidGateway;
    }

    /**
     * Check if the current user has permission to view first aid records
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Students', 'firstAidRecord');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('First Aid');
    }


    /**
     * Generate HTML output for the first aid page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        // Fetch student data for action column
        $student = $this->fetchStudentData();
        
        // Guard clause: check if student exists
        if (empty($student)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        $criteria = $this->firstAidGateway->newQueryCriteria()
            ->sortBy(['date', 'timeIn'], 'DESC')
            ->fromPOST('firstAid');

        $firstAidRecords = $this->firstAidGateway->queryFirstAidByStudent(
            $criteria,
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );

        // DATA TABLE
        $table = DataTable::createPaginated('firstAidRecords', $criteria);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Students/firstAidRecord_add.php')
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->displayLabel();

        $table->addExpandableColumn('details')->format(function($person) {
            $output = '';
            if ($person['description'] != '') {
                $output .= '<b>'.__('Description').'</b><br/>'.nl2br($person['description']).'<br/><br/>';
            }
            if ($person['actionTaken'] != '') {
                $output .= '<b>'.__('Action Taken').'</b><br/>'.nl2br($person['actionTaken']).'<br/><br/>';
            }
            if ($person['followUp'] != '') {
                $output .= '<b>'.__("Follow Up by {name} at {date}", [
                    'name' => Format::name('', $person['preferredNameFirstAider'], $person['surnameFirstAider']),
                    'date' => Format::dateTimeReadable($person['timestamp'])
                ]).'</b><br/>'.nl2br($person['followUp']).'<br/><br/>';
            }
            
            $resultLog = $this->firstAidGateway->queryFollowUpByFirstAidID($person['gibbonFirstAidID']);
            foreach ($resultLog as $rowLog) {
                $output .= '<b>'.__("Follow Up by {name} at {date}", [
                    'name' => Format::name('', $rowLog['preferredName'], $rowLog['surname']),
                    'date' => Format::dateTimeReadable($rowLog['timestamp'])
                ]).'</b><br/>'.nl2br($rowLog['followUp']).'<br/><br/>';
            }

            return $output;
        });

        $table->addColumn('firstAider', __('First Aider'))
            ->sortable(['surnameFirstAider', 'preferredNameFirstAider'])
            ->format(Format::using('name', ['', 'preferredNameFirstAider', 'surnameFirstAider', 'Staff', false, true]));

        $table->addColumn('date', __('Date'))
            ->format(Format::using('date', ['date']));

        $table->addColumn('time', __('Time'))
            ->sortable(['timeIn', 'timeOut'])
            ->format(Format::using('timeRange', ['timeIn', 'timeOut']));

        $highestActionFirstAid = Access::get('Students', 'firstAidRecord');
        $table->addActionColumn()
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->addParam('gibbonFormGroupID', $student['gibbonFormGroupID'])
            ->addParam('gibbonYearGroupID', $student['gibbonYearGroupID'])
            ->addParam('gibbonFirstAidID')
            ->format(function ($person, $actions) use ($highestActionFirstAid) {
                if ($highestActionFirstAid->allows('First Aid Record_editAll')) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Students/firstAidRecord_edit.php');
                } elseif ($highestActionFirstAid->allows('First Aid Record_viewOnlyAddNotes')) {
                    $actions->addAction('view', __('View'))
                        ->setURL('/modules/Students/firstAidRecord_edit.php');
                }
            });

        return $table->render($firstAidRecords);
    }

    /**
     * Fetch student data from database
     * 
     * @return array Student data or empty array if not found
     */
    protected function fetchStudentData(): array
    {
        $result = $this->studentGateway->selectActiveStudentByPerson(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }
}
