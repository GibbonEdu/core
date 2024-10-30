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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/serverInfo.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Server Info'));

    $info = phpinfoArray();

    // FORM
    $form = Form::create('info', '');
    $form->setTitle(__('Server Info'));
    $form->setDescription(__("This page outputs a large amount of information about the your server's configuration. This is useful for troubleshooting and debugging."));

    $form->addRow()->addHeading('System Logs', __('System Logs'));

    // Display the log information at the top for easy reference
    $row = $form->addRow();
        $row->addLabel('logs', __('Error Log Location'));
        $row->addContent($info['Environment']['APACHE_LOG_DIR'] ?? $info['Core']['error_log'] ?? '')
            ->wrap('<div class="text-left w-full">', '</div>');

    $displayErrors = $info['Core']['display_errors'] ?? 'Off';
    $row = $form->addRow();
        $row->addLabel('logs', __('Display Errors'));
        $row->addContent(__(is_array($displayErrors) ? $displayErrors[0] : $displayErrors))
            ->wrap('<div class="text-left w-full">', '</div>');
        
    $logErrors = $info['Core']['log_errors'] ?? 'Off';
    $row = $form->addRow();
        $row->addLabel('logs', __('Log Errors'));
        $row->addContent(__(is_array($logErrors) ? $logErrors[0] : $logErrors))
            ->wrap('<div class="text-left w-full">', '</div>');

    // Display the full contents of the phpinfo function
    foreach ($info as $section => $vars) {
        $form->addRow()->addHeading($section);
        
        foreach ($vars as $name => $value) {
            $value = is_array($value) ? current($value) : $value;

            $row = $form->addRow();
            $row->addLabel('', $name);
            $row->addContent($value)->wrap('<div class="text-left w-full break-all">', '</div>');
        }
    }
    
    echo $form->getOutput();
}
