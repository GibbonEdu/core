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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

$gibbonLibraryItemEventID = trim($_GET['gibbonLibraryItemEventID']) ?? '';
$gibbonLibraryItemID = trim($_GET['gibbonLibraryItemID']) ?? '';

$page->breadcrumbs
    ->add(__('Lending & Activity Log'), 'library_lending.php')
    ->add(__('View Item'), 'library_lending_item.php', ['gibbonLibraryItemID' => $gibbonLibraryItemID])
    ->add(__('Edit Item'));
    
$name = $_GET['name'] ?? '';
$gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$status = $_GET['status'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // check if school year specified
    if (empty($gibbonLibraryItemEventID) or empty($gibbonLibraryItemID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'gibbonLibraryItemEventID' => $gibbonLibraryItemEventID);
            $sql = 'SELECT gibbonLibraryItemEvent.*, gibbonLibraryItem.name AS name, gibbonLibraryItem.id, gibbonPersonID, surname, preferredName
                FROM gibbonLibraryItem
                    JOIN gibbonLibraryItemEvent ON (gibbonLibraryItem.gibbonLibraryItemID=gibbonLibraryItemEvent.gibbonLibraryItemID)
                    JOIN gibbonPerson ON (gibbonLibraryItemEvent.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID)
                WHERE gibbonLibraryItemEvent.gibbonLibraryItemID=:gibbonLibraryItemID
                    AND gibbonLibraryItemEvent.gibbonLibraryItemEventID=:gibbonLibraryItemEventID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/library_lending_item_editProcess.php?gibbonLibraryItemEventID=$gibbonLibraryItemEventID&gibbonLibraryItemID=$gibbonLibraryItemID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            
            if (!empty($name) or !empty($gibbonLibraryTypeID) or !empty($gibbonSpaceID) or !empty($status)) {
                $params = [
                    "gibbonLibraryItemEventID" => $gibbonLibraryItemEventID,
                    "gibbonLibraryItemID" => $gibbonLibraryItemID,
                    "name" => $name,
                    "gibbonLibraryTypeID" => $gibbonLibraryTypeID,
                    "gibbonSpaceID" => $gibbonSpaceID,
                    "status" => $status
                ];
                $form->addHeaderAction('back', __('Back'))
                    ->setURL('/modules/Library/library_lending_item.php')
                    ->addParams($params);
            }

            $form->addRow()->addHeading('Item Details', __('Item Details'));

            $row = $form->addRow();
                $row->addLabel('id', __('ID'));
                $row->addTextField('id')->setValue($values['id'])->readonly()->required();

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->setValue($values['name'])->readonly()->required();

            $row = $form->addRow();
                $row->addLabel('statusCurrent', __('Current Status'));
                $row->addTextField('statusCurrent')->setValue(__($values['status']))->readonly()->required();

            $form->addRow()->addHeading('This Event', __('This Event'));

            $statuses = array(
                'On Loan' => __('On Loan'),
                'Reserved' => __('Reserved'),
                'Decommissioned' => __('Decommissioned'),
                'Lost' => __('Lost'),
                'Repair' => __('Repair')
            );
            $row = $form->addRow();
                $row->addLabel('status', __('New Status'));
                $row->addSelect('status')->fromArray($statuses)->required()->selected($values['status'])->placeholder();

            $form->addHiddenValue('gibbonPersonIDStatusResponsible', $values['gibbonPersonIDStatusResponsible']);
            $row = $form->addRow();
                $row->addLabel('gibbonPersonIDStatusResponsiblename', __('Responsible User'));
                $row->addTextField('gibbonPersonIDStatusResponsiblename')->setValue(Format::name('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student', true))->readonly()->required();

            $row = $form->addRow();
                $row->addLabel('returnExpected', __('Expected Return Date'));
                $row->addDate('returnExpected')->setValue(Format::date($values['returnExpected']))->required();


            $row = $form->addRow()->addHeading('On Return', __('On Return'));

            $actions = array(
                'Reserve' => __('Reserve'),
                'Decommission' => __('Decommission'),
                'Repair' => __('Repair')
            );
            $row = $form->addRow();
                $row->addLabel('returnAction', __('Action'))->description(__('What to do when item is next returned.'));
                $row->addSelect('returnAction')->fromArray($actions)->selected($values['returnAction'])->placeholder();

            //USER SELECT
            $people = array();

            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => date('Y-m-d'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonFormGroup.name AS formGroupName
                FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE status='Full'
                    AND (dateStart IS NULL OR dateStart<=:date)
                    AND (dateEnd IS NULL  OR dateEnd>=:date)
                    AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY name, surname, preferredName";
            $result = $pdo->executeQuery($data, $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('Enrolable Students').'--'] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = $item['formGroupName'].' - '.Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')';
                    return $group;
                }, array());
            }

            $sql = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
            $result = $pdo->executeQuery(array(), $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('All Users').'--'] = array_reduce($result->fetchAll(), function($group, $item) {
                    $expected = ($item['status'] == 'Expected')? '('.__('Expected').')' : '';
                    $group[$item['gibbonPersonID']] = Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')'.$expected;
                    return $group;
                }, array());
            }

            $row = $form->addRow();
                $row->addLabel('gibbonPersonIDReturnAction', __('Responsible User'))->description(__('Who will be responsible for the future status?'));
                $row->addSelect('gibbonPersonIDReturnAction')->fromArray($people)->placeholder();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
