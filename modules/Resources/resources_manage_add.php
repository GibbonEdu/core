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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Resources/resources_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/resources_manage.php'>".__($guid, 'Manage Resources')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Resource').'</div>';
        echo '</div>';

        $search = (isset($_GET['search']))? $_GET['search'] : null;

        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Resources/resources_manage_edit.php&gibbonResourceID='.$_GET['editID'].'&search='.$_GET['search'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, null);
        }

        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Resources/resources_manage.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/resources_manage_addProcess.php?search='.$search);
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $form->addRow()->addHeading(__('Resource Contents'));

        $types = array('File' => __('File'), 'HTML' => __('HTML'), 'Link' => __('Link'));
        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($types)->isRequired()->placeholder();

        // File
        $form->toggleVisibilityByClass('resourceFile')->onSelect('type')->when('File');
        $row = $form->addRow()->addClass('resourceFile');
            $row->addLabel('file', __('File'));
            $row->addFileUpload('file')->isRequired()->addClass('right');

        // HTML
        $form->toggleVisibilityByClass('resourceHTML')->onSelect('type')->when('HTML');
        $row = $form->addRow()->addClass('resourceHTML');
            $column = $row->addColumn()->setClass('');
            $column->addLabel('html', __('HTML'));
            $column->addEditor('html', $guid)->isRequired();

        // Link
        $form->toggleVisibilityByClass('resourceLink')->onSelect('type')->when('Link');
        $row = $form->addRow()->addClass('resourceLink');
            $row->addLabel('link', __('Link'));
            $row->addURL('link')->maxLength(255)->isRequired();

        $form->addRow()->addHeading(__('Resource Details'));

        $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')->isRequired()->maxLength(60);

        $categories = getSettingByScope($connection2, 'Resources', 'categories');
        $row = $form->addRow();
            $row->addLabel('category', __('Category'));
            $row->addSelect('category')->fromString($categories)->isRequired()->placeholder();

        $purposesGeneral = getSettingByScope($connection2, 'Resources', 'purposesGeneral');
        $purposesRestricted = getSettingByScope($connection2, 'Resources', 'purposesRestricted');
        $row = $form->addRow();
            $row->addLabel('purpose', __('Purpose'));
            $row->addSelect('purpose')->fromString($purposesGeneral)->fromString($purposesRestricted)->placeholder();

        $sql = "SELECT tag as value, CONCAT(tag, ' <i>(', count, ')</i>') as name FROM gibbonResourceTag WHERE count>0 ORDER BY tag";
        $row = $form->addRow()->addClass('tags');
            $column = $row->addColumn();
            $column->addLabel('tags', __('Tags'))->description(__('Use lots of tags!'));
            $column->addFinder('tags')
                ->fromQuery($pdo, $sql)
                ->isRequired()
                ->setParameter('hintText', __('Type a tag...'))
                ->setParameter('allowCreation', true);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Groups'))->description(__('Students year groups which may participate'));
            $row->addCheckboxYearGroup('gibbonYearGroupID')->checkAll()->addCheckAllNone();

        $row = $form->addRow();
            $row->addLabel('description', __('Description'));
            $row->addTextArea('description')->setRows(8);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

        // HACK: Otherwise FastFinder width overrides this one :(
        echo '<style>.tags ul.token-input-list-facebook {width: 100% !important;} </style>';
    }
}
