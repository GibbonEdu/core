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

require_once './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_postQuickWall.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'New Quick Wall Message').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true&gibbonMessengerID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    echo "<div class='warning'>";
    echo __($guid, 'This page allows you to quick post a message wall entry to all users, without needing to set a range of options, making it a quick way to post to the Message Wall.');
	echo '</div>';
	
	$form = Form::create('postQuickWall', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/messenger_postQuickWallProcess.php?address='.$_GET['q']);
                
	$form->addHiddenValue('messageWall', 'Y');

	$sql = "SELECT DISTINCT category FROM gibbonRole ORDER BY category";
	$result = $pdo->executeQuery(array(), $sql);
	$categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();
	foreach($categories as $key => $category) {
		$form->addHiddenValue("roleCategories[$key]", $category);
	}
	
	$form->addRow()->addHeading(__('Delivery Mode'));

	$row = $form->addRow();
		$row->addLabel('messageWallLabel', __('Message Wall'))->description(__('Place this message on user\'s message wall?'));
		$row->addTextField('messageWallText')->readonly()->setValue(__('Yes'));

	$row = $form->addRow();
        $row->addLabel('date1', __('Publication Dates'))->description(__('Select up to three individual dates.'));
		$col = $row->addColumn('date1')->addClass('stacked');
		$col->addDate('date1')->setValue(dateConvertBack($guid, date('Y-m-d')))->isRequired();
		$col->addDate('date2');
		$col->addDate('date3');

	$form->addRow()->addHeading(__('Message Details'));

    $row = $form->addRow();
        $row->addLabel('subject', __('Subject'));
        $row->addTextField('subject')->isRequired()->maxLength(60);

    $row = $form->addRow();
        $col = $row->addColumn('body');
        $col->addLabel('body', __('Body'));
        $col->addEditor('body', $guid)->isRequired()->setRows(20)->showMedia(true);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
