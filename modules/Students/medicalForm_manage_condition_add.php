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
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_condition_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'] ?? '';
    $search = $_GET['search'] ?? '';
    $editID = $_GET['editID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Medical Forms'), 'medicalForm_manage.php')
        ->add(__('Manage Medical Forms'), 'medicalForm_manage.php')
        ->add(__('Edit Medical Form'), 'medicalForm_manage_edit.php', ['gibbonPersonMedicalID' => $gibbonPersonMedicalID])
        ->add(__('Add Condition'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/medicalForm_manage_condition_edit.php&'.
            http_build_query([
                'gibbonPersonMedicalConditionID' => $editID,
                'search' => $search,
                'gibbonPersonMedicalID' => $gibbonPersonMedicalID,
            ]);
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'];
    $search = $_GET['search'];
    if ($gibbonPersonMedicalID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {

        $medicalGateway = $container->get(MedicalGateway::class);
        $values = $medicalGateway->getMedicalFormByID($gibbonPersonMedicalID);

        if (empty($values)) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage_edit.php&search=$search&gibbonPersonMedicalID=$gibbonPersonMedicalID'>".__('Back').'</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/medicalForm_manage_condition_addProcess.php?gibbonPersonMedicalID=$gibbonPersonMedicalID&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonPersonMedicalID', $gibbonPersonMedicalID);

            $form->addRow()->addHeading(__('General Information'));

            $row = $form->addRow();
                $row->addLabel('personName', __('Student'));
                $row->addTextField('personName')->setValue(Format::name('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student'))->isRequired()->readonly();

            $sql = "SELECT name AS value, name FROM gibbonMedicalCondition ORDER BY name";
            $row = $form->addRow();
                $row->addLabel('name', __('Condition Name'));
                $row->addSelect('name')->fromQuery($pdo, $sql)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addLabel('gibbonAlertLevelID', __('Risk'));
                $row->addSelectAlert('gibbonAlertLevelID')->isRequired();

            $row = $form->addRow();
                $row->addLabel('triggers', __('Triggers'));
                $row->addTextField('triggers')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('reaction', __('Reaction'));
                $row->addTextField('reaction')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('response', __('Response'));
                $row->addTextField('response')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('medication', __('Medication'));
                $row->addTextField('medication')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('lastEpisode', __('Last Episode Date'));
                $row->addDate('lastEpisode');

            $row = $form->addRow();
                $row->addLabel('lastEpisodeTreatment', __('Last Episode Treatment'));
                $row->addTextField('lastEpisodeTreatment')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
