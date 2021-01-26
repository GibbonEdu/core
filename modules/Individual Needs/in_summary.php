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
use Gibbon\Domain\IndividualNeeds\INGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_summary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Individual Needs Summary'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    $gibbonINDescriptorID = null;
    if (isset($_GET['gibbonINDescriptorID'])) {
        $gibbonINDescriptorID = $_GET['gibbonINDescriptorID'];
    }
    $gibbonAlertLevelID = null;
    if (isset($_GET['gibbonAlertLevelID'])) {
        $gibbonAlertLevelID = $_GET['gibbonAlertLevelID'];
    }
    $gibbonRollGroupID = null;
    if (isset($_GET['gibbonRollGroupID'])) {
        $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
    }
    $gibbonYearGroupID = null;
    if (isset($_GET['gibbonYearGroupID'])) {
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
    }

    echo '<h3>';
    echo __('Filter');
    echo '</h3>';

    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth standardForm');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('q', '/modules/Individual Needs/in_summary.php');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    //SELECT FROM ARRAY
    $sql = "SELECT gibbonINDescriptorID as value, name FROM gibbonINDescriptor ORDER BY sequenceNumber";
    $row = $form->addRow();
    	$row->addLabel('gibbonINDescriptorID', __('Descriptor'));
        $row->addSelect('gibbonINDescriptorID')->fromQuery($pdo, $sql)->selected($gibbonINDescriptorID)->placeholder();

    $sql = "SELECT gibbonAlertLevelID as value, name FROM gibbonAlertLevel ORDER BY sequenceNumber";
    $row = $form->addRow();
        $row->addLabel('gibbonAlertLevelID', __('Alert Level'));
        $row->addSelect('gibbonAlertLevelID')->fromQuery($pdo, $sql)->selected($gibbonAlertLevelID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelectRollGroup('gibbonRollGroupID', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonRollGroupID)->placeholder();
    
    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID)->placeholder();
    
    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));
        
    echo $form->getOutput();

    echo '<h3>';
    echo __('Students With Records');
    echo '</h3>';
    echo '<p>';
    echo __('Students only show up in this list if they have an Individual Needs record with descriptors set. If a student does not show up here, check in Individual Needs Records.');
    echo '</p>';

    $individualNeedsGateway = $container->get(INGateway::class);

    $criteria = $individualNeedsGateway->newQueryCriteria(true)
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('descriptor', $gibbonINDescriptorID)
        ->filterBy('alert', $gibbonAlertLevelID)
        ->filterBy('rollGroup', $gibbonRollGroupID)
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->fromPOST();

    $individualNeeds = $individualNeedsGateway->queryINBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    // DATA TABLE
    $table = DataTable::createPaginated('inSummary', $criteria);

    $table->modifyRows(function($student, $row) {
        if ($student['status'] != 'Full') $row->addClass('error');
        if (!($student['dateStart'] == '' || $student['dateStart'] <= date('Y-m-d'))) $row->addClass('error');
        if (!($student['dateEnd'] == '' || $student['dateEnd'] >= date('Y-m-d'))) $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'alert:003'    => __('Alert Level').': '.__('Low'),
        'alert:002' => __('Alert Level').': '.__('Medium'),
        'alert:001'   => __('Alert Level').': '.__('High'),
    ]);

    // COLUMNS
    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'Student', true, false, ['subpage' => 'Individual Needs']]));
    $table->addColumn('yearGroup', __('Year Group'));
    $table->addColumn('rollGroup', __('Roll Group'));

    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->addParam('gibbonINDescriptorID', $gibbonINDescriptorID)
        ->addParam('gibbonAlertLevelID', $gibbonAlertLevelID)
        ->addParam('gibbonRollGroupID', $gibbonRollGroupID)
        ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
        ->addParam('source', 'summary')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit Individual Needs Details'))
                    ->setURL('/modules/Individual Needs/in_edit.php');
        });

    echo $table->render($individualNeeds);
}
