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
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Alerts\AlertGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\Domain\Markbook\MarkbookEntryGateway;
use Gibbon\Domain\IndividualNeeds\INPersonDescriptorGateway;
use Gibbon\View\Component;

/**
 * Alert class to calculate and get Alerts for Students
 *
 * @version  v30
 * @since    v30
 */

class Alert
{
    protected $db;
    protected $session;
    protected $settingGateway;
    protected $alertLevelGateway;
    protected $medicalGateway;
    protected $inPersonDescriptorGateway;
    protected $markbookEntryGateway;
    protected $behaviourGateway;
    protected $alertGateway;
    protected $alerts = [];

    public function __construct(
        Connection $db,
        Session $session,
        SettingGateway $settingGateway,
        AlertLevelGateway $alertLevelGateway,
        MedicalGateway $medicalGateway,
        INPersonDescriptorGateway $inPersonDescriptorGateway,
        MarkbookEntryGateway $markbookEntryGateway,
        BehaviourGateway $behaviourGateway,
        AlertGateway $alertGateway
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->alertLevelGateway = $alertLevelGateway;
        $this->medicalGateway = $medicalGateway;
        $this->inPersonDescriptorGateway = $inPersonDescriptorGateway;
        $this->markbookEntryGateway = $markbookEntryGateway;
        $this->behaviourGateway = $behaviourGateway;
        $this->alertGateway = $alertGateway;
    }

    public function getAlertBar($gibbonPersonID, $privacy = '', $divExtras = '', $div = true, $large = false, $target = "_self") 
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
       
        $output = '';
        $target = ($target == "_blank") ? "_blank" : "_self";

        $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
        if ($highestAction == 'View Student Profile_full' or $highestAction == 'View Student Profile_fullNoNotes' or $highestAction == 'View Student Profile_fullEditAllNotes') {
            
            // Calculate All ALERTS
            $this->calculateAlerts($gibbonPersonID, $privacy);

            if (empty($this->alerts)) {
                return '';
            }

            foreach ($this->alerts as $alert) {
                $output .= Component::render(Alert::class, $alert + [
                    'large' => $large,
                    'target' => $target,
                ]);
            }

            if ($div == true) {
                $output = "<div {$divExtras} class='w-20 lg:w-24 h-6 text-left py-1 px-0 mx-auto'>{$output}</div>";
            }
        }
        
        return $output;
    }


    public function calculateAlerts($gibbonPersonID, $privacy = '')
    {
        // Individual Needs
        $this->calculateIndividualNeedsAlerts($gibbonPersonID);

        // Academic
        $this->calculateAcademicAlerts($gibbonPersonID);

        // Behaviour
        $this->calculateBehaviourAlerts($gibbonPersonID);

        // Medical
        $this->calculateMedicalAlerts($gibbonPersonID);

        // Privacy
        $this->calculatePrivacyAlerts($gibbonPersonID, $privacy);

        // Custom
        $this->calculateCustomAlerts($gibbonPersonID);

        return $this;
    }


    // Individual Needs Alert
    protected function calculateIndividualNeedsAlerts($gibbonPersonID)
    {
        $resultAlert = $this->inPersonDescriptorGateway->selectINPersonDescriptorsandAlertLevelsByPersonID($gibbonPersonID);

        if ($alert = $resultAlert->fetch()) {
            $title = $resultAlert->rowCount() == 1 ? $resultAlert->rowCount() . ' ' . sprintf(__('Individual Needs alert is set, with an alert level of %1$s.'), $alert['name']) : $resultAlert->rowCount() . ' ' . sprintf(__('Individual Needs alerts are set, up to a maximum alert level of %1$s.'), $alert['name']);

            $this->alerts[] = [
                'highestLevel'    => __($alert['name']),
                'color'   => $alert['color'],
                'colorBG' => $alert['colorBG'],
                'tag'             => __('IN'),
                'title'           => $title,
                'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                    ->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Individual Needs']),
            ];
        }
    }

    // Academic Alert
    protected function calculateAcademicAlerts($gibbonPersonID)
    {
        $resultAlert = $this->markbookEntryGateway->selectCompletedMarkbookByStudent($gibbonPersonID, $this->session->get('gibbonSchoolYearID'));
        $resultAlertCount = $resultAlert->rowCount();

        $thresholds = $this->getAlertThresholds('academic');
        $alertData = $this->determineAlertLevelAndThresholdText($resultAlertCount, $thresholds);

        if (!empty($alertData)) {
            $gibbonAlertLevelID = $alertData['level'] ?? '';
            $alertThresholdText = $alertData['text'] ?? '';
            
            if ($alert = $this->alertLevelGateway->getByID($gibbonAlertLevelID)) {
                $this->alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'color'   => $alert['color'],
                    'colorBG' => $alert['colorBG'],
                    'tag'             => __('A'),
                    'title'           => sprintf(__('Student has a %1$s alert for academic concern over the past 60 days.'), __($alert['name'])) . ' ' . $alertThresholdText,
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                                            ->withQueryParams([
                                                'gibbonPersonID' => $gibbonPersonID,
                                                'subpage' => 'Markbook',
                                                'filter' => $this->session->get('gibbonSchoolYearID'),
                                            ]),
                ];
            }
        }
    }

    // Behaviour Alert
    protected function calculateBehaviourAlerts($gibbonPersonID)
    {
        $resultAlert = $this->behaviourGateway->selectNegativeBehavioursByStudent($gibbonPersonID);
        $resultAlertCount = $resultAlert->rowCount();

        $thresholds = $this->getAlertThresholds('behaviour');
        $alertData = $this->determineAlertLevelAndThresholdText($resultAlertCount, $thresholds);

        if (!empty($alertData)) {
            $gibbonAlertLevelID = $alertData['level'] ?? '';
            $alertThresholdText = $alertData['text'] ?? '';

            if ($alert = $this->alertLevelGateway->getByID($gibbonAlertLevelID)) {
                $this->alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'color'   => $alert['color'],
                    'colorBG' => $alert['colorBG'],
                    'tag'             => __('B'),
                    'title'           => sprintf(__('Student has a %1$s alert for behaviour over the past 60 days.'), __($alert['name'])) . ' ' . $alertThresholdText,
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                                            ->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Behaviour']),
                ];
            }
        }
    }

    // Medical Alert
    protected function calculateMedicalAlerts($gibbonPersonID)
    {
        if ($alert = $this->medicalGateway->getHighestMedicalRisk($gibbonPersonID)) {
            $this->alerts[] = [
                'highestLevel'    => __($alert['name']),
                'color'   => $alert['color'],
                'colorBG' => $alert['colorBG'],
                'tag'             => __('M'),
                'title'           => sprintf(__('Medical alerts are set, up to a maximum of %1$s'), $alert['name']),
                'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                    ->withQueryParams(['gibbonPersonID' => $gibbonPersonID, 'subpage' => 'Medical']),
            ];
        }
    }

    // Privacy Alert
    protected function calculatePrivacyAlerts($gibbonPersonID, $privacy)
    {
        $privacySetting = $this->settingGateway->getSettingByScope('User Admin', 'privacy');

        if ($privacySetting == 'Y' and $privacy != '') {
            if ($alert = $this->alertLevelGateway->getByID(AlertLevelGateway::LEVEL_HIGH)) {
                $this->alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'color'   => $alert['color'],
                    'colorBG' => $alert['colorBG'],
                    'tag'             => __('P'),
                    'title'           => sprintf(__('Privacy is required: %1$s'), $privacy),
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                                            ->withQueryParam('gibbonPersonID', $gibbonPersonID),
                ];
            }
        }
    }

    // Custom Alert
    protected function calculateCustomAlerts($gibbonPersonID)
    {
        $customTypes = $this->alertGateway->selectCustomAlertTypes()->fetchAll();

        foreach ($customTypes as $type) {
            if ($alert = $this->alertGateway->getHighestCustomAlert($gibbonPersonID, $this->session->get('gibbonSchoolYearID'), $type['name'])) {
                $this->alerts[] = [
                    'highestLevel'    => __($alert['name']),
                    'color'   => $alert['color'],
                    'colorBG' => $alert['colorBG'],
                    'tag'             => $alert['tag'],
                    'title'           => $alert['description'],
                    'link'            => Url::fromModuleRoute('Students', 'student_view_details')
                                            ->withQueryParams(['gibbonPersonID' => $gibbonPersonID]),
                ];
            }
        }
        
    }

    private function getAlertThresholds($type)
    {
        return [
            'low' => $this->settingGateway->getSettingByScope('Students', "{$type}AlertLowThreshold"),
            'medium' => $this->settingGateway->getSettingByScope('Students', "{$type}AlertMediumThreshold"),
            'high' => $this->settingGateway->getSettingByScope('Students', "{$type}AlertHighThreshold"),
        ];
    }

    private function determineAlertLevelAndThresholdText($count, $thresholds)
    {
        if ($count >= $thresholds['high']) {
            return [
                'level' => 001,
                'text'  => sprintf(__('This alert level occurs when there are more than %1$s events recorded for a student.'), $thresholds['high']),
            ];
        } elseif ($count >= $thresholds['medium']) {
            return [
                'level' => 002,
                'text'  => sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $thresholds['medium'], $thresholds['high'] - 1),
            ];
        } elseif ($count >= $thresholds['low']) {
            return [
                'level' => 003,
                'text'  => sprintf(__('This alert level occurs when there are between %1$s and %2$s events recorded for a student.'), $thresholds['low'], $thresholds['medium'] - 1),
            ];
        }

        return null;
    }   
}
