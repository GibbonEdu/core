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
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

/**
 * EmergencyContactsPage
 * 
 * Displays emergency contact information including adult family members,
 * emergency contacts, and follow-up contacts.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class EmergencyContactsPage extends ProfilePage
{
    private Connection $pdo;
    private FamilyGateway $familyGateway;
    private UserGateway $userGateway;
    private StudentGateway $studentGateway;
    private SettingGateway $settingGateway;
    private \Gibbon\View\View $view;

    public function __construct(
        Session $session,
        Connection $pdo,
        FamilyGateway $familyGateway,
        UserGateway $userGateway,
        StudentGateway $studentGateway,
        SettingGateway $settingGateway,
        \Gibbon\View\View $view
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
        $this->familyGateway = $familyGateway;
        $this->userGateway = $userGateway;
        $this->studentGateway = $studentGateway;
        $this->settingGateway = $settingGateway;
        $this->view = $view;
    }

    /**
     * Check if the current user has permission to view emergency contacts
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'student_view_details', 'View Student Profile_full');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Emergency Contacts');
    }


    /**
     * Generate HTML output for the emergency contacts page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch student data
        $student = $this->fetchStudentData();
        
        // Guard clause: check if student exists
        if (empty($student)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        $output = '';

        // Add edit button if user has permission
        if (Access::allows('User Admin', 'user_manage')) {
            $form = Form::createBlank('buttons');
            $form->addHeaderAction('edit', __('Edit User'))
                ->setURL('/modules/User Admin/user_manage_edit.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->displayLabel();
            $output .= $form->getOutput();
        }

        $output .= '<p>';
        $output .= __('In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below.');
        $output .= '</p>';

        // Render adult family members
        $output .= $this->renderAdultFamilyMembers();

        // Render emergency contacts
        $output .= $this->renderEmergencyContacts($student);

        // Render follow-up contacts
        $output .= $this->renderFollowUpContacts($student);

        return $output;
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
        
        $result = $this->pdo->select($sql, $data);
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }

    /**
     * Render adult family members section
     * 
     * @return string HTML for adult family members
     */
    protected function renderAdultFamilyMembers(): string
    {
        $output = '<h4>';
        $output .= __('Adult Family Members');
        $output .= '</h4>';

        $dataFamily = ['gibbonPersonID' => $this->gibbonPersonID];
        $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
        $resultFamily = $this->pdo->select($sqlFamily, $dataFamily);

        if ($resultFamily->rowCount() == 0) {
            return $output . Format::alert(__('There are no records to display.'), 'empty');
        }

        while ($rowFamily = $resultFamily->fetch()) {
            $dataMember = ['gibbonFamilyID' => $rowFamily['gibbonFamilyID']];
            $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
            $resultMember = $this->pdo->select($sqlMember, $dataMember);

            while ($rowMember = $resultMember->fetch()) {
                $output .= $this->renderAdultMember($rowMember, $rowFamily['gibbonFamilyID']);
            }
        }

        return $output;
    }

    /**
     * Render a single adult family member
     * 
     * @param array $member Adult member data
     * @param string $gibbonFamilyID Family ID
     * @return string HTML for adult member
     */
    protected function renderAdultMember(array $member, string $gibbonFamilyID): string
    {
        $output = "<table class='smallIntBorder mb-2' cellspacing='0' style='width: 100%'>";
        $output .= '<tr>';
        $output .= "<td style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
        $output .= Format::name($member['title'], $member['preferredName'], $member['surname'], 'Parent');
        $output .= '</td>';
        $output .= "<td style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Relationship').'</span><br/>';

        $dataRelationship = [
            'gibbonPersonID1' => $member['gibbonPersonID'],
            'gibbonPersonID2' => $this->gibbonPersonID,
            'gibbonFamilyID' => $gibbonFamilyID
        ];
        $sqlRelationship = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID';
        $resultRelationship = $this->pdo->select($sqlRelationship, $dataRelationship);
        
        if ($resultRelationship->rowCount() == 1) {
            $rowRelationship = $resultRelationship->fetch();
            $output .= __($rowRelationship['relationship']);
        } else {
            $output .= '<i>'.__('Unknown').'</i>';
        }

        $output .= '</td>';
        $output .= "<td style='width: 34%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Phone').'</span><br/>';
        
        for ($i = 1; $i < 5; ++$i) {
            if ($member['phone'.$i] != '') {
                if ($member['phone'.$i.'Type'] != '') {
                    $output .= $member['phone'.$i.'Type'].':</i> ';
                }
                if ($member['phone'.$i.'CountryCode'] != '') {
                    $output .= '+'.$member['phone'.$i.'CountryCode'].' ';
                }
                $output .= __($member['phone'.$i]).'<br/>';
            }
        }
        
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';

        return $output;
    }

    /**
     * Render emergency contacts section
     * 
     * @param array $student Student data
     * @return string HTML for emergency contacts
     */
    protected function renderEmergencyContacts(array $student): string
    {
        $output = '<h4>';
        $output .= __('Emergency Contacts');
        $output .= '</h4>';
        $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
        $output .= '<tr>';
        $output .= "<td style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact 1').'</span><br/>';
        $output .= $student['emergency1Name'];
        if ($student['emergency1Relationship'] != '') {
            $output .= ' ('.__($student['emergency1Relationship']).')';
        }
        $output .= '</td>';
        $output .= "<td style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Number 1').'</span><br/>';
        $output .= $student['emergency1Number1'];
        $output .= '</td>';
        $output .= "<td style='width: 34%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Number 2').'</span><br/>';
        if ($student['emergency1Number2'] != '') {
            $output .= $student['emergency1Number2'];
        }
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact 2').'</span><br/>';
        $output .= $student['emergency2Name'];
        if ($student['emergency2Relationship'] != '') {
            $output .= ' ('.__($student['emergency2Relationship']).')';
        }
        $output .= '</td>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Number 1').'</span><br/>';
        $output .= $student['emergency2Number1'];
        $output .= '</td>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Number 2').'</span><br/>';
        if ($student['emergency2Number2'] != '') {
            $output .= $student['emergency2Number2'];
        }
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        $output .= '<br/><br/>';

        return $output;
    }

    /**
     * Render follow-up contacts section
     * 
     * @param array $student Student data
     * @return string HTML for follow-up contacts
     */
    protected function renderFollowUpContacts(array $student): string
    {
        $contacts = [];
        $emergencyFollowUpGroup = $this->settingGateway->getSettingByScope('Students', 'emergencyFollowUpGroup');

        if (!empty($emergencyFollowUpGroup)) {
            $contactsList = explode(',', $emergencyFollowUpGroup) ?? [];
            $contacts = $this->userGateway->selectNotificationDetailsByPerson($contactsList)->fetchAll();
        }

        $staff = $this->studentGateway->selectAllRelatedUsersByStudent(
            $this->gibbonSchoolYearID,
            $student['gibbonYearGroupID'],
            $student['gibbonFormGroupID'],
            $this->gibbonPersonID,
            false
        )->fetchAll();

        $familyAdults = $this->familyGateway->selectFamilyAdultsByStudent($this->gibbonPersonID)->fetchAll();
        $familyAdults = array_filter($familyAdults, function ($parent) {
            return $parent['contactEmail'] == 'Y';
        });

        $table = DataTable::create('followupMedicalContacts');
        $table->setTitle(__('Follow-up Contacts'));
        $table->setDescription(__('These contacts can be used when following up on an emergency, or for less serious issues, when parents and staff need to be notified by email.'));

        $table->addColumn('fullName', __('Name'))
            ->notSortable()
            ->format(function ($person) {
                return Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true);
            });
        
        $table->addColumn('email', __('Email'))
            ->notSortable()
            ->format(function ($person) {
                $person['email'] = filter_var(trim($person['email']), FILTER_SANITIZE_EMAIL);
                return htmlPrep('<'.$person['email'].'>');
            });
        
        $table->addColumn('context', __('Context'))
            ->notSortable()
            ->format(function ($person) {
                if ($person['type'] == 'Family') {
                    $person['type'] = $person['type'].', '.$person['relationship'];
                } elseif ($person['type'] == 'Teaching' || $person['type'] == 'Support') {
                    $person['type'] = $person['jobTitle'];
                }

                if (!empty($person['classID'])) {
                    return Format::link('./index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$person['classID'], __($person['type']), ['class' => 'unselectable underline']);
                } else {
                    return '<span class="unselectable">'.__($person['type']).'</span>';
                }
            });

        return $table->render(new DataSet(array_merge($familyAdults, $contacts, $staff)));
    }
}
