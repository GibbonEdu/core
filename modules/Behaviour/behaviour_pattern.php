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
use Gibbon\Domain\Students\StudentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Find Behaviour Patterns'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    $descriptor = isset($_GET['descriptor'])? $_GET['descriptor'] : '';
    $level = isset($_GET['level'])? $_GET['level'] : '';
    $fromDate = isset($_GET['fromDate'])? $_GET['fromDate'] : '';
    $gibbonRollGroupID = isset($_GET['gibbonRollGroupID'])? $_GET['gibbonRollGroupID'] : '';
    $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';
    $minimumCount = isset($_GET['minimumCount'])? $_GET['minimumCount'] : 1;

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', "/modules/Behaviour/behaviour_pattern.php");

    if ($enableDescriptors == 'Y') {
        $negativeDescriptors = getSettingByScope($connection2, 'Behaviour', 'negativeDescriptors');
        $negativeDescriptors = array_map('trim', explode(',', $negativeDescriptors));
        
        $row = $form->addRow();
            $row->addLabel('descriptor', __('Descriptor'));
            $row->addSelect('descriptor')->fromArray($negativeDescriptors)->placeholder()->selected($descriptor);
    }

    if ($enableLevels == 'Y') {
        $optionsLevels = getSettingByScope($connection2, 'Behaviour', 'levels');
        if ($optionsLevels != '') {
            $optionsLevels = explode(',', $optionsLevels);
        }
        $row = $form->addRow();
            $row->addLabel('level', __('Level'));
            $row->addSelect('level')->fromArray($optionsLevels)->placeholder()->selected($level);
    }

    $row = $form->addRow();
        $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('fromDate')->setValue($fromDate);

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addLabel('gibbonYearGroupID',__('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addLabel('minimumCount', __('Minimum Count'));
        $row->addSelect('minimumCount')->fromArray(array(0,1,2,3,4,5,10,25,50))->selected($minimumCount);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    echo '<h3>';
    echo __('Behaviour Records');
    echo '</h3>';
    echo '<p>';
    echo __('The students listed below match the criteria above, for negative behaviour records in the current school year. The count is updated according to the criteria above.');
    echo '</p>';

    $behaviourGateway = $container->get(BehaviourGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    // CRITERIA
    $criteria = $behaviourGateway->newQueryCriteria(true)
        ->sortBy('count', 'DESC')
        ->sortBy('rollGroup')
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('descriptor', $descriptor)
        ->filterBy('level', $level)
        ->filterBy('fromDate', Format::dateConvert($fromDate))
        ->filterBy('rollGroup', $gibbonRollGroupID)
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->filterBy('minimumCount', $minimumCount)
        ->fromPOST();

    $records = $behaviourGateway->queryBehaviourPatternsBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    // DATA TABLE
    $table = DataTable::createPaginated('behaviourPatterns', $criteria);

    $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

    // COLUMNS
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) use ($guid) {
            $url = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&subpage=Behaviour&gibbonPersonID='.$person['gibbonPersonID'].'&search=&allStudents=&sort=surname,preferredName';
            return Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true))
                . '<br/><small><i>'.Format::userStatusInfo($person).'</i></small>';
        });
    $table->addColumn('count', __('Negative Count'))->description(__('(Current Year Only)'));
    $table->addColumn('yearGroup', __('Year Group'));
    $table->addColumn('rollGroup', __('Roll Group'));

    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->addParam('search', '')
        ->format(function ($row, $actions) {
            $actions->addAction('view', __('View Details'))
                ->setURL('/modules/Behaviour/behaviour_view_details.php');
        });

    echo $table->render($records);
}
