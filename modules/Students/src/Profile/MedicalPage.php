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
use Gibbon\Database\Connection;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * MedicalPage
 * 
 * Displays student medical information including medical conditions, medications,
 * allergies, and emergency medical protocols.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class MedicalPage extends ProfilePage
{
    private MedicalGateway $medicalGateway;
    private CustomFieldHandler $customFieldHandler;
    private Connection $pdo;

    public function __construct(
        Session $session,
        MedicalGateway $medicalGateway,
        CustomFieldHandler $customFieldHandler,
        Connection $pdo
    ) {
        parent::__construct($session);
        $this->medicalGateway = $medicalGateway;
        $this->customFieldHandler = $customFieldHandler;
        $this->pdo = $pdo;
    }

    /**
     * Check if the current user has permission to view medical information
     * 
     * @return bool True if user has access, false otherwise
     */
    /**
     * Check if the current user has permission to view medical information
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'View Student Profile_full');
    }


    /**
     * Generate HTML output for the medical page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch medical data
        $medical = $this->fetchMedicalData();
        $conditions = $this->fetchMedicalConditions($medical['gibbonPersonMedicalID'] ?? null);

        $output = '';

        // Display medical alert
        $output .= $this->renderMedicalAlert();

        // Display medical details table
        $output .= $this->renderMedicalDetails($medical, $conditions);

        // Display medical conditions
        $output .= $this->renderMedicalConditions($conditions);

        return $output;
    }

    /**
     * Fetch medical data from database
     * 
     * @return array Medical data or empty array if not found
     */
    protected function fetchMedicalData(): array
    {
        $medical = $this->medicalGateway->getMedicalFormByPerson($this->gibbonPersonID);
        return $medical ?: [];
    }

    /**
     * Fetch medical conditions from database
     * 
     * @param string|null $gibbonPersonMedicalID Medical form ID
     * @return array Medical conditions data
     */
    protected function fetchMedicalConditions(?string $gibbonPersonMedicalID): array
    {
        if (empty($gibbonPersonMedicalID)) {
            return [];
        }

        return $this->medicalGateway->selectMedicalConditionsByID($gibbonPersonMedicalID)->fetchAll();
    }

    /**
     * Render medical alert banner
     * 
     * @return string HTML for medical alert
     */
    protected function renderMedicalAlert(): string
    {
        $alert = $this->medicalGateway->getHighestMedicalRisk($this->gibbonPersonID);
        
        // Guard clause: no alert
        if (empty($alert)) {
            return '';
        }
        
        $output = "<div class='error' style='background-color: #".$alert['colorBG'].
                  '; border: 1px solid #'.$alert['color'].'; color: #'.$alert['color']."'>";
        $output .= '<b>'.__('This student has one or more {level} risk medical conditions.', 
                           ['level' => __($alert['name'])]).'</b>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render medical details table
     * 
     * @param array $medical Medical data
     * @param array $conditions Medical conditions
     * @return string HTML for medical details
     */
    protected function renderMedicalDetails(array $medical, array $conditions): string
    {
        $table = DataTable::createDetails('medical');

        // Add header actions if user has permission
        if (Access::allows('Students', 'Manage Medical Forms')) {
            if (empty($medical)) {
                $table->addHeaderAction('add', __('Add Medical Form'))
                    ->setURL('/modules/Students/medicalForm_manage_add.php')
                    ->addParam('gibbonPersonID', $this->gibbonPersonID)
                    ->displayLabel();
            } else {
                $table->addHeaderAction('edit', __('Edit Medical Form'))
                    ->setURL('/modules/Students/medicalForm_manage_edit.php')
                    ->addParam('gibbonPersonID', $this->gibbonPersonID)
                    ->addParam('gibbonPersonMedicalID', $medical['gibbonPersonMedicalID'])
                    ->displayLabel();
            }
        }

        $col = $table->addColumn('General Information');

        $col->addColumn('longTermMedication', __('Long Term Medication'))
            ->format(Format::using('yesno', 'longTermMedication'));

        $col->addColumn('longTermMedicationDetails', __('Details'))
            ->addClass('col-span-2')
            ->format(function ($medical) {
                return !empty($medical['longTermMedication'])
                    ? $medical['longTermMedicationDetails']
                    : Format::small(__('Unknown'));
            });

        $this->customFieldHandler->addCustomFieldsToTable($table, 'Medical Form', [], $medical['fields'] ?? '', $table);

        $col->addColumn('medicalConditions', __('Medical Conditions?'))
            ->addClass('col-span-3')
            ->format(function ($medical) use ($conditions) {
                return count($conditions) > 0
                    ? __('Yes').'. '.__('Details below.')
                    : __('No');
            });

        if (!empty($medical['comment'])) {
            $col->addColumn('comment', __('Comment'))->addClass('col-span-3');
        }

        // Merge custom fields into medical data
        if (!empty($medical['fields']) && is_string($medical['fields'])) {
            $fields = json_decode($medical['fields'], true);
            $medical = is_array($fields) ? array_merge($medical, $fields) : $medical;
        }

        return $table->render([$medical]);
    }

    /**
     * Render medical conditions tables
     * 
     * @param array $conditions Medical conditions
     * @return string HTML for medical conditions
     */
    protected function renderMedicalConditions(array $conditions): string
    {
        // Guard clause: no conditions
        if (empty($conditions)) {
            return '';
        }

        $output = '';

        foreach ($conditions as $condition) {
            $table = DataTable::createDetails('medicalConditions');
            $table->setTitle(__($condition['name'])." <span style='color: ".$condition['alertColor']."'>(".__($condition['risk']).' '.__('Risk').')</span>');
            $table->setDescription($condition['description']);
            $table->addMetaData('gridClass', 'grid-cols-1 md:grid-cols-2');

            $table->addColumn('triggers', __('Triggers'));
            $table->addColumn('reaction', __('Reaction'));
            $table->addColumn('response', __('Response'));
            $table->addColumn('medication', __('Medication'));
            $table->addColumn('lastEpisode', __('Last Episode Date'))
                ->format(Format::using('date', 'lastEpisode'));
            $table->addColumn('lastEpisodeTreatment', __('Last Episode Treatment'));
            $table->addColumn('comment', __('Comments'))->addClass('col-span-2');

            if (!empty($condition['attachment'])) {
                $table->addColumn('attachment', __('Attachment'))
                    ->addClass('col-span-2')
                    ->format(function ($condition) {
                        return Format::link('./'.$condition['attachment'], __('View Attachment'), ['target' => '_blank']);
                    });
            }

            $output .= $table->render([$condition]);
        }

        return $output;
    }
}
