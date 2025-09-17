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

namespace Gibbon\UI\Components;

use Gibbon\Http\Url;
use Gibbon\View\Component;
use Gibbon\Support\Facades\Access;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\Markbook\MarkbookEntryGateway;
use Gibbon\Domain\IndividualNeeds\INPersonDescriptorGateway;

/**
 * Alert class to calculate and get Student Alerts
 *
 * @version  v30
 * @since    v30
 */

class Alert
{
    protected $db;
    protected $session;
    protected $settingGateway;
    protected $userGateway;
    protected $medicalGateway;
    protected $inPersonDescriptorGateway;
    protected $markbookEntryGateway;
    protected $behaviourGateway;
    protected $alertGateway;

    protected $alertTypes;
    protected $alertLevels = ['High' => 001, 'Medium' => 002, 'Low' => 003];
    
    protected $days = 60;

    /**
     * Construct an Alerts UI object and cache alert types.
     *
     * @param Session $session
     * @param SettingGateway $settingGateway
     * @param UserGateway $userGateway
     * @param MedicalGateway $medicalGateway
     * @param INPersonDescriptorGateway $inPersonDescriptorGateway
     * @param MarkbookEntryGateway $markbookEntryGateway
     * @param BehaviourGateway $behaviourGateway
     * @param AlertGateway $alertGateway
     */
    public function __construct(
        Session $session,
        SettingGateway $settingGateway,
        UserGateway $userGateway,
        MedicalGateway $medicalGateway,
        INPersonDescriptorGateway $inPersonDescriptorGateway,
        MarkbookEntryGateway $markbookEntryGateway,
        BehaviourGateway $behaviourGateway,
        AlertGateway $alertGateway
    ) {
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->userGateway = $userGateway;
        $this->medicalGateway = $medicalGateway;
        $this->inPersonDescriptorGateway = $inPersonDescriptorGateway;
        $this->markbookEntryGateway = $markbookEntryGateway;
        $this->behaviourGateway = $behaviourGateway;
        $this->alertGateway = $alertGateway;

        $this->alertTypes = $this->alertGateway->selectAllAlertTypes()->fetchGroupedUnique();
    }

    /**
     * Renders an HTML display of student alerts as UI tags.
     *
     * @param string $gibbonPersonID
     * @param string $privacy
     * @param string $divExtras
     * @param bool $div
     * @param bool $large
     * @param string $target
     * @return void
     */
    public function getAlertBar(string $gibbonPersonID, $divExtras = '', $div = true, $large = false, $target = '_self') 
    {
        $action = Access::get('Students', 'student_view_details');
        if (!$action->allowsAny('View Student Profile_full', 'View Student Profile_fullNoNotes', 'View Student Profile_fullEditAllNotes')) return '';

        $output = '';
        $alerts = $this->getAlertsByStudent($gibbonPersonID);
        
        foreach ($alerts as $alert) {
            $details = $this->getAlertTextAndLink($gibbonPersonID, $alert['type'], $alert['level'] ?? $alert['privacy']);

            $output .= Component::render(Alert::class, [
                'color'   => $alert['levelColor'] ?? $alert['color'] ?? '#939090',
                'colorBG' => $alert['levelColorBG'] ?? $alert['colorBG'] ?? '#dddddd',
                'large'   => $large,
                'target'  => $target == '_blank' ? '_blank' : '_self',
            ] + $details + $alert);
        }

        if ($div == true) {
            $output = "<div class='w-20 lg:w-24 h-6 -mt-6 text-left py-1 px-0 mx-auto'><div {$divExtras}>{$output}</div></div>";
        }
        
        return $output;
    }
    
    /**
     * Gets an array of cached alert type details by name.
     *
     * @param string $type
     * @return array
     */
    public function getAlertType(string $type) : array
    {
        return $this->alertTypes[$type] ?? [];
    }

    /**
     * Gets whether an alert type is active based on the field in gibbonAlertType.
     *
     * @param string $type
     * @return bool
     */
    public function isAlertTypeActive(string $type)
    {
        return !empty($this->alertTypes[$type]) && $this->alertTypes[$type]['active'] == 'Y';
    }

    /**
     * Get all existing alerts from the database, including manual and automatic.
     * Only return the highest level of each alert type.
     *
     * @param string $gibbonPersonID
     * @return array
     */
    public function getAlertsByStudent(string $gibbonPersonID) : array
    {
        $allAlerts = $this->alertGateway->selectActiveAlertsByStudent($this->session->get('gibbonSchoolYearID'), $gibbonPersonID);
        $alerts = [];
        
        foreach ($allAlerts as $alert) {
            if (empty($alerts[$alert['type']])) {
                $alerts[$alert['type']] = $alert;
                continue;
            }

            $existing = $alerts[$alert['type']];

            $isHigherLevel = $alert['alertLevel'] > $existing['alertLevel'];
            $isHigherContext = $alert['context'] == 'Manual' && $existing['context'] != 'Manual';
            $isMoreRecent = $alert['timestampCreated'] > $existing['timestampCreated'];

            if ($isHigherLevel || $isHigherContext || $isMoreRecent) {
                $alerts[$alert['type']] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Calculate and return all automatic alerts for a student by ID.
     *
     * @param string $gibbonPersonID
     * @return array
     */
    public function calculateAlerts(string $gibbonPersonID) : array
    {
        $alerts = [];

        $this->calculateIndividualNeedsAlerts($alerts, $gibbonPersonID);
        $this->calculateAcademicAlerts($alerts, $gibbonPersonID);
        $this->calculateBehaviourAlerts($alerts, $gibbonPersonID);
        $this->calculateMedicalAlerts($alerts, $gibbonPersonID);
        $this->calculatePrivacyAlerts($alerts, $gibbonPersonID);

        return $alerts;
    }

    /**
     * Calculates and updates automatic alerts for a student by ID.
     *
     * @param string $gibbonPersonID
     * @return void
     */
    public function recalculateAlerts(string $gibbonPersonID)
    {
        $existingAlerts = $this->alertGateway->selectAutomaticAlertsByStudent($this->session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetchGroupedUnique();
        $newAlerts = $this->calculateAlerts($gibbonPersonID);

        foreach ($this->alertTypes as $type => $alertType) {
            if (!empty($existingAlerts[$type]) && !empty($newAlerts[$type])) {
                // Update existing alert
                $this->alertGateway->update($existingAlerts[$type]['gibbonAlertID'], $newAlerts[$type]);
            } elseif (!empty($existingAlerts[$type]) && empty($newAlerts[$type])) {
                // Delete existing alert
                $this->alertGateway->delete($existingAlerts[$type]['gibbonAlertID']);
            } elseif (empty($existingAlerts[$type]) && !empty($newAlerts[$type])) {
                // Insert new alert
                $this->alertGateway->insert($newAlerts[$type] + [
                    'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'),
                    'gibbonPersonID'     => $gibbonPersonID,
                    'context'            => 'Automatic',
                ]);
            }
        }
    }

    /**
     * Calculate alerts based on the alert level of any IN descriptors.
     *
     * @param string $gibbonPersonID
     * @return void
     */
    protected function calculateIndividualNeedsAlerts(array &$alerts, string $gibbonPersonID)
    {
        if (!$this->isAlertTypeActive('Individual Needs')) return;

        $resultAlert = $this->inPersonDescriptorGateway->selectINDescriptorAlertLevelsByPerson($gibbonPersonID);

        if ($alert = $resultAlert->fetch()) {
            $alertType = $this->getAlertType('Individual Needs');

            $alerts[$alertType['name']] = [
                'gibbonAlertTypeID'  => $alertType['gibbonAlertTypeID'],
                'gibbonAlertLevelID' => $this->getAlertLevelID($alert['level']),
                'context'            => 'Automatic',
                'status'             => 'Approved',
                'type'               => $alertType['name'],
                'level'              => $alert['level'] ?? null,
            ];
        }
    }

    /**
     * Calculate alerts based on the number of markbook columns flagged for 
     * academic or effort concern, in a given time period.
     *
     * @param string $gibbonPersonID
     * @return void
     */
    protected function calculateAcademicAlerts(array &$alerts, string $gibbonPersonID)
    {
        if (!$this->isAlertTypeActive('Academic')) return;

        $resultAlert = $this->markbookEntryGateway->selectMarkbookConcernsByStudentAndDate($this->session->get('gibbonSchoolYearID'), $gibbonPersonID, $this->days);

        $alertType = $this->getAlertType('Academic');
        $alertLevel = $this->getAlertLevelByThreshold($alertType['name'], $resultAlert->rowCount());

        if (!empty($alertLevel)) {
            $alerts[$alertType['name']] = [
                'gibbonAlertTypeID'  => $alertType['gibbonAlertTypeID'],
                'gibbonAlertLevelID' => $alertLevel['id'] ?? null,
                'context'            => 'Automatic',
                'status'             => 'Approved',
                'type'               => $alertType['name'],
                'level'              => $alertLevel['level'] ?? null,
            ];
        }
    }

    /**
     * Calculate alerts based on the number of negative behaviour events for 
     * a student in a given time period.
     *
     * @param string $gibbonPersonID
     * @return void
     */
    protected function calculateBehaviourAlerts(array &$alerts, string $gibbonPersonID)
    {
        if (!$this->isAlertTypeActive('Behaviour')) return;

        $resultAlert = $this->behaviourGateway->selectNegativeBehaviourByStudentAndDate($gibbonPersonID, $this->days);

        $alertType = $this->getAlertType('Behaviour');
        $alertLevel = $this->getAlertLevelByThreshold($alertType['name'], $resultAlert->rowCount());

        if (!empty($alertLevel)) {
            $alerts[$alertType['name']] = [
                'gibbonAlertTypeID'  => $alertType['gibbonAlertTypeID'],
                'gibbonAlertLevelID' => $alertLevel['id'] ?? null,
                'context'            => 'Automatic',
                'status'             => 'Approved',
                'type'               => $alertType['name'],
                'level'              => $alertLevel['level'] ?? null,
            ];
        }
    }

    /**
     * Calculate alerts based on the alert levels of any medical conditions.
     *
     * @param string $gibbonPersonID
     * @return void
     */
    protected function calculateMedicalAlerts(array &$alerts, string $gibbonPersonID)
    {
        if (!$this->isAlertTypeActive('Medical')) return;

        if ($alert = $this->medicalGateway->getHighestMedicalRisk($gibbonPersonID)) {
            $alertType = $this->getAlertType('Medical');
            $alerts[$alertType['name']] = [
                'gibbonAlertTypeID'  => $alertType['gibbonAlertTypeID'],
                'gibbonAlertLevelID' => $this->getAlertLevelID($alert['level']),
                'context'            => 'Automatic',
                'status'             => 'Approved',
                'type'               => $alertType['name'],
                'level'              => $alert['level'],
            ];
        }
    }

    /**
     * Calculate alerts based on privacy options for this student, if the 
     * privacy setting is turned on.
     *
     * @param string $gibbonPersonID
     * @return void
     */
    protected function calculatePrivacyAlerts(array &$alerts, string $gibbonPersonID)
    {
        if (!$this->isAlertTypeActive('Privacy')) return;

        $privacySetting = $this->settingGateway->getSettingByScope('User Admin', 'privacy');
        if ($privacySetting != 'Y') return;
           
        $person = $this->userGateway->getByID($gibbonPersonID, ['privacy']);
        if (!empty($person['privacy']) && $alertType = $this->getAlertType('Privacy')) {
            $alerts[$alertType['name']] = [
                'gibbonAlertTypeID'  => $alertType['gibbonAlertTypeID'],
                'gibbonAlertLevelID' => null,
                'context'            => 'Automatic',
                'status'             => 'Approved',
                'type'               => $alertType['name'],
                'level'              => $person['privacy'],
            ];
        }
    }

    /**
     * Get the UI tooltip text and link for a given alert type and level
     *
     * @param string $gibbonPersonID
     * @param string $type
     * @param string $level
     * @return array
     */
    private function getAlertTextAndLink(string $gibbonPersonID, string $type, ?string $level = '') : array
    {
        $link = Url::fromModuleRoute('Students', 'student_view_details');

        switch ($type) {
            case 'Individual Needs':
                return [
                    'title' => __('Individual Needs alerts are set, up to a maximum alert level of {level}.', ['level' => $level]),
                    'link' => $link->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Individual Needs']),
                ];
            case 'Academic':
                return [
                    'title' => __('Student has a {level} alert for academic concern over the past 60 days.', ['level' => $level]).' '.$this->getThresholdText($type, $level),
                    'link' => $link->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Markbook', 'filter' => $this->session->get('gibbonSchoolYearID')]),
                ];
            case 'Behaviour':
                return [
                    'title' => __('Student has a {level} alert for behaviour over the past 60 days.', ['level' => $level]).' '.$this->getThresholdText($type, $level),
                    'link' => $link->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Behaviour']),
                ];
            case 'Medical':
                return [
                    'title' => __('Medical alerts are set, up to a maximum of {level}', ['level' => $level]),
                    'link' => $link->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Medical']),
                ];
            case 'Privacy':
                return [
                    'title' => __('Privacy is required: {level}', ['level' => $level]),
                    'link' => $link->withQueryParams(['gibbonPersonID' => $gibbonPersonID]),
                ];
            default:
                return [
                    'title' => __($type).': '.__($level),
                    'link' => $link->withQueryParams(['gibbonPersonID' => $gibbonPersonID]),
                ];
        }
    }

    /**
     * Get the threshold description string for a given alert level.
     *
     * @param string $type
     * @param string $level
     * @return string
     */
    private function getThresholdText(string $type, ?string $level) : string
    {
        $alertType = $this->getAlertType($type);

        if (empty($level) || empty($alertType['thresholdHigh'])) return '';

        switch ($level) {
            case 'High':
                return __('This alert level occurs when there are more than {count} events recorded for a student.', ['count' => $alertType['thresholdHigh']]);
            case 'Medium':
                return __('This alert level occurs when there are between {count} and {count2} events recorded for a student.', ['count' => $alertType['thresholdMed'], 'count2' => $alertType['thresholdHigh'] - 1]);
            case 'Low':
                return __('This alert level occurs when there are between {count} and {count2} events recorded for a student.', ['count' => $alertType['thresholdLow'], 'count2' => $alertType['thresholdMed'] - 1]);
        }

        return '';
    }

    /**
     * Get the gibbonAlertLevelID for a given level by name.
     *
     * @param string $level
     * @return string
     */
    private function getAlertLevelID(?string $level) : ?string
    {
        return $this->alertLevels[$level] ?? null;
    }

    /**
     * Determine the alert level based on low, medium, high thresholds.
     *
     * @param string $type
     * @param int $count
     * @return array
     */
    private function getAlertLevelByThreshold(string $type, int $count) : array
    {
        $alertType = $this->getAlertType($type);

        if (empty($alertType['thresholdHigh']) || empty($alertType['thresholdMed']) || empty($alertType['thresholdLow'])) return [];

        if ($count >= $alertType['thresholdHigh']) {
            return ['id' => 001, 'level' => 'High'];
        } elseif ($count >= $alertType['thresholdMed']) {
            return ['id' => 002, 'level' => 'Medium'];
        } elseif ($count >= $alertType['thresholdLow']) {
            return ['id' => 003, 'level' => 'Low'];
        }

        return [];
    }   
}
