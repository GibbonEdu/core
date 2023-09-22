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
use Gibbon\Domain\System\I18nGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $form = Form::create('update', $session->get('absoluteURL').'/modules/System Admin/i18n_manage_updateAllProcess.php');
    $form->addHiddenValue('address', $_GET['q']);

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addContent(__('Update All'))->wrap('<strong style="font-size: 18px;">', '</strong><br/><br/>');
        $col->addContent(sprintf(__('This action will download the required files and place them in the %1$s folder on your server.'), '<b>'.$session->get('absolutePath').'/i18n/'.'</b>').' '.__('Are you sure you want to continue?'));

    $form->addRow()->addConfirmSubmit();

    echo $form->getOutput();

}
