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

use Gibbon\Domain\System\SettingGateway;
use Psr\Container\ContainerInterface;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\Domain\Students\StudentGateway;

function getBehaviourRecord(ContainerInterface $container, $gibbonPersonID, $gibbonPersonIDCreator = null)
{
    global $session;

    $output = '';

    $guid = $container->get('config')->getConfig('guid');
    $connection2 = $container->get('db')->getConnection();

    $settingGateway = $container->get(SettingGateway::class);

    $enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
    $enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

    $behaviourGateway = $container->get(BehaviourGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $schoolYears = $studentGateway->selectAllStudentEnrolmentsByPerson($gibbonPersonID)->fetchAll();

    $highestViewAction = getHighestGroupedAction($guid, '/modules/Behaviour/behaviour_view.php', $connection2);
    $viewingSelf = $session->get('gibbonPersonID') == $gibbonPersonID;

    if ($highestViewAction == 'View Behaviour Records_myself' && !$viewingSelf) {
        echo Format::alert(__('You do not have access to this action.'));
        return;
    }

    if (empty($schoolYears)) {
        $output .= "<div class='error'>";
        $output .= __('There are no records to display.');
        $output .= '</div>';
    } else {

        foreach ($schoolYears as $schoolYear) {

            // CRITERIA
            $criteria = $behaviourGateway->newQueryCriteria()
                ->sortBy('timestamp', 'DESC')
                ->fromPOST($schoolYear['gibbonSchoolYearID']);

            $behaviourRecords = $behaviourGateway->queryBehaviourRecordsByPerson($criteria, $schoolYear['gibbonSchoolYearID'], $gibbonPersonID, $gibbonPersonIDCreator);
            
            $table = DataTable::createPaginated('behaviour'.$schoolYear['gibbonSchoolYearID'], $criteria);
            $table->setTitle($schoolYear['name']);

            $canManageBehaviour = isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php');

            if (!$viewingSelf && $schoolYear['gibbonSchoolYearID'] == $session->get('gibbonSchoolYearID')) {
                if ($canManageBehaviour) {
                    $table->addHeaderAction('add', __('Add'))
                        ->setURL('/modules/Behaviour/behaviour_manage_add.php')
                        ->addParam('gibbonPersonID', $gibbonPersonID)
                        ->addParam('gibbonFormGroupID', '')
                        ->addParam('gibbonYearGroupID', '')
                        ->addParam('type', '')
                        ->displayLabel();
                }

                $policyLink = $settingGateway->getSettingByScope('Behaviour', 'policyLink');
                if (!empty($policyLink)) {
                    $table->addHeaderAction('policy', __('View Behaviour Policy'))
                        ->setExternalURL($policyLink)
                        ->setIcon('document')
                        ->displayLabel();
                }
            }

            $table->addMetaData('hidePagination', true);

            if ($highestViewAction == 'View Behaviour Records_all') {
                $table->addExpandableColumn('comment')
                    ->format(function($behaviour) {
                        $output = '';
                        if (!empty($behaviour['comment'])) {
                            $output .= '<strong>'.__('Incident').'</strong><br/>';
                            $output .= nl2br($behaviour['comment']).'<br/>';
                        }
                        
                        if (!empty($behaviour['followup'])) {
                            $output .= '<br/><strong>'.__('Follow Up').'</strong><br/>';
                            $output .= nl2br($behaviour['followup']).'<br/>';
                        }
                        return $output;
                    });
            }

            $table->addColumn('date', __('Date'))
                ->context('primary')
                ->format(function($behaviour) {
                    if (substr($behaviour['timestamp'], 0, 10) > $behaviour['date']) {
                        return __('Updated:').' '.Format::date($behaviour['timestamp']).'<br/>'
                            . __('Incident:').' '.Format::date($behaviour['date']).'<br/>';
                    } else {
                        return Format::date($behaviour['timestamp']);
                    }
                });

            $table->addColumn('type', __('Type'))
                ->context('secondary')
                ->width('5%')
                ->format(function($behaviour) {
                    if ($behaviour['type'] == 'Negative') {
                        return icon('solid', 'cross', 'size-6 fill-current text-red-700');
                    } elseif ($behaviour['type'] == 'Positive') {
                        return icon('solid', 'check', 'size-6 fill-current text-green-600');
                    }
                });

            if ($enableDescriptors == 'Y') {
                $table->addColumn('descriptor', __('Descriptor'))->context('primary');
            }

            if ($highestViewAction == 'View Behaviour Records_all' && $enableLevels == 'Y') {
                $table->addColumn('level', __('Level'))->width('15%');
            }

            $table->addColumn('teacher', __('Teacher'))
                ->context('primary')
                ->sortable(['preferredNameCreator', 'surnameCreator'])
                ->width('25%')
                ->format(function($person) {
                    return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
                });

            if ($canManageBehaviour && $schoolYear['gibbonSchoolYearID'] == $session->get('gibbonSchoolYearID')) {
                $highestManageAction = getHighestGroupedAction($guid, '/modules/Behaviour/behaviour_manage.php', $connection2);

                $table->addActionColumn()
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('gibbonFormGroupID', '')
                    ->addParam('gibbonYearGroupID', '')
                    ->addParam('type', '')
                    ->addParam('gibbonBehaviourID')
                    ->format(function ($person, $actions) use ($session, $highestManageAction) {
                        if ($highestManageAction == 'Manage Behaviour Records_all'
                        || ($highestManageAction == 'Manage Behaviour Records_my' && $person['gibbonPersonIDCreator'] == $session->get('gibbonPersonID'))) {
                            $actions->addAction('edit', __('Edit'))
                                ->setURL('/modules/Behaviour/behaviour_manage_edit.php');
                        } elseif ($highestManageAction == 'Manage Behaviour Records_my') {
                            $actions->addAction('view', __('View'))
                                ->setURL('/modules/Behaviour/behaviour_manage_edit.php');
                        }
                    });
            }

            $output .= $table->render($behaviourRecords);
        }
    }
    return $output;
}
