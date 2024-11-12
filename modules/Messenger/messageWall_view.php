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
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\MessengerGateway;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $date = isset($_REQUEST['date'])? $_REQUEST['date'] : date('Y-m-d');

    $page->breadcrumbs->add(($date === date('Y-m-d')) ?
        __('Today\'s Messages').' ('.$date.')' :
        __('View Messages').' ('.$date.')');

    // Update messenger last read timestamp
    $session->set('messengerLastRead', date('Y-m-d H:i:s'));
    $container->get(UserGateway::class)->update($session->get('gibbonPersonID'), ['messengerLastRead' => date('Y-m-d H:i:s')]);

    // Handle attendance student registration message
    if (isset($_GET['return'])) {
        $status = (!empty($_GET['status'])) ? $_GET['status'] : __('Unknown');
        $emailLink = $container->get(SettingGateway::class)->getSettingByScope('System', 'emailLink');
        if (empty($emailLink)) {
            $suggest = sprintf(__('Why not read the messages below, or %1$scheck your email%2$s?'), '', '');
        }
        else {
            $suggest = sprintf(__('Why not read the messages below, or %1$scheck your email%2$s?'), "<a target='_blank' href='$emailLink'>", '</a>');
        }
        $suggest = '<b>'.$suggest.'</b>';
        $page->return->addReturns(['message0' => sprintf(__('Attendance has been taken for you today. Your current status is: %1$s.'), "<b>".$status."</b>").'<br/><br/>'.$suggest]);

    }

	$form = Form::createBlank('action', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/messageWall_view.php');

	$form->addHiddenValue('address', $session->get('address'));

	$row = $form->addRow()->addClass('flex flex-wrap mb-4');

	$link = $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/messageWall_view.php';
	$prevDay = DateTime::createFromFormat('Y-m-d', $date)->modify('-1 day')->format('Y-m-d');
	$nextDay = DateTime::createFromFormat('Y-m-d', $date)->modify('+1 day')->format('Y-m-d');

	$col = $row->addColumn()->addClass('flex-1 flex items-center');
		$col->addButton(__('Previous Day'))
            ->groupAlign('left')
            ->onClick("window.location.href='{$link}&date={$prevDay}'");
		$col->addButton(__('Next Day'))
            ->groupAlign('right')
            ->onClick("window.location.href='{$link}&date={$nextDay}'");

	$col = $row->addColumn()->addClass('flex items-center justify-end');
		$col->addDate('date')->setValue($date)->setClass('shortWidth')->groupAlign('left');
		$col->addSubmit(__('Go'))->groupAlign('right');

	echo $form->getOutput();

    $messageGateway = $container->get(MessengerGateway::class);
    echo $messageGateway->getMessages('print', Format::dateConvert($date));
}
