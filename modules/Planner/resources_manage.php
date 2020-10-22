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
use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Manage Resources'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }
        
        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        
        
        $form = Form::create('resourcesManage', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');
        
        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/resources_manage.php');
        
        $form->setTitle(__('Search'));
        
        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Resource name.'));
            $row->addTextField('search')->setValue($search);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();

        if ($highestAction == 'Manage Resources_all') {
            $data = array();
            $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY timestamp DESC';
            if ($search != '') {
                $data = array('name' => "%$search%");
                $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) AND (name LIKE :name) ORDER BY timestamp DESC';
            }
        } elseif ($highestAction == 'Manage Resources_my') {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID ORDER BY timestamp DESC';
            if ($search != '') {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'name' => "%$search%");
                $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID AND (name LIKE :name) ORDER BY timestamp DESC';
            }
        }
        $result = $pdo->select($sql, $data)->toDataSet();
        
        $table = DataTable::create('resources');
        $table->setTitle('View');
         $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/' .$gibbon->session->get('module') . '/resources_manage_add.php')
            ->displayLabel();
            
        $table->addColumn('name', __('Name'))->description(__('Contributor'))->format(function ($row) use ($guid) {
            return getResourceLink($guid, $row['gibbonResourceID'], $row['type'], $row['name'], $row['content']) . '<br/>'. Format::small(__(Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff')));
        });
        $table->addColumn('type', __('Type'));
        $table->addColumn('category', __('Category'))->description(__('Purpose'))->format(function ($row) {
            return $row['category'] . '<br/>'. Format::small(__($row['purpose']));
        });
        $table->addColumn('tags', __('Tags'))->format(function ($row) {
                $output = '';
                $tags = explode(',', $row['tags']);
                natcasesort($tags);
                foreach ($tags as $tag) {
                    $output .= trim($tag).', ';
                }
                return substr($output, 0, -2);
        });
        
        $table->addColumn('years', __('Year Groups'))->format(function ($row) use ($connection2) {
                $dataYears = array();
                $sqlYears = 'SELECT gibbonYearGroupID, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber';
                $resultYears = $connection2->prepare($sqlYears);
                $resultYears->execute($dataYears);
                
                $years = explode(',', $row['gibbonYearGroupIDList']);
                if (count($years) > 0 and $years[0] != '') {
                    if (count($years) == $resultYears->rowCount()) {
                        Return '<i>'.__('All Years').'</i>';
                    } else {
                        $count3 = 0;
                        $count4 = 0;
                        $output = '';
                        while ($rowYears = $resultYears->fetch()) {
                            for ($i = 0; $i < count($years); ++$i) {
                                if ($rowYears['gibbonYearGroupID'] == $years[$i]) {
                                    if ($count3 > 0 and $count4 > 0) {
                                        $output .= ', ';
                                    }
                                    $output .= __($rowYears['nameShort']);
                                    ++$count4;
                                }
                            }
                            ++$count3;
                        }
                       return $output;
                    }
                } else {
                    Return '<i>'.__('None').'</i>';
                }
        });
        
        $actions = $table->addActionColumn()->addParam('gibbonResourceID');
        $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Planner/resources_manage_edit.php');

        echo $table->render($result);
        
    }
}
?>
