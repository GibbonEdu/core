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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYear_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage School Years'), 'schoolYear_manage.php')
        ->add(__('Edit School Year'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('schoolYear', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/schoolYear_manage_editProcess.php?gibbonSchoolYearID='.$gibbonSchoolYearID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $statuses = array(
                'Past'     => __('Past'),
                'Current'  => __('Current'),
                'Upcoming' => __('Upcoming'),
            );

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired()->maxLength(9)->setValue($values['name']);

            if ($values['status'] == 'Current') {
                $form->addHiddenValue('status', $values['status']);
                $row = $form->addRow();
                    $row->addLabel('status', __('Status'));
                    $row->addTextField('status')->readOnly()->setValue($values['status']);
            } else {
                $row = $form->addRow();
                    $row->addLabel('status', __('Status'));
                    $row->addSelect('status')->fromArray($statuses)->isRequired()->selected($values['status']);

                    $form->toggleVisibilityByClass('statusChange')->onSelect('status')->when('Current');
                    $direction = ($values['sequenceNumber'] < $_SESSION[$guid]['gibbonSchoolYearSequenceNumberCurrent'])? __('Upcoming') : __('Past');

                    // Display an alert to warn users that changing this will have an impact on their system.
                    $row = $form->addRow()->setClass('statusChange');
                    $row->addAlert(sprintf(__('Setting the status of this school year to Current will change the current school year %1$s to %2$s. Adjustments to the Academic Year can affect the visibility of vital data in your system. It\'s recommended to use the Rollover tool in User Admin to advance school years rather than changing them here. PROCEED WITH CAUTION!'), $_SESSION[$guid]['gibbonSchoolYearNameCurrent'], $direction) );
            }

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
                $row->addSequenceNumber('sequenceNumber', 'gibbonSchoolYear', $values['sequenceNumber'])->isRequired()->maxLength(3)->setValue($values['sequenceNumber']);

            $row = $form->addRow();
                $row->addLabel('firstDay', __('First Day'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                $row->addDate('firstDay')->isRequired()->setValue(dateConvertBack($guid, $values['firstDay']));

            $row = $form->addRow();
                $row->addLabel('lastDay', __('Last Day'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                $row->addDate('lastDay')->isRequired()->setValue(dateConvertBack($guid, $values['lastDay']));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
