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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Input\Editor;

include '../../gibbon.php';
include './moduleFunctions.php';

$page = $container->get('page');

$id = $_POST['id'] ?? '';
$value = $_POST['value'] ?? '';
$showMedia = $_POST['showMedia'] ?? false;
$rows = !empty($_POST['rows']) ? $_POST['rows'] : 15;

$editor = (new Editor($id))
    ->tinymceInit(false)
    ->setValue($value)
    ->setRows($rows)
    ->showMedia($showMedia)
    ->setRequired(false)
    ->initiallyHidden(false)
    ->allowUpload($showMedia)
    ->resourceAlphaSort(false);
echo $editor->getOutput();
