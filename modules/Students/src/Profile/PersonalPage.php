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
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * PersonalPage
 * 
 * Displays student personal information including basic information, contact details,
 * school information, background information, system access, custom fields, and documents.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class PersonalPage extends ProfilePage
{
    private StudentGateway $studentGateway;
    private UserGateway $userGateway;
    private SchoolYearGateway $schoolYearGateway;
    private YearGroupGateway $yearGroupGateway;
    private FormGroupGateway $formGroupGateway;
    private HouseGateway $houseGateway;
    private SettingGateway $settingGateway;
    private PersonalDocumentGateway $personalDocumentGateway;
    private CustomFieldHandler $customFieldHandler;
    private $connection2;
    private $guid;
    private $page;

    public function __construct(
        Session $session,
        StudentGateway $studentGateway,
        UserGateway $userGateway,
        SchoolYearGateway $schoolYearGateway,
        YearGroupGateway $yearGroupGateway,
        FormGroupGateway $formGroupGateway,
        HouseGateway $houseGateway,
        SettingGateway $settingGateway,
        PersonalDocumentGateway $personalDocumentGateway,
        CustomFieldHandler $customFieldHandler,
        $connection2,
        $guid,
        $page
    ) {
        parent::__construct($session);
        $this->studentGateway = $studentGateway;
        $this->userGateway = $userGateway;
        $this->schoolYearGateway = $schoolYearGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->formGroupGateway = $formGroupGateway;
        $this->houseGateway = $houseGateway;
        $this->settingGateway = $settingGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
        $this->customFieldHandler = $customFieldHandler;
        $this->connection2 = $connection2;
        $this->guid = $guid;
        $this->page = $page;
    }

    /**
     * Check if the current user has permission to view personal information
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'View Student Profile_full');
    }

    /**
     * Generate HTML output for the personal information page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch student and person data
        $personData = $this->fetchPersonData();
        
        // Guard clause: check if person exists
        if (empty($personData)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        $output = '';

        // Render personal information table
        $output .= $this->renderPersonalInfoTable($personData);

        // Render personal documents section
        $output .= $this->renderPersonalDocuments();

        return $output;
    }

    /**
     * Fetch person data from database
     * 
     * @return array Person data or empty array if not found
     */
    protected function fetchPersonData(): array
    {
        $data = ['gibbonPersonID' => $this->gibbonPersonID];
        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.fields as enrollmentFields 
                FROM gibbonPerson 
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID 
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
        $data['gibbonSchoolYearID'] = $this->gibbonSchoolYearID;
        
        $result = $this->connection2->prepare($sql);
        $result->execute($data);
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }

    /**
     * Render personal information table
     * 
     * @param array $row Person data
     * @return string HTML for personal information table
     */
    protected function renderPersonalInfoTable(array $row): string
    {
        // Fetch related data
        $student = $this->studentGateway->selectActiveStudentByPerson($this->gibbonSchoolYearID, $this->gibbonPersonID, false)->fetch();
        $tutors = $this->formGroupGateway->selectTutorsByFormGroup($student['gibbonFormGroupID'] ?? '')->fetchAll();
        $yearGroup = $this->yearGroupGateway->getByID($student['gibbonYearGroupID'] ?? '', ['name', 'gibbonPersonIDHOY']);
        $headOfYear = $this->userGateway->getByID($yearGroup['gibbonPersonIDHOY'] ?? '', ['title', 'surname', 'preferredName', 'gibbonPersonID']);
        $house = $this->houseGateway->getByID($row['gibbonHouseID'] ?? '', ['name']);

        $table = DataTable::createDetails('overview');

        // Add edit action if user has permission
        if (Access::allows('User Admin', 'Manage Users_view')) {
            $table->addHeaderAction('edit', __('Edit User'))
                ->setURL('/modules/User Admin/user_manage_edit.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->displayLabel();
        }

        // Basic Information section
        $this->addBasicInformationColumns($table);

        // Contact Information section
        $this->addContactInformationColumns($table, $row);

        // School Information section
        $this->addSchoolInformationColumns($table, $student, $tutors, $house, $headOfYear);

        // Custom fields for Student Enrolment
        $this->customFieldHandler->addCustomFieldsToTable($table, 'Student Enrolment', [], $student['fields'] ?? '');

        // Background Information section
        $this->addBackgroundInformationColumns($table);

        // System Access section
        $this->addSystemAccessColumns($table);

        // Miscellaneous section
        $this->addMiscellaneousColumns($table, $row);

        // Custom fields for User
        $this->customFieldHandler->addCustomFieldsToTable($table, 'User', ['student' => 1], $row['fields']);

        return $table->render([$row]);
    }

    /**
     * Add basic information columns to table
     * 
     * @param DataTable $table The table to add columns to
     */
    protected function addBasicInformationColumns(DataTable $table): void
    {
        $col = $table->addColumn('Basic Information');

        $col->addColumn('surname', __('Surname'));
        $col->addColumn('firstName', __('First Name'))->addClass('col-span-2');
        $col->addColumn('preferredName', __('Preferred Name'));
        $col->addColumn('officialName', __('Official Name'));
        $col->addColumn('nameInCharacters', __('Name In Characters'));
        $col->addColumn('gender', __('Gender'))
            ->format(Format::using('genderName', 'gender'));
        $col->addColumn('dob', __('Date of Birth'))->format(Format::using('date', 'dob'));
        $col->addColumn('age', __('Age'))->format(Format::using('age', 'dob'));
    }

    /**
     * Add contact information columns to table
     * 
     * @param DataTable $table The table to add columns to
     * @param array $row Person data
     */
    protected function addContactInformationColumns(DataTable $table, array $row): void
    {
        $col = $table->addColumn('Contact Information', __('Contact Information'));

        for ($i = 1; $i <= 4; $i++) {
            if (empty($row["phone$i"])) continue;
            $col->addColumn("phone$i", __('Phone '.$i))->format(Format::using('phone', ["phone{$i}", "phone{$i}CountryCode", "phone{$i}Type"]));
        }
        $col->addColumn('email', __('Email'))->format(Format::using('link', 'email'));
        $col->addColumn('emailAlternate', __('Alternate Email'))->format(Format::using('link', 'emailAlternate'));
        $col->addColumn('website', __('Website'))->format(Format::using('link', 'website'));
    }

    /**
     * Add school information columns to table
     * 
     * @param DataTable $table The table to add columns to
     * @param array|false $student Student enrollment data
     * @param array $tutors List of tutors
     * @param array $house House data
     * @param array|false $headOfYear Head of year data
     */
    protected function addSchoolInformationColumns(DataTable $table, $student, array $tutors, array $house, $headOfYear): void
    {
        $col = $table->addColumn('School Information', __('School Information'));

        if (!empty($student)) {
            $col->addColumn('yearGroup', __('Year Group'))->format(function ($values) use ($student) {
                return $student['yearGroupName'];
            });
            $col->addColumn('gibbonFormGroupID', __('Form Group'))->format(function ($values) use ($student) {
                return Format::link('./index.php?q=/modules/Form Groups/formGroups_details.php&gibbonFormGroupID='.$student['gibbonFormGroupID'], $student['formGroupName']);
            });
        }
        $col->addColumn('tutors', __('Tutors'))->format(function ($values) use ($tutors) {
            if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';
            return Format::nameList($tutors, 'Staff', false, true);
        });
        $col->addColumn('gibbonHouseID', __('House'))->format(function ($values) use ($house) {
            return !empty($house['name']) ? $house['name'] : '';
        });
        $col->addColumn('studentID', __('Student ID'));
        $col->addColumn('headOfYear', __('Head of Year'))->format(function ($values) use ($headOfYear) {
            return !empty($headOfYear)
                ? Format::nameLinked($headOfYear['gibbonPersonID'], '', $headOfYear['preferredName'], $headOfYear['surname'], 'Staff')
                : '';
        });

        $col->addColumn('lastSchool', __('Last School'));
        $col->addColumn('dateStart', __('Start Date'))->format(Format::using('date', 'dateStart'));
        $col->addColumn('classOf', __('Class Of'))->format(function ($values) {
            if (empty($values['gibbonSchoolYearIDClassOf'])) return Format::small(__('N/A'));
            $schoolYear = $this->schoolYearGateway->getByID($values['gibbonSchoolYearIDClassOf'], ['name']);
            return $schoolYear['name'] ?? '';
        });
        $col->addColumn('nextSchool', __('Next School'));
        $col->addColumn('dateEnd', __('End Date'))->format(Format::using('date', 'dateEnd'));
        $col->addColumn('departureReason', __('Departure Reason'));
    }

    /**
     * Add background information columns to table
     * 
     * @param DataTable $table The table to add columns to
     */
    protected function addBackgroundInformationColumns(DataTable $table): void
    {
        $col = $table->addColumn('Background Information', __('Background Information'));

        $col->addColumn('countryOfBirth', __('Country of Birth'))->translatable();
        $col->addColumn('ethnicity', __('Ethnicity'));
        $col->addColumn('religion', __('Religion'));

        $col->addColumn('languageFirst', __('First Language'))->translatable();
        $col->addColumn('languageSecond', __('Second Language'))->translatable();
        $col->addColumn('languageThird', __('Third Language'))->translatable();
    }

    /**
     * Add system access columns to table
     * 
     * @param DataTable $table The table to add columns to
     */
    protected function addSystemAccessColumns(DataTable $table): void
    {
        $col = $table->addColumn('System Access', __('System Access'));

        $col->addColumn('username', __('Username'));
        $col->addColumn('canLogin', __('Can Login?'))->format(Format::using('yesNo', 'canLogin'));
        $col->addColumn('lastIPAddress', __('Last IP Address'));
    }

    /**
     * Add miscellaneous columns to table
     * 
     * @param DataTable $table The table to add columns to
     * @param array $row Person data
     */
    protected function addMiscellaneousColumns(DataTable $table, array $row): void
    {
        $col = $table->addColumn('Miscellaneous', __('Miscellaneous'));

        $col->addColumn('transport', __('Transport'))->format(function ($values) {
            $output = $values['transport'];
            if (!empty($values['transportNotes'])) {
                $output .= '<br/>'.$values['transportNotes'];
            }
            return $output;
        });
        $col->addColumn('vehicleRegistration', __('Vehicle Registration'));
        $col->addColumn('lockerNumber', __('Locker Number'));

        $privacySetting = $this->settingGateway->getSettingByScope('User Admin', 'privacy');
        if ($privacySetting == 'Y') {
            $col->addColumn('privacy', __('Privacy'))->format(function ($values) {
                if (!empty($values['privacy'])) {
                    return Format::tag(__('Privacy required:').' '.$values['privacy'], 'error');
                } else {
                    return Format::tag(__('Privacy not required or not set.'), 'success');
                }
            });
        }
        $studentAgreementOptions = $this->settingGateway->getSettingByScope('School Admin', 'studentAgreementOptions');
        if (!empty($studentAgreementOptions)) {
            $col->addColumn('studentAgreements', __('Student Agreements:'))->format(function ($values) {
                return __('Agreements Signed:') .' '.$values['studentAgreements'];
            });
        }
    }

    /**
     * Render personal documents section
     * 
     * @return string HTML for personal documents
     */
    protected function renderPersonalDocuments(): string
    {
        // Guard clause: check if user has access to personal documents
        if (!Access::allows('Students', 'Student Personal Document Summary')) {
            return '';
        }

        $params = ['student' => true, 'notEmpty' => true];
        $documents = $this->personalDocumentGateway->selectPersonalDocuments('gibbonPerson', $this->gibbonPersonID, $params)->fetchAll();

        return $this->page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents]);
    }
}
