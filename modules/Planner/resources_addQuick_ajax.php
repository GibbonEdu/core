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

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Setup variables
$output = '';
$id = isset($_GET['id'])? $_GET['id'] : '';
$id = preg_replace('/[^a-zA-Z0-9-_]/', '', $id);

$output .= "<script type='text/javascript'>";
    $output .= '$(document).ready(function() {';
        $output .= 'var options={';
            $output .= 'success: function(response) {';
                $output .= "tinymce.execCommand(\"mceFocus\",false,\"$id\"); tinyMCE.execCommand(\"mceInsertContent\", 0, response); formReset(); \$(\".".$id.'resourceQuickSlider").slideUp();';
            $output .= '}, ';
            $output .= "url: '".$_SESSION[$guid]['absoluteURL']."/modules/Planner/resources_addQuick_ajaxProcess.php',";
            $output .= "type: 'POST'";
        $output .= '};';

        $output .= "$('#".$id."ajaxForm').submit(function() {";
            $output .= '$(this).ajaxSubmit(options);';
            $output .= '$(".'.$id."resourceQuickSlider\").html(\"<div class='resourceAddSlider'><img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif' alt='".__($guid, 'Uploading')."' onclick='return false;' /><br/>".__($guid, 'Loading').'</div>");';
            $output .= 'return false;';
        $output .= '});';
    $output .= '});';

    $output .= 'var formReset=function() {';
        $output .= "$('#".$id."resourceQuick').css('display','none');";
    $output .= '};';
$output .= '</script>';

$form = Form::create($id.'ajaxForm', '')->addClass('resourceQuick');

$form->addHiddenValue('id', $id);
$form->addHiddenValue($id.'address', $_SESSION[$guid]['address']);

$row = $form->addRow();
    $row->addWebLink("<img title='".__($guid, 'Close')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/>")
        ->onClick("formReset(); \$(\".".$id."resourceQuickSlider\").slideUp();")->addClass('right');

for ($i = 1; $i < 5; ++$i) {
    $row = $form->addRow();
        $row->addLabel($id.'file'.$i, sprintf(__('File %1$s'), $i));
        $row->addFileUpload($id.'file'.$i)->setMaxUpload(false);
}

$row = $form->addRow();
    $row->addLabel('imagesAsLinks', __('Insert Images As'));
    $row->addSelect('imagesAsLinks')->fromArray(array('N' => __('Image'), 'Y' => __('Link')))->isRequired();

$row = $form->addRow();
    $row->addContent(getMaxUpload($guid, true));
    $row->addSubmit(__('Upload'));

$output .= $form->getOutput();

echo $output;
