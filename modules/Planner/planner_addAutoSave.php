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

use Gibbon\Data\Validator; 
use Gibbon\Forms\Builder\Storage\FormSessionStorage;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML', 'teacherNotes' => 'HTML', 'homeworkDetails' => 'HTML', 'contents*' => 'HTML', 'teachersNotes*' => 'HTML']);

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_add.php') == false) {
    header("Location: /");
    exit();
}

if (empty($_POST)) {
    header("Location: /");
    exit();
}

$formData = $container->get(FormSessionStorage::class);
$formData->load('plannerAdd');
$formData->addData($_POST);
$formData->save('plannerAdd');
