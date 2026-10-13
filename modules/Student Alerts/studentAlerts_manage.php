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
use Gibbon\Support\Facades\Access;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Module\StudentAlerts\Tables\ManageAlerts;

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_manage.php')) {
	// Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
    $action = Access::get('Student Alerts', 'studentAlerts_manage');
    if (empty($action)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    } 

    $page->breadcrumbs->add(__('Manage Student Alerts'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $gibbonYearGroupIDHOY = '';

    $yearGroup = $container->get(YearGroupGateway::class)->getYearGroupByPerson($session->get('gibbonPersonID'));

    if ($action->allows('Manage Student Alerts_headOfYear') && !empty($yearGroup) && empty($gibbonPersonID) && empty($gibbonFormGroupID) && empty($gibbonYearGroupID)) {
        $gibbonYearGroupIDHOY = $yearGroup['gibbonYearGroupID'];
        $gibbonYearGroupID = $yearGroup['gibbonYearGroupID'];
    }

    // SEARCH
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

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();
    

    // ALERTS TABLE
    $table = $container->get(ManageAlerts::class)->create(
        $session->get('gibbonSchoolYearID'),
        $gibbonPersonID,
        $gibbonFormGroupID,
        $gibbonYearGroupID,
        $gibbonYearGroupIDHOY
    );
    $table->setTitle(__('Alerts'));

    echo $table->getOutput();
}
