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

@session_start();

use \Library\Forms\Form as Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/daysOfWeek_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Alert Levels').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    //Let's go!
    $form = Form::create('financeSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/alertLevelSettingsProcess.php' );

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $count = 0;
    while ($rowSQL = $result->fetch()) {
        $row = $form->addRow()->addHeading($rowSQL['name']);

        $form->addHiddenValue('gibbonAlertLevelID'.$count, $rowSQL['gibbonAlertLevelID']);

        $row = $form->addRow();
        	$row->addLabel('name'.$count, 'Name');
    		$row->addTextField('name'.$count)
            ->setValue($rowSQL['name'])
            ->maxLength(50)
            ->isRequired();

        $row = $form->addRow();
        	$row->addLabel('nameShort'.$count, 'Short Name');
    		$row->addTextField('nameShort'.$count)
            ->setValue($rowSQL['nameShort'])
            ->maxLength(4)
            ->isRequired();

        $row = $form->addRow();
        	$row->addLabel('color'.$count, 'Font/Border Color')->description('RGB Hex value, without leading #.');
    		$row->addTextField('color'.$count)
                ->setValue($rowSQL['color'])
                ->maxLength(6)
                ->isRequired();

        $row = $form->addRow();
        	$row->addLabel('colorBG'.$count, 'Background Color')->description('RGB Hex value, without leading #.');
    		$row->addTextField('colorBG'.$count)
                ->setValue($rowSQL['colorBG'])
                ->maxLength(6)
                ->isRequired();

        $row = $form->addRow();
        	$row->addLabel('sequenceNumber'.$count, 'Sequence Number');
    		$row->addTextField('sequenceNumber'.$count)
            ->setValue($rowSQL['sequenceNumber'])
            ->maxLength(4)
            ->readonly()
            ->isRequired();

        $row = $form->addRow();
        	$row->addLabel('description'.$count, 'Description');
            $row->addTextArea('description'.$count)->setValue($rowSQL['description']);

        $count++;
    }

    $form->addHiddenValue('count', $count);

    $row = $form->addRow();
		$row->addContent('<span class="emphasis small">* '.__('denotes a required field').'</span>');
		$row->addSubmit();

	echo $form->getOutput();

}
?>
