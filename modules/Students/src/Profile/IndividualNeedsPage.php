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
use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * IndividualNeedsPage
 * 
 * Displays individual needs/SEN information for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class IndividualNeedsPage extends ProfilePage
{
    private Connection $pdo;
    private INGateway $inGateway;
    private CustomFieldHandler $customFieldHandler;

    public function __construct(
        Session $session,
        Connection $pdo,
        INGateway $inGateway,
        CustomFieldHandler $customFieldHandler
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
        $this->inGateway = $inGateway;
        $this->customFieldHandler = $customFieldHandler;
    }

    /**
     * Check if the current user has permission to view individual needs
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Individual Needs', 'in_view');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Individual Needs');
    }


    /**
     * Generate HTML output for the individual needs page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('You have not specified one or more required parameters.'));
        }

        $output = '';

        // Edit link button
        if (Access::allows('Individual Needs', 'in_edit')) {
            $form = Form::createBlank('buttons');
            $form->addHeaderAction('edit', __('Edit Individual Needs Record'))
                ->setURL('/modules/Individual Needs/in_edit.php')
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->displayLabel();
            $output .= $form->getOutput();
        }

        // Include module functions for status table
        include './modules/Individual Needs/moduleFunctions.php';

        // Display status table
        $statusTable = printINStatusTable($this->pdo, $this->session->get('guid'), $this->gibbonPersonID, 'disabled');
        if ($statusTable == false) {
            $output .= Format::alert(__('Your request failed due to a database error.'));
        } else {
            $output .= $statusTable;
        }

        // Display Educational Assistants
        $output .= $this->renderEducationalAssistants();

        // Display Individual Education Plan
        $output .= $this->renderIndividualEducationPlan();

        return $output;
    }

    /**
     * Render Educational Assistants section
     * 
     * @return string HTML for educational assistants
     */
    protected function renderEducationalAssistants(): string
    {
        $result = $this->inGateway->selectEducationalAssistantsByStudent(
            $this->gibbonPersonID,
            $this->gibbonSchoolYearID
        );
        
        if ($result->rowCount() == 0) {
            return '';
        }

        $output = '<h3>' . __('Educational Assistants') . '</h3>';
        $output .= '<ul>';
        
        while ($row = $result->fetch()) {
            $output .= '<li>' . htmlPrep(Format::name('', $row['preferredName'], $row['surname'], 'Student', false));
            if ($row['email'] != '') {
                $row['email'] = filter_var(trim($row['email']), FILTER_SANITIZE_EMAIL);
                $output .= htmlPrep(' <' . $row['email'] . '>');
            }
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        
        return $output;
    }

    /**
     * Render Individual Education Plan section
     * 
     * @return string HTML for individual education plan
     */
    protected function renderIndividualEducationPlan(): string
    {
        $output = '<h3>' . __('Individual Education Plan') . '</h3>';

        $rowIN = $this->inGateway->getINStudentByPersonID($this->gibbonPersonID);

        if (empty($rowIN)) {
            $output .= '<div class="error">' . __('There are no records to display.') . '</div>';
            return $output;
        }

        // Targets
        $output .= "<div style='font-weight: bold'>" . __('Targets') . '</div>';
        $output .= '<p>' . $rowIN['targets'] . '</p>';

        // Teaching Strategies
        $output .= "<div style='font-weight: bold; margin-top: 30px'>" . __('Teaching Strategies') . '</div>';
        $output .= '<p>' . $rowIN['strategies'] . '</p>';

        // Notes & Review
        $output .= "<div style='font-weight: bold; margin-top: 30px'>" . __('Notes & Review') . '</div>';
        $output .= '<p>' . $rowIN['notes'] . '</p>';

        // Custom fields
        if (!empty($rowIN['fields'])) {
            $table = DataTable::createDetails('inFields');
            $this->customFieldHandler->addCustomFieldsToTable($table, 'Individual Needs', ['student' => 1], $rowIN['fields']);
            $output .= $table->render([$rowIN]);
        }

        return $output;
    }
}
