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

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Students\FirstAidGateway;
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
    private FirstAidGateway $firstAidGateway;
    private $connection2;
    private $guid;

    public function __construct(
        Session $session,
        FirstAidGateway $firstAidGateway,
        $connection2,
        $guid
    ) {
        parent::__construct($session);
        $this->firstAidGateway = $firstAidGateway;
        $this->connection2 = $connection2;
        $this->guid = $guid;
    }

    /**
     * Check if the current user has permission to view first aid records
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'View Student Profile_full')) {
            return false;
        }

        return isActionAccessible($this->guid, $this->connection2, '/modules/Students/firstAidRecord.php');
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
            return Format::alert(__('Invalid student ID.'));
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

        $highestActionFirstAid = getHighestGroupedAction($this->guid, '/modules/Students/firstAidRecord.php', $this->connection2);
        $table->addActionColumn()
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->addParam('gibbonFormGroupID', $student['gibbonFormGroupID'])
            ->addParam('gibbonYearGroupID', $student['gibbonYearGroupID'])
            ->addParam('gibbonFirstAidID')
            ->format(function ($person, $actions) use ($highestActionFirstAid) {
                if ($highestActionFirstAid == 'First Aid Record_editAll') {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Students/firstAidRecord_edit.php');
                } elseif ($highestActionFirstAid == 'First Aid Record_viewOnlyAddNotes') {
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
        $data = [
            'gibbonSchoolYearID' => $this->gibbonSchoolYearID,
            'gibbonPersonID' => $this->gibbonPersonID
        ];
        
        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID 
                FROM gibbonPerson 
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                AND status='Full' 
                AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
                AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') 
                AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
        
        $result = $this->connection2->prepare($sql);
        $result->execute($data);
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }
}
