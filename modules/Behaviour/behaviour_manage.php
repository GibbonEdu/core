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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Behaviour\BehaviourGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs->add(__('Manage Behaviour Records'));

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
        $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : '';
        $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';
        $type = isset($_GET['type'])? $_GET['type'] : '';

        $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('q', "/modules/Behaviour/behaviour_manage.php");

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID',__('Student'));
            $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonPersonID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonRollGroupID',__('Roll Group'));
            $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID',__('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

        $arrTypes = array(
            'Positive' => __('Positive'),
            'Negative' => __('Negative')
        );
                
        $row = $form->addRow();
            $row->addLabel('type',__('Type'));
            $row->addSelect('type')->fromArray($arrTypes)->selected($type)->placeholder();


        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

        echo $form->getOutput();

        $behaviourGateway = $container->get(BehaviourGateway::class);

        // CRITERIA
        $criteria = $behaviourGateway->newQueryCriteria(true)
            ->sortBy('timestamp', 'DESC')
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('rollGroup', $gibbonRollGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->filterBy('type', $type)
            ->fromPOST();

        
        if ($highestAction == 'Manage Behaviour Records_all') {
            $records = $behaviourGateway->queryBehaviourBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);
        } else if ($highestAction == 'Manage Behaviour Records_my') {
            $records = $behaviourGateway->queryBehaviourBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID']);
        } else {
            return;
        }

        // DATA TABLE
        $table = DataTable::createPaginated('behaviourManage', $criteria);
        $table->setTitle(__('Behaviour Records'));

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Behaviour/behaviour_manage_add.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonRollGroupID', $gibbonRollGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('type', $type)
            ->displayLabel()
            ->append('&nbsp|&nbsp');

        $table->addHeaderAction('addMultiple', __('Add Multiple'))
            ->setURL('/modules/Behaviour/behaviour_manage_addMulti.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonRollGroupID', $gibbonRollGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('type', $type)
            ->displayLabel();

        $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
        if (!empty($policyLink)) {
            $table->addHeaderAction('policy', __('View Behaviour Policy'))
                ->setExternalURL($policyLink)
                ->displayLabel()
                ->prepend('&nbsp|&nbsp');
        }

        $table->addExpandableColumn('comment')
            ->format(function($beahviour) {
                $output = '';
                if (!empty($beahviour['comment'])) {
                    $output .= '<strong>'.__('Incident').'</strong><br/>';
                    $output .= nl2brr($beahviour['comment']).'<br/>';
                }
                if (!empty($beahviour['followup'])) {
                    $output .= '<br/><strong>'.__('Follow Up').'</strong><br/>';
                    $output .= nl2brr($beahviour['followup']).'<br/>';
                }
                return $output;
            });

        $table->addColumn('student', __('Student'))
            ->description(__('Roll Group'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->width('25%')
            ->format(function($person) use ($guid) {
                $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Behaviour&search=&allStudents=&sort=surname,preferredName';
                return '<b>'.Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true)).'</b>'
                      .'<br/><small><i>'.$person['rollGroup'].'</i></small>';
            });

        $table->addColumn('date', __('Date'))
            ->format(function($beahviour) {
                if (substr($beahviour['timestamp'], 0, 10) > $beahviour['date']) {
                    return __('Updated:').' '.Format::date($beahviour['timestamp']).'<br/>'
                         . __('Incident:').' '.Format::date($beahviour['date']).'<br/>';
                } else {
                    return Format::date($beahviour['timestamp']);
                }
            });
            
        $table->addColumn('type', __('Type'))
            ->width('5%')
            ->format(function($beahviour) use ($guid) {
                if ($beahviour['type'] == 'Negative') {
                    return "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                } elseif ($beahviour['type'] == 'Positive') {
                    return "<img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                }
            });

        if ($enableDescriptors == 'Y') {
            $table->addColumn('descriptor', __('Descriptor'));
        }

        if ($enableLevels == 'Y') {
            $table->addColumn('level', __('Level'))->width('15%');
        }

        $table->addColumn('teacher', __('Teacher'))
            ->sortable(['preferredNameCreator', 'surnameCreator'])
            ->width('25%')
            ->format(function($person) {
                return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
            });

        $table->addActionColumn()
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonRollGroupID', $gibbonRollGroupID)
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
