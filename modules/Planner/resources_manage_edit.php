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

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage_edit.php') == false) {
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
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/resources_manage.php'>".__($guid, 'Manage Resources')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Resource').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonResourceID = $_GET['gibbonResourceID'];
        if ($gibbonResourceID == 'Y') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Resources_all') {
                    $data = array('gibbonResourceID' => $gibbonResourceID);
                    $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC';
                } elseif ($highestAction == 'Manage Resources_my') {
                    $data = array('gibbonResourceID' => $gibbonResourceID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC';
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                //Let's go!
                $values = $result->fetch();
                $values['gibbonYearGroupID'] = explode(',', $values['gibbonYearGroupIDList']);

                $search = (isset($_GET['search']))? $_GET['search'] : null;

                if (!empty($search)) {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/resources_manage.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/resources_manage_editProcess.php?gibbonResourceID='.$gibbonResourceID.'&search='.$search);
                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('type', $values['type']);

                $form->addRow()->addHeading(__('Resource Contents'));

                if ($values['type'] == 'File') {
                    // File
                    $row = $form->addRow()->addClass('resourceFile');
                        $row->addLabel('file', __('File'));
                        $row->addFileUpload('file')
                            ->addClass('right')
                            ->isRequired()
                            ->setAttachment('content', $_SESSION[$guid]['absoluteURL'], $values['content']);
                } else if ($values['type'] == 'HTML') {
                    // HTML
                    $row = $form->addRow()->addClass('resourceHTML');
                        $column = $row->addColumn()->setClass('');
                        $column->addLabel('html', __('HTML'));
                        $column->addEditor('html', $guid)->isRequired()->setValue($values['content']);
                } else if ($values['type'] == 'Link') {
                    // Link
                    $row = $form->addRow()->addClass('resourceLink');
                        $row->addLabel('link', __('Link'));
                        $row->addURL('link')->maxLength(255)->isRequired()->setValue($values['content']);
                }

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
                    $row->addCheckboxYearGroup('gibbonYearGroupID')->addCheckAllNone();

                $row = $form->addRow();
                    $row->addLabel('description', __('Description'));
                    $row->addTextArea('description')->setRows(8);

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                $form->loadAllValuesFrom($values);

                echo $form->getOutput();

                // HACK: Otherwise FastFinder width overrides this one :(
                echo '<style>.tags ul.token-input-list-facebook {width: 100% !important;} </style>';
            }
        }
    }
}
