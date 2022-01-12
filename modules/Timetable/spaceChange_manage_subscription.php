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

$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/tt_exportProcess.php');

// Readonly
$row = $form->addRow();
    $row->addLabel('value', __('Export to Calendar'))->description(__('Please configure your export settings below.'));
// Select
$row = $form->addRow();
    $row->addLabel('value', __('Reminders'));
    $options = array('No Reminder', '5 minutes', '10 minutes', '15 minutes');
    $row->addSelect('id')->fromArray($options);

$row = $form->addRow();
    $row->addSubmit();

echo $form->getOutput();
