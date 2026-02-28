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
    private FamilyGateway $familyGateway;
    private UserGateway $userGateway;
    private StudentGateway $studentGateway;
    private SettingGateway $settingGateway;

    public function __construct(
        Session $session,
        FamilyGateway $familyGateway,
        UserGateway $userGateway,
        StudentGateway $studentGateway,
        SettingGateway $settingGateway,
    ) {
        parent::__construct($session);
        $this->familyGateway = $familyGateway;
        $this->userGateway = $userGateway;
        $this->studentGateway = $studentGateway;
        $this->settingGateway = $settingGateway;
    }

    /**
     * Check if the current user has permission to view emergency contacts
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'student_view_details');
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
            return Format::alert(__('You have not specified one or more required parameters.'));
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
        $result = $this->studentGateway->selectActiveStudentWithEmergencyContacts(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID
        );
        
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

        $resultFamily = $this->familyGateway->selectFamiliesByStudent($this->gibbonPersonID);

        if ($resultFamily->rowCount() == 0) {
            return $output . Format::alert(__('There are no records to display.'), 'empty');
        }

        while ($rowFamily = $resultFamily->fetch()) {
            $familyAdults = $this->familyGateway->selectAdultsWithRelationshipByFamily(
                $rowFamily['gibbonFamilyID'],
                $this->gibbonPersonID
            )->fetchAll();

            foreach ($familyAdults as $index => $adult) {
                $table = DataTable::createDetails('family' . $index);

                $table->addColumn('preferredName', __('Name'))
                    ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Parent']));

                $table->addColumn('relationship', __('Relationship'))
                    ->format(function($adult) {
                        return !empty($adult['relationship']) ? __($adult['relationship']) : Format::small(__('Unknown'));
                    });


                $table->addColumn('phone', __('Contact By Phone'))
                    ->format(function($adult) {
                        $phones = '';

                        for ($i = 1; $i < 5; ++$i) {
                            if ($adult['phone'.$i] != '') {
                                $phones .= Format::phone($adult['phone' . $i], $adult['phone'.$i.'CountryCode'], $adult['phone'.$i.'Type']) . '<br/>';
                            }
                        }

                        return $phones;
                    });

                $output .= $table->render([$adult]).'<br>';
            }
        }

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
        $table = DataTable::createDetails('emergency');
        $table->setTitle(__('Emergency Contacts'));

        for ($i = 1; $i <= 2; $i++) {
            $emergency = 'emergency' . $i;
            $table->addColumn($emergency . 'Name', __('Contact ' . $i))
                ->format(function($row) use ($emergency) {
                    if ($row[$emergency . 'Relationship'] != '') {
                        return $row[$emergency . 'Name'] . ' (' . __($row[$emergency . 'Relationship']) . ')';
                    }
                    return $row[$emergency . 'Name'];
                });

            $table->addColumn($emergency . 'Number1', __('Number 1'));
            $table->addColumn($emergency . 'Number2', __('Number 2'));
        }

        return $table->render([$student]);
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
