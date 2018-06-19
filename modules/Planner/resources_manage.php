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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Resources').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        echo '<h2>';
        echo __($guid, 'Search');
        echo '</h2>';

        $form = Form::create('resourcesManage', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/resources_manage.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Resource name.'));
            $row->addTextField('search')->setValue($search);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();

        echo '<h2>';
        echo __($guid, 'View');
        echo '</h2>';

        try {
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
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/resources_manage_add.php&search='.$search."'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Name').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Contributor').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Type');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Category').'<br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Purpose').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Tags');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Year Groups');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            try {
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($row = $resultPage->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo getResourceLink($guid, $row['gibbonResourceID'], $row['type'], $row['name'], $row['content']);
                echo "<span style='font-size: 85%; font-style: italic'>".formatName($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</span>';
                echo '</td>';
                echo '<td>';
                echo $row['type'];
                echo '</td>';
                echo '<td>';
                echo '<b>'.$row['category'].'</b><br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".$row['purpose'].'</span>';
                echo '</td>';
                echo '</td>';
                echo '<td>';
                $output = '';
                $tags = explode(',', $row['tags']);
                natcasesort($tags);
                foreach ($tags as $tag) {
                    $output .= trim($tag).', ';
                }
                echo substr($output, 0, -2);
                echo '</td>';
                echo '<td>';
                try {
                    $dataYears = array();
                    $sqlYears = 'SELECT gibbonYearGroupID, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber';
                    $resultYears = $connection2->prepare($sqlYears);
                    $resultYears->execute($dataYears);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                $years = explode(',', $row['gibbonYearGroupIDList']);
                if (count($years) > 0 and $years[0] != '') {
                    if (count($years) == $resultYears->rowCount()) {
                        echo '<i>'.__($guid, 'All Years').'</i>';
                    } else {
                        $count3 = 0;
                        $count4 = 0;
                        while ($rowYears = $resultYears->fetch()) {
                            for ($i = 0; $i < count($years); ++$i) {
                                if ($rowYears['gibbonYearGroupID'] == $years[$i]) {
                                    if ($count3 > 0 and $count4 > 0) {
                                        echo ', ';
                                    }
                                    echo __($guid, $rowYears['nameShort']);
                                    ++$count4;
                                }
                            }
                            ++$count3;
                        }
                    }
                } else {
                    echo '<i>'.__($guid, 'None').'</i>';
                }
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/resources_manage_edit.php&gibbonResourceID='.$row['gibbonResourceID']."&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
            }
        }
    }
}
?>
