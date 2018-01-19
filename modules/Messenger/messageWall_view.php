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

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    $dateFormat = $_SESSION[$guid]['i18n']['dateFormatPHP'];
    $date = isset($_REQUEST['date'])? $_REQUEST['date'] : date($dateFormat);

    $extra = '';
    if ($date == date($dateFormat)) {
        $extra = __($guid, "Today's Messages").' ('.$date.')';
    } else {
        $extra = __($guid, 'View Messages').' ('.$date.')';
	}
	
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>$extra</div>";
	echo '</div>';
	
	$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/messageWall_view.php');
	$form->setClass('blank fullWidth');
	
	$form->addHiddenValue('address', $_SESSION[$guid]['address']);

	$row = $form->addRow();

	$link = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/messageWall_view.php';
	$prevDay = DateTime::createFromFormat($dateFormat, $date)->modify('-1 day')->format($dateFormat);
	$nextDay = DateTime::createFromFormat($dateFormat, $date)->modify('+1 day')->format($dateFormat);
	
	$col = $row->addColumn()->addClass('inline');
		$col->addButton(__('Previous Day'))->addClass('buttonLink')->onClick("window.location.href='{$link}&date={$prevDay}'");
		$col->addButton(__('Next Day'))->addClass('buttonLink')->onClick("window.location.href='{$link}&date={$nextDay}'");

	$col = $row->addColumn()->addClass('inline right');
		$col->addDate('date')->setValue($date)->setClass('shortWidth');
		$col->addSubmit(__('Go'));

	echo $form->getOutput();

    echo getMessages($guid, $connection2, 'print', dateConvert($guid, $date));
}
?>