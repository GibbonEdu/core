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

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (!$session->has('gibbonPersonID')) {
    echo Format::alert(__('You do not have access to this action.'));
    exit();
} else {
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
                $output .= "url: '".$session->get('absoluteURL')."/modules/Planner/resources_addQuick_ajaxProcess.php',";
                $output .= "type: 'POST'";
            $output .= '};';

            $output .= "$('#".$id."ajaxForm').submit(function() {";
                $output .= '$(this).ajaxSubmit(options);';
                $output .= '$(".'.$id."resourceQuickSlider\").html(\"<div class='resourceAddSlider'><img style='margin: 10px 0 5px 0' src='".$session->get('absoluteURL').'/themes/'.($session->get('gibbonThemeName') ?? 'Default')."/img/loading.gif' alt='".__('Uploading')."' onclick='return false;' /><br/>".__('Loading').'</div>");';
                $output .= 'return false;';
            $output .= '});';
        $output .= '});';

        $output .= 'var formReset=function() {';
            $output .= "$('#".$id."resourceQuick').css('display','none');";
        $output .= '};';
    $output .= '</script>';

    $form = Form::create($id.'ajaxForm', '')->addClass('resourceQuick');

    $form->addHiddenValue('id', $id);
    $form->addHiddenValue($id.'address', $session->get('address'));

    $row = $form->addRow();
        $row->addWebLink("<img title='".__('Close')."' src='./themes/".($session->get('gibbonThemeName') ?? 'Default')."/img/iconCross.png'/>")
            ->onClick("formReset(); \$(\".".$id."resourceQuickSlider\").slideUp();")->addClass('right');

    for ($i = 1; $i < 5; ++$i) {
        $row = $form->addRow();
            $row->addLabel($id.'file'.$i, sprintf(__('File %1$s'), $i));
            $row->addFileUpload($id.'file'.$i)->setMaxUpload(false);
    }

    $row = $form->addRow();
        $row->addLabel('imagesAsLinks', __('Insert Images As'));
        $row->addSelect('imagesAsLinks')->fromArray(array('N' => __('Image'), 'Y' => __('Link')))->required();

    $row = $form->addRow();
        $row->addContent(getMaxUpload(true));
        $row->addSubmit(__('Upload'), 'bg-purple');

    $output .= $form->getOutput();

    echo $output;
}
