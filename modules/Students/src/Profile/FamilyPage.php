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
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * FamilyPage
 * 
 * Displays student family information including family details, adult family members,
 * and siblings with their contact information and relationships.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class FamilyPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    private Connection $pdo;
    private CustomFieldHandler $customFieldHandler;
    private \Gibbon\View\View $view;

    public function __construct(
        Session $session,
        Connection $pdo,
        CustomFieldHandler $customFieldHandler,
        \Gibbon\View\View $view
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
        $this->customFieldHandler = $customFieldHandler;
        $this->view = $view;
    }

    /**
     * Check if the current user has permission to view family information
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
        return __('Family');
    }


    /**
     * Generate HTML output for the family page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch family data
        $families = $this->fetchFamilyData();
        
        // Guard clause: check if family data exists
        if (empty($families)) {
            return Format::alert(__('There are no records to display.'), 'empty');
        }

        $output = '';

        // Render each family
        foreach ($families as $family) {
            $output .= $this->renderFamily($family);
        }

        return $output;
    }

    /**
     * Fetch family data from database
     * 
     * @return array Family data or empty array if not found
     */
    protected function fetchFamilyData(): array
    {
        $data = ['gibbonPersonID' => $this->gibbonPersonID];
        $sql = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
        
        return $this->pdo->select($sql, $data)->fetchAll();
    }

    /**
     * Render a single family
     * 
     * @param array $family Family data
     * @return string HTML for family display
     */
    protected function renderFamily(array $family): string
    {
        $output = '';

        // Add edit button if user has permission
        if (Access::allows('User Admin', 'family_manage')) {
            $form = Form::createBlank('buttons');
            $form->addHeaderAction('edit', __('Edit Family'))
                ->setURL('/modules/User Admin/family_manage_edit.php')
                ->addParam('gibbonFamilyID', $family['gibbonFamilyID'])
                ->displayLabel();
            $output .= $form->getOutput();
        } else {
            $output .= '<br/><br/>';
        }

        // Render family information table
        $output .= $this->renderFamilyInfo($family);

        // Render custom fields
        $output .= $this->renderCustomFields($family);

        // Render adult family members
        $output .= $this->renderAdults($family['gibbonFamilyID']);

        // Render siblings
        $output .= $this->renderSiblings($family['gibbonFamilyID']);

        return $output;
    }

    /**
     * Render family information table
     * 
     * @param array $family Family data
     * @return string HTML for family information
     */
    protected function renderFamilyInfo(array $family): string
    {
        $output = "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
        $output .= '<tr>';
        $output .= "<td style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Family Name').'</span><br/>';
        $output .= $family['name'];
        $output .= '</td>';
        $output .= "<td style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Family Status').'</span><br/>';
        $output .= __($family['status']);
        $output .= '</td>';
        $output .= "<td style='width: 34%; vertical-align: top' colspan=2>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Home Languages').'</span><br/>';
        if ($family['languageHomePrimary'] != '') {
            $output .= __($family['languageHomePrimary']).'<br/>';
        }
        if ($family['languageHomeSecondary'] != '') {
            $output .= __($family['languageHomeSecondary']).'<br/>';
        }
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Address Name').'</span><br/>';
        $output .= $family['nameAddress'];
        $output .= '</td>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= '</td>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= '</td>';
        $output .= '</tr>';

        $output .= '<tr>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Home Address').'</span><br/>';
        $output .= $family['homeAddress'];
        $output .= '</td>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Home Address (District)').'</span><br/>';
        $output .= $family['homeAddressDistrict'];
        $output .= '</td>';
        $output .= "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Home Address (Country)').'</span><br/>';
        $output .= __($family['homeAddressCountry']);
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';

        return $output;
    }

    /**
     * Render custom fields for family
     * 
     * @param array $family Family data
     * @return string HTML for custom fields
     */
    protected function renderCustomFields(array $family): string
    {
        $table = DataTable::createDetails('family');
        $this->customFieldHandler->addCustomFieldsToTable($table, 'Family', [], $family['fields'] ?? '');
        return $table->render([['' => '']]);
    }

    /**
     * Render adult family members
     * 
     * @param string $gibbonFamilyID Family ID
     * @return string HTML for adults display
     */
    protected function renderAdults(string $gibbonFamilyID): string
    {
        $adults = $this->fetchAdults($gibbonFamilyID);
        
        $output = '';
        $count = 1;

        foreach ($adults as $adult) {
            $output .= $this->renderAdult($adult, $count);
            $count++;
        }

        return $output;
    }

    /**
     * Fetch adult family members
     * 
     * @param string $gibbonFamilyID Family ID
     * @return array Adult members data
     */
    protected function fetchAdults(string $gibbonFamilyID): array
    {
        $data = ['gibbonFamilyID' => $gibbonFamilyID];
        $sql = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
        
        return $this->pdo->select($sql, $data)->fetchAll();
    }

    /**
     * Render a single adult family member
     * 
     * @param array $adult Adult data
     * @param int $count Adult number
     * @return string HTML for adult display
     */
    protected function renderAdult(array $adult, int $count): string
    {
        $class = '';
        if ($adult['status'] != 'Full') {
            $class = "class='error'";
        }

        $output = '<h4>';
        $output .= __('Adult').' '.$count;
        $output .= '</h4>';
        $output .= "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
        $output .= '<tr>';
        $output .= "<td $class style='width: 33%; vertical-align: top' rowspan=2>";
        $output .= Format::userPhoto($adult['image_240'], 75);
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
        $output .= Format::name($adult['title'], $adult['preferredName'], $adult['surname'], 'Parent');
        if ($adult['status'] != 'Full') {
            $output .= "<span style='font-weight: normal; font-style: italic'> (".__($adult['status']).')</span>';
        }
        $output .= "<div style='font-size: 85%; font-style: italic'>";
        $output .= $this->getRelationship($adult['gibbonPersonID']);
        $output .= '</div>';
        $output .= '</td>';
        $output .= "<td $class style='width: 34%; vertical-align: top' colspan=2>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact Priority').'</span><br/>';
        $output .= $adult['contactPriority'];
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('First Language').'</span><br/>';
        $output .= __($adult['languageFirst']);
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Second Language').'</span><br/>';
        $output .= __($adult['languageSecond']);
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Phone').'</span><br/>';
        $output .= $this->renderPhoneContact($adult);
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact By SMS').'</span><br/>';
        $output .= $this->renderSMSContact($adult);
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan=2>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Email').'</span><br/>';
        $output .= $this->renderEmailContact($adult);
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Profession').'</span><br/>';
        $output .= $adult['profession'];
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Employer').'</span><br/>';
        $output .= $adult['employer'];
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Job Title').'</span><br/>';
        $output .= $adult['jobTitle'];
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '<tr>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Vehicle Registration').'</span><br/>';
        $output .= $adult['vehicleRegistration'];
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= '</td>';
        $output .= "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
        $output .= '</td>';
        $output .= '</tr>';

        // Check to ensure only people with full profile access can view these comments
        $highestAction = Access::get('Students', 'student_view_details');
        if ($adult['comment'] != '' && $highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
            $output .= '<tr>';
            $output .= "<td $class style='width: 33%; vertical-align: top' colspan=3>";
            $output .= "<span style='font-size: 115%; font-weight: bold'>".__('Comment').'</span><br/>';
            $output .= Format::alert($adult['comment'], 'message');
            $output .= '</td>';
            $output .= '</tr>';
        }
        $output .= '</table>';

        return $output;
    }

    /**
     * Get relationship between adult and student
     * 
     * @param string $adultPersonID Adult's person ID
     * @return string Relationship text
     */
    protected function getRelationship(string $adultPersonID): string
    {
        $data = [
            'gibbonPersonID1' => $adultPersonID,
            'gibbonPersonID2' => $this->gibbonPersonID,
            'gibbonFamilyID' => ''
        ];
        
        // Get gibbonFamilyID from the current context
        $familyData = $this->fetchFamilyData();
        if (!empty($familyData)) {
            $data['gibbonFamilyID'] = $familyData[0]['gibbonFamilyID'];
        }
        
        $sql = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID';
        $result = $this->pdo->select($sql, $data);
        
        if ($result->rowCount() == 1) {
            $relationship = $result->fetch();
            return __($relationship['relationship']);
        }
        
        return '<i>'.__('Relationship Unknown').'</i>';
    }

    /**
     * Render phone contact information
     * 
     * @param array $adult Adult data
     * @return string HTML for phone contact
     */
    protected function renderPhoneContact(array $adult): string
    {
        if ($adult['contactCall'] == 'N') {
            return __('Do not contact by phone.');
        }
        
        if ($adult['contactCall'] == 'Y' && ($adult['phone1'] != '' || $adult['phone2'] != '' || $adult['phone3'] != '' || $adult['phone4'] != '')) {
            $output = '';
            for ($i = 1; $i < 5; ++$i) {
                if ($adult['phone'.$i] != '') {
                    if ($adult['phone'.$i.'Type'] != '') {
                        $output .= $adult['phone'.$i.'Type'].':</i> ';
                    }
                    if ($adult['phone'.$i.'CountryCode'] != '') {
                        $output .= '+'.$adult['phone'.$i.'CountryCode'].' ';
                    }
                    $output .= Format::phone($adult['phone'.$i]).'<br/>';
                }
            }
            return $output;
        }
        
        return Format::small(__('N/A'));
    }

    /**
     * Render SMS contact information
     * 
     * @param array $adult Adult data
     * @return string HTML for SMS contact
     */
    protected function renderSMSContact(array $adult): string
    {
        if ($adult['contactSMS'] == 'N') {
            return __('Do not contact by SMS.');
        }
        
        if ($adult['contactSMS'] == 'Y' && ($adult['phone1'] != '' || $adult['phone2'] != '' || $adult['phone3'] != '' || $adult['phone4'] != '')) {
            $output = '';
            for ($i = 1; $i < 5; ++$i) {
                if ($adult['phone'.$i] != '' && $adult['phone'.$i.'Type'] == 'Mobile') {
                    if ($adult['phone'.$i.'Type'] != '') {
                        $output .= $adult['phone'.$i.'Type'].':</i> ';
                    }
                    if ($adult['phone'.$i.'CountryCode'] != '') {
                        $output .= '+'.$adult['phone'.$i.'CountryCode'].' ';
                    }
                    $output .= Format::phone($adult['phone'.$i]).'<br/>';
                }
            }
            return $output ?: Format::small(__('N/A'));
        }
        
        return Format::small(__('N/A'));
    }

    /**
     * Render email contact information
     * 
     * @param array $adult Adult data
     * @return string HTML for email contact
     */
    protected function renderEmailContact(array $adult): string
    {
        if ($adult['contactEmail'] == 'N') {
            return __('Do not contact by email.');
        }
        
        if ($adult['contactEmail'] == 'Y' && ($adult['email'] != '' || $adult['emailAlternate'] != '')) {
            $output = '';
            if ($adult['email'] != '') {
                $email = filter_var(trim($adult['email']), FILTER_SANITIZE_EMAIL);
                $output .= __('Email').": <a href='mailto:".$email."'>".$email.'</a><br/>';
            }
            if ($adult['emailAlternate'] != '') {
                $emailAlt = filter_var(trim($adult['emailAlternate']), FILTER_SANITIZE_EMAIL);
                $output .= __('Email')." 2: <a href='mailto:".$emailAlt."'>".$emailAlt.'</a><br/>';
            }
            return $output.'<br/>';
        }
        
        return Format::small(__('N/A'));
    }

    /**
     * Render siblings
     * 
     * @param string $gibbonFamilyID Family ID
     * @return string HTML for siblings display
     */
    protected function renderSiblings(string $gibbonFamilyID): string
    {
        $siblings = $this->fetchSiblings($gibbonFamilyID);
        
        // Guard clause: no siblings
        if (empty($siblings)) {
            return '';
        }

        $output = '<h4>';
        $output .= __('Siblings');
        $output .= '</h4>';

        $output .= "<table class='smallIntBorder' cellspacing='0' style='width:100%'>";
        $count = 0;
        $columns = 3;
        $highlightClass = '';

        foreach ($siblings as $sibling) {
            if ($count % $columns == 0) {
                $output .= '<tr>';
            }
            $highlightClass = $sibling['status'] != 'Full' ? 'error' : '';
            $output .= "<td style='width:30%; text-align: left; vertical-align: top' class='".$highlightClass."'>";
            $output .= Format::userPhoto($sibling['image_240'], 75);
            $output .= "<div style='padding-top: 5px'><b>";

            $allStudents = '';
            if ($sibling['gibbonStudentEnrolmentID'] == null) {
                $allStudents = 'on';
            }

            $output .= "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$sibling['gibbonPersonID']."&allStudents=".$allStudents."'>".Format::name('', $sibling['preferredName'], $sibling['surname'], 'Student').'</a><br/>';
            $output .= "<span style='font-weight: normal; font-style: italic'>".__('Status').': '.__($sibling['status']).'</span>';
            $output .= '</div>';
            $output .= '</td>';

            if ($count % $columns == ($columns - 1)) {
                $output .= '</tr>';
            }
            $count++;
        }

        for ($i = 0; $i < $columns - ($count % $columns); ++$i) {
            $output .= '<td class="'.$highlightClass.'"></td>';
        }

        if ($count % $columns != 0) {
            $output .= '</tr>';
        }

        $output .= '</table>';

        return $output;
    }

    /**
     * Fetch siblings
     * 
     * @param string $gibbonFamilyID Family ID
     * @return array Siblings data
     */
    protected function fetchSiblings(string $gibbonFamilyID): array
    {
        $data = [
            'gibbonFamilyID' => $gibbonFamilyID,
            'gibbonPersonID' => $this->gibbonPersonID,
            'gibbonSchoolYearID' => $this->gibbonSchoolYearID
        ];
        $sql = 'SELECT gibbonPerson.gibbonPersonID, image_240, preferredName, surname, status, gibbonStudentEnrolmentID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
        
        return $this->pdo->select($sql, $data)->fetchAll();
    }
}
