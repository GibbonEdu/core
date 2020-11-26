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
use Gibbon\Services\Format;

$gibbonLibraryItemID = trim($_GET['gibbonLibraryItemID']) ?? '';

$page->breadcrumbs
    ->add(__('Lending & Activity Log'), 'library_lending.php')
    ->add(__('View Item'), 'library_lending_item.php', ['gibbonLibraryItemID' => $gibbonLibraryItemID])
    ->add(__('Sign Out'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item_signOut.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    if (empty($gibbonLibraryItemID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $values = $result->fetch();

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            if ($values['returnAction'] != '') {
                if ($values['gibbonPersonIDReturnAction'] != '') {
                    
                        $dataPerson = array('gibbonPersonID' => $values['gibbonPersonIDReturnAction']);
                        $sqlPerson = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                        $resultPerson = $connection2->prepare($sqlPerson);
                        $resultPerson->execute($dataPerson);

                    if ($resultPerson->rowCount() == 1) {
                        $rowPerson = $resultPerson->fetch();
                        $person = Format::name('', htmlPrep($rowPerson['preferredName']), htmlPrep($rowPerson['surname']), 'Student');
                    }
                }

                echo "<div class='warning'>";
                if ($values['returnAction'] == 'Make Available') {
                    echo __('This item has been marked to be <u>made available</u> for loan on return.');
                }
                if ($values['returnAction'] == 'Reserve' and $values['gibbonPersonIDReturnAction'] != '') {
                    echo __("This item has been marked to be <u>reserved</u> for <u>$person</u> on return.");
                }
                if ($values['returnAction'] == 'Decommission' and $values['gibbonPersonIDReturnAction'] != '') {
                    echo __("This item has been marked to be <u>decommissioned</u> by <u>$person</u> on return.");
                }
                if ($values['returnAction'] == 'Repair' and $values['gibbonPersonIDReturnAction'] != '') {
                    echo __("This item has been marked to be <u>repaired</u> by <u>$person</u> on return.");
                }
                echo ' '.__('You can change this below if you wish.');
                echo '</div>';
            }

            if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_lending_item.php&name='.$_GET['name']."&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryTypeID=".$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status']."'>".__('Back').'</a>';
                echo '</div>';
            }

            $form = Form::create('libraryLendingSignout', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/library_lending_item_signoutProcess.php?name=".$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status']);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonLibraryItemID', $gibbonLibraryItemID);
            $form->addHiddenValue('statusCurrent', $values['status']);

            $form->addRow()->addHeading(__('Item Details'));

            $row = $form->addRow();
                $row->addLabel('idLabel', __('ID'));
                $row->addTextField('idLabel')->setValue($values['id'])->readonly()->required();

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->setValue($values['name'])->readonly()->required();

            $row = $form->addRow();
                $row->addLabel('statusCurrentText', __('Current Status'));
                $row->addTextField('statusCurrentText')->setValue(__($values['status']))->readonly()->required();

            $form->addRow()->addHeading(__('This Event'));

            $statuses = array(
                'On Loan' => __('On Loan'),
                'Reserved' => __('Reserved'),
                'Decommissioned' => __('Decommissioned'),
                'Lost' => __('Lost'),
                'Repair' => __('Repair')
            );
            $row = $form->addRow();
                $row->addLabel('status', __('New Status'));
                $row->addSelect('status')->fromArray($statuses)->required()->selected('On Loan')->placeholder();

            $people = array();

            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, username, gibbonRollGroup.name AS rollGroupName
                FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE status='Full'
                    AND (dateStart IS NULL OR dateStart<=:date)
                    AND (dateEnd IS NULL  OR dateEnd>=:date)
                    AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY name, surname, preferredName";
            $result = $pdo->executeQuery($data, $sql);

            if ($result->rowCount() > 0) {
                $people['--'.__('Students By Roll Group').'--'] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = $item['rollGroupName'].' - '.Format::name('', htmlPrep($item['preferredName']), htmlPrep($item['surname']), 'Student', true).' ('.$item['username'].')';
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
                $row->addLabel('gibbonPersonIDStatusResponsible', __('Responsible User'))->description(__('Who is responsible for this new status?'));
                $row->addSelect('gibbonPersonIDStatusResponsible')->fromArray($people)->placeholder()->required();

            $loanLength = getSettingByScope($connection2, 'Library', 'defaultLoanLength');
            $loanLength = (is_numeric($loanLength) == false or $loanLength < 0) ? 7 : $loanLength ;
            $row = $form->addRow();
                $row->addLabel('returnExpected', __('Expected Return Date'))->description(sprintf(__('Default renew length is today plus %1$s day(s)'), $loanLength));
                $row->addDate('returnExpected')->setValue(date($_SESSION[$guid]['i18n']['dateFormatPHP'], time() + ($loanLength * 60 * 60 * 24)))->required();

            $row = $form->addRow()->addHeading(__('On Return'));

            $actions = array(
                'Reserve' => __('Reserve'),
                'Decommission' => __('Decommission'),
                'Repair' => __('Repair')
            );
            $row = $form->addRow();
                $row->addLabel('returnAction', __('Action'))->description(__('What to do when item is next returned.'));
                $row->addSelect('returnAction')->fromArray($actions)->selected($values['returnAction'])->placeholder();

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
