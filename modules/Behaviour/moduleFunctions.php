<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

function getBehaviourRecord(ContainerInterface $container, $gibbonPersonID)
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

            $behaviourRecords = $behaviourGateway->queryBehaviourRecordsByPerson($criteria, $schoolYear['gibbonSchoolYearID'], $gibbonPersonID);

            $table = DataTable::createPaginated('behaviour'.$schoolYear['gibbonSchoolYearID'], $criteria);
            $table->setTitle($schoolYear['name']);

            if ($schoolYear['gibbonSchoolYearID'] == $session->get('gibbonSchoolYearID')) {
                if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php')) {
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
                        ->displayLabel()
                        ->prepend('&nbsp|&nbsp');
                }
            }

            $table->addMetaData('hidePagination', true);

            $table->addExpandableColumn('comment')
                ->format(function($beahviour) {
                    $output = '';
                    if (!empty($beahviour['comment'])) {
                        $output .= '<strong>'.__('Incident').'</strong><br/>';
                        $output .= nl2br($beahviour['comment']).'<br/>';
                    }
                    if (!empty($beahviour['followup'])) {
                        $output .= '<br/><strong>'.__('Follow Up').'</strong><br/>';
                        $output .= nl2br($beahviour['followup']).'<br/>';
                    }
                    return $output;
                });

            $table->addColumn('date', __('Date'))
                ->context('primary')
                ->format(function($beahviour) {
                    if (substr($beahviour['timestamp'], 0, 10) > $beahviour['date']) {
                        return __('Updated:').' '.Format::date($beahviour['timestamp']).'<br/>'
                            . __('Incident:').' '.Format::date($beahviour['date']).'<br/>';
                    } else {
                        return Format::date($beahviour['timestamp']);
                    }
                });

            $table->addColumn('type', __('Type'))
                ->context('secondary')
                ->width('5%')
                ->format(function($beahviour) use ($session) {
                    if ($beahviour['type'] == 'Negative') {
                        return "<img src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/> ";
                    } elseif ($beahviour['type'] == 'Positive') {
                        return "<img src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/> ";
                    }
                });

            if ($enableDescriptors == 'Y') {
                $table->addColumn('descriptor', __('Descriptor'))->context('primary');
            }

            if ($enableLevels == 'Y') {
                $table->addColumn('level', __('Level'))->width('15%');
            }

            $table->addColumn('teacher', __('Teacher'))
                ->context('primary')
                ->sortable(['preferredNameCreator', 'surnameCreator'])
                ->width('25%')
                ->format(function($person) {
                    return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
                });

            if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php') && $schoolYear['gibbonSchoolYearID'] == $session->get('gibbonSchoolYearID')) {
                $highestAction = getHighestGroupedAction($guid, '/modules/Behaviour/behaviour_manage.php', $connection2);

                $table->addActionColumn()
                    ->addParam('gibbonPersonID', $gibbonPersonID)
                    ->addParam('gibbonFormGroupID', '')
                    ->addParam('gibbonYearGroupID', '')
                    ->addParam('type', '')
                    ->addParam('gibbonBehaviourID')
                    ->format(function ($person, $actions) use ($session, $highestAction) {
                        if ($highestAction == 'Manage Behaviour Records_all'
                        || ($highestAction == 'Manage Behaviour Records_my' && $person['gibbonPersonIDCreator'] == $session->get('gibbonPersonID'))) {
                            $actions->addAction('edit', __('Edit'))
                                ->setURL('/modules/Behaviour/behaviour_manage_edit.php');
                        }
                    });
            }

            $output .= $table->render($behaviourRecords);
        }
    }
    return $output;
}
