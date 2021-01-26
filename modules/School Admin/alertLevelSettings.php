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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/daysOfWeek_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Alert Levels'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $data = array();
    $sql = 'SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber';
    $result = $connection2->prepare($sql);
    $result->execute($data);

    //Let's go!
    $form = Form::create('alertLevelSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/alertLevelSettingsProcess.php' );

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $count = 0;
    while ($rowSQL = $result->fetch()) {
        $row = $form->addRow()->addHeading(__($rowSQL['name']));

        $form->addHiddenValue('gibbonAlertLevelID'.$count, $rowSQL['gibbonAlertLevelID']);

        $row = $form->addRow();
        	$row->addLabel('name'.$count, __('Name'));
    		$row->addTextField('name'.$count)
            ->setValue($rowSQL['name'])
            ->maxLength(50)
            ->required();

        $row = $form->addRow();
        	$row->addLabel('nameShort'.$count, __('Short Name'));
    		$row->addTextField('nameShort'.$count)
            ->setValue($rowSQL['nameShort'])
            ->maxLength(4)
            ->required();

        $row = $form->addRow();
        	$row->addLabel('color'.$count, __('Font/Border Colour'))->description(__('Click to select a colour.'));
    		$row->addColor("color$count")
                ->setValue($rowSQL['color'])
                ->required();

        $row = $form->addRow();
        	$row->addLabel('colorBG'.$count, __('Background Colour'))->description(__('Click to select a colour.'));
    		$row->addColor("colorBG$count")
                ->setValue($rowSQL['colorBG'])
                ->required();

        $row = $form->addRow();
        	$row->addLabel('sequenceNumber'.$count, __('Sequence Number'));
    		$row->addTextField('sequenceNumber'.$count)
            ->setValue($rowSQL['sequenceNumber'])
            ->maxLength(4)
            ->readonly()
            ->required();

        $row = $form->addRow();
        	$row->addLabel('description'.$count, __('Description'));
            $row->addTextArea('description'.$count)->setValue($rowSQL['description']);

        $count++;
    }

    $form->addHiddenValue('count', $count);

    $row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();

}
