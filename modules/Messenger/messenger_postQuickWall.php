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

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('New Quick Wall Message'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_postQuickWall.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true&gibbonMessengerID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $page->addMessage(__('This page allows you to quick post a message wall entry to all users, without needing to set a range of options, making it a quick way to post to the Message Wall.'));

	$form = Form::create('postQuickWall', $session->get('absoluteURL').'/modules/'.$session->get('module').'/messenger_postQuickWallProcess.php?address='.$_GET['q']);

	$form->addHiddenValue('messageWall', 'Y');

	$sql = "SELECT DISTINCT category FROM gibbonRole ORDER BY category";
	$result = $pdo->executeQuery(array(), $sql);
	$categories = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN, 0) : array();
	foreach($categories as $key => $category) {
		$form->addHiddenValue("roleCategories[$key]", $category);
	}

	$form->addRow()->addHeading('Delivery Mode', __('Delivery Mode'));

	$row = $form->addRow();
		$row->addLabel('messageWallLabel', __('Message Wall'))->description(__('Place this message on user\'s message wall?'));
		$row->addTextField('messageWallText')->readonly()->setValue(__('Yes'));

    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage.php", "Manage Messages_all")) {
        $row = $form->addRow();
            $row->addLabel('messageWallPin', __('Pin To Top?'));
            $row->addYesNo('messageWallPin')->selected('N')->required();
    }

	$row = $form->addRow();
        $row->addLabel('datePublished', __('Publication Dates'));
		$col = $row->addColumn('dateStart')->addClass('stacked');
        $col->addLabel('dateStart', __('Start Date'));
        $col->addDate('dateStart')->chainedTo('dateEnd')->required();
        $col->addLabel('dateEnd', __('End Date'));
		$col->addDate('dateEnd')->chainedFrom('dateStart')->required();

	$form->addRow()->addHeading('Message Details', __('Message Details'));

    $row = $form->addRow();
        $row->addLabel('subject', __('Subject'));
        $row->addTextField('subject')->required()->maxLength(60);

    $row = $form->addRow();
        $col = $row->addColumn('body');
        $col->addLabel('body', __('Body'));
        $col->addEditor('body', $guid)->required()->setRows(20)->showMedia(true);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
