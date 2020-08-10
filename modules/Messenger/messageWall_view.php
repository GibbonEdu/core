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
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    $dateFormat = $_SESSION[$guid]['i18n']['dateFormatPHP'];
    $date = isset($_REQUEST['date'])? $_REQUEST['date'] : date($dateFormat);

    $page->breadcrumbs->add(($date === date($dateFormat)) ?
        __('Today\'s Messages').' ('.$date.')' :
        __('View Messages').' ('.$date.')');

    if (isset($_GET['return'])) {
        $status = (!empty($_GET['status'])) ? $_GET['status'] : __('Unknown');
        $emailLink = getSettingByScope($connection2, 'System', 'emailLink');
        if (empty($emailLink)) {
            $suggest = sprintf(__('Why not read the messages below, or %1$scheck your email%2$s?'), '', '');
        }
        else {
            $suggest = sprintf(__('Why not read the messages below, or %1$scheck your email%2$s?'), "<a target='_blank' href='$emailLink'>", '</a>');
        }
        $suggest = '<b>'.$suggest.'</b>';
        returnProcess($guid, $_GET['return'], null, array('message0' => sprintf(__('Attendance has been taken for you today. Your current status is: %1$s.'), "<b>".$status."</b>").'<br/><br/>'.$suggest));
    }

	$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/messageWall_view.php');
	$form->setClass('blank fullWidth');

	$form->addHiddenValue('address', $_SESSION[$guid]['address']);

	$row = $form->addRow()->addClass('flex flex-wrap');

	$link = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/messageWall_view.php';
	$prevDay = DateTime::createFromFormat($dateFormat, $date)->modify('-1 day')->format($dateFormat);
	$nextDay = DateTime::createFromFormat($dateFormat, $date)->modify('+1 day')->format($dateFormat);

	$col = $row->addColumn()->addClass('flex-1 flex items-center');
		$col->addButton(__('Previous Day'))->addClass('buttonLink mr-px rounded-l-sm hover:bg-gray-400')->onClick("window.location.href='{$link}&date={$prevDay}'");
		$col->addButton(__('Next Day'))->addClass('buttonLink rounded-r-sm hover:bg-gray-400')->onClick("window.location.href='{$link}&date={$nextDay}'");

	$col = $row->addColumn()->addClass('flex items-center justify-end');
		$col->addDate('date')->setValue($date)->setClass('shortWidth');
		$col->addSubmit(__('Go'));

	echo $form->getOutput();

    echo getMessages($guid, $connection2, 'print', dateConvert($guid, $date));
}
?>
