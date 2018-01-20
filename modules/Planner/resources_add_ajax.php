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
use Gibbon\Forms\DatabaseFormFactory;

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Setup variables
$output = '';
$id = $_GET['id'];
$action = null;
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}
$category = null;
if (isset($_GET['category'])) {
    $category = $_GET['category'];
}
$purpose = null;
if (isset($_GET['purpose'])) {
    $purpose = $_GET['purpose'];
}
$tag = null;
if (isset($_GET['tag'.$id])) {
    $tag = $_GET['tag'.$id];
}
$gibbonYearGroupID = null;
if (isset($_GET['gibbonYearGroupID'])) {
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
}
$allowUpload = $_GET['allowUpload'];
$alpha = null;
if (isset($_GET['alpha'])) {
    $alpha = $_GET['alpha'];
}

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage_add.php') == false) {
    //Acess denied
    $output .= "<div class='error'>";
    $output .= __($guid, 'Your request failed because you do not have access to this action.');
    $output .= '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Planner/resources_manage.php', $connection2);
    if ($highestAction == false) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'The highest grouped action cannot be determined.');
        $output .= '</div>';
    } else {
        $output .= "<script type='text/javascript'>";
        $output .= '$(document).ready(function() {';

        $output .= "$('.checkall').click(function () {";
        $output .= "$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);";
        $output .= "});";
        $output .= 'var options={';
        $output .= 'success: function(response) {';
        $output .= "tinymce.execCommand(\"mceFocus\",false,\"$id\"); tinyMCE.execCommand(\"mceInsertContent\", 0, response); formReset(); \$(\".".$id.'resourceAddSlider").slideUp();';
        $output .= '}, ';
        $output .= "url: '".$_SESSION[$guid]['absoluteURL']."/modules/Planner/resources_add_ajaxProcess.php',";
        $output .= "type: 'POST'";
        $output .= '};';

        $output .= "$('#".$id."ajaxForm').submit(function() {";
        $output .= '$(this).ajaxSubmit(options);';
        $output .= '$(".'.$id."resourceAddSlider\").html(\"<div class='resourceAddSlider'><img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif' alt='".__($guid, 'Uploading')."' onclick='return false;' /><br/>".__($guid, 'Loading').'</div>");';
        $output .= 'return false;';
        $output .= '});';
        $output .= '});';

        $output .= 'var formReset=function() {';
        $output .= "$('#".$id."resourceAdd').css('display','none');";
        $output .= '};';
        $output .= '</script>';

        $form = Form::create($id.'ajaxForm', '')->addClass('resourceQuick');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('id', $id);
        $form->addHiddenValue($id.'address', $_SESSION[$guid]['address']);

        $col = $form->addRow()->addColumn();
            $col->addWebLink("<img title='".__($guid, 'Close')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/>")
                ->onClick("formReset(); \$(\".".$id."resourceAddSlider\").slideUp();")->addClass('right');
            $col->addContent(__('Add & Insert A New Resource'))->wrap('<h3 style="margin-top: 0;">', '</h3>');
            $col->addContent(__('Use the form below to add a new resource to Gibbon. If the addition is successful, then it will be automatically inserted into your work above. Note that you  cannot create HTML resources here (you have to go to the Planner module for that).'))->wrap('<p>', '</p>');
        
        $form->addRow()->addSubheading(__('Resource Contents'));

        $types = array('File' => __('File'), 'Link' => __('Link'));
        $row = $form->addRow();
            $row->addLabel($id.'type', __('Type'));
            $row->addSelect($id.'type')->fromArray($types)->isRequired()->placeholder();

        // File
        $form->toggleVisibilityByClass('resourceFile')->onSelect($id.'type')->when('File');
        $row = $form->addRow()->addClass('resourceFile');
            $row->addLabel($id.'file', __('File'));
            $row->addFileUpload($id.'file')->isRequired();

        // Link
        $form->toggleVisibilityByClass('resourceLink')->onSelect($id.'type')->when('Link');
        $row = $form->addRow()->addClass('resourceLink');
            $row->addLabel($id.'link', __('Link'));
            $row->addURL($id.'link')->maxLength(255)->isRequired();

        $form->addRow()->addSubheading(__('Resource Details'));

        $row = $form->addRow();
            $row->addLabel($id.'name', __('Name'));
            $row->addTextField($id.'name')->isRequired()->maxLength(60);

        $categories = getSettingByScope($connection2, 'Resources', 'categories');
        $row = $form->addRow();
            $row->addLabel($id.'category', __('Category'));
            $row->addSelect($id.'category')->fromString($categories)->isRequired()->placeholder();

        $purposesGeneral = getSettingByScope($connection2, 'Resources', 'purposesGeneral');
        $purposesRestricted = ($highestAction == 'Manage Resources_all')? getSettingByScope($connection2, 'Resources', 'purposesRestricted') : '';
        $row = $form->addRow();
            $row->addLabel($id.'purpose', __('Purpose'));
            $row->addSelect($id.'purpose')->fromString($purposesGeneral)->fromString($purposesRestricted)->placeholder();

        $sql = "SELECT tag as value, CONCAT(tag, ' <i>(', count, ')</i>') as name FROM gibbonResourceTag WHERE count>0 ORDER BY tag";
        $row = $form->addRow()->addClass('tags');
            $row->addLabel($id.'tags', __('Tags'))->description(__('Use lots of tags!'));
            $row->addFinder($id.'tags')
                ->fromQuery($pdo, $sql)
                ->isRequired()
                ->setParameter('hintText', __('Type a tag...'))
                ->setParameter('allowCreation', true);

        $row = $form->addRow();
            $row->addLabel($id.'gibbonYearGroupID', __('Year Groups'))->description(__('Students year groups which may participate'));
            $row->addCheckboxYearGroup($id.'gibbonYearGroupID')->checkAll()->addCheckAllNone();

        $row = $form->addRow();
            $row->addLabel($id.'description', __('Description'));
            $row->addTextArea($id.'description')->setRows(8);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();
        
        $output .= $form->getOutput();
    }
}

echo $output;
