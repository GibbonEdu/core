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

use Gibbon\Domain\Behaviour\BehaviourFollowUpGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Behaviour\BehaviourGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('Manage Behaviour Records'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $type = $_GET['type'] ?? '';

        $form = Form::createSearch();
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID',__('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->selected($gibbonPersonID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID',__('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID',__('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

        $arrTypes = array(
            'Negative' => __('Negative'),
            'Positive' => __('Positive'),
            'Observation' => __('Observation')
        );

        $row = $form->addRow();
            $row->addLabel('type',__('Type'));
            $row->addSelect('type')->fromArray($arrTypes)->selected($type)->placeholder();


        $row = $form->addRow()->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        $behaviourGateway = $container->get(BehaviourGateway::class);

        // CRITERIA
        $criteria = $behaviourGateway->newQueryCriteria(true)
            ->sortBy('timestamp', 'DESC')
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->filterBy('type', $type)
            ->fromPOST();

        
        if ($highestAction == 'Manage Behaviour Records_all') {
            $records = $behaviourGateway->queryBehaviourBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));
        } else if ($highestAction == 'Manage Behaviour Records_my') {
            $records = $behaviourGateway->queryBehaviourBySchoolYear($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
        } else {
            return;
        }

        $behaviourFollowUpGateway = $container->get(BehaviourFollowUpGateway::class);
         // Join follow-up based on behaviour ID
         $behaviourIDs = $records->getColumn('gibbonBehaviourID');
         $followUpData = $behaviourFollowUpGateway->selectFollowUpsByBehaviorID($behaviourIDs)->fetchGrouped();
         $records->joinColumn('gibbonBehaviourID', 'followUps', $followUpData);

        // DATA TABLE
        $table = DataTable::createPaginated('behaviourManage', $criteria);
        $table->setTitle(__('Behaviour Records'));

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Behaviour/behaviour_manage_add.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('type', $type)
            ->displayLabel();

        $table->addHeaderAction('addMultiple', __('Add Multiple'))
            ->setURL('/modules/Behaviour/behaviour_manage_addMulti.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('type', $type)
            ->displayLabel();

        $policyLink = $settingGateway->getSettingByScope('Behaviour', 'policyLink');
        if (!empty($policyLink)) {
            $table->addHeaderAction('policy', __('View Behaviour Policy'))
                ->setExternalURL($policyLink)
                ->displayLabel();
        }

        $table->addExpandableColumn('comment')
            ->format(function($behaviour) {
                $output = '';
                if (!empty($behaviour['comment'])) {
                    $output .= Format::bold(__('Incident')).'<br/>';
                    $output .= nl2br($behaviour['comment']).'<br/>';
                }

                if (!empty($behaviour['followUps'])) {
                    foreach ($behaviour['followUps'] as $followUp) { 
                        $output .= '<br/>'.Format::bold(__('Follow Up By ').$followUp['firstName']._(' ').$followUp['surname']).'<br/>';
                        $output .= nl2br($followUp['followUp']).'<br/>';
                    }
                }
                return $output;
            });

        $table->addColumn('student', __('Student'))
            ->description(__('Form Group'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->context('primary')
            ->format(function($person) use ($session) {
                $url = $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Behaviour&search=&allStudents=&sort=surname,preferredName';
                return '<b>'.Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true)).'</b>'
                      .'<br/><small><i>'.$person['formGroup'].'</i></small>';
            });

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
                    return icon('solid', 'add', 'size-6 fill-current text-green-600');
                } elseif ($behaviour['type'] == 'Observation') {
                    return icon('solid','view', 'size-6 fill-current text-blue-600');
                } 
            });

        if ($enableDescriptors == 'Y') {
            $table->addColumn('descriptor', __('Descriptor'))->context('primary');
        }

        if ($enableLevels == 'Y') {
            $table->addColumn('level', __('Level'))->width('15%');
        }

        $table->addColumn('teacher', __('Teacher'))
            ->context('secondary')
            ->sortable(['preferredNameCreator', 'surnameCreator'])
            ->width('25%')
            ->format(function($person) {
                return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
            });

        $table->addActionColumn()
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('type', $type)
            ->addParam('gibbonBehaviourID')
            ->format(function ($person, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Behaviour/behaviour_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Behaviour/behaviour_manage_delete.php');
            });

        echo $table->render($records);
    }
}