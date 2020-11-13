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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('View Resources'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    echo '<h3>';
    echo __('Filters');
    echo '</h3>';

    //Get current filter values
    $tags = (isset($_REQUEST['tag']))? trim($_REQUEST['tag']) : null;
    $tags = preg_replace('/[^a-zA-Z0-9-_, \']/', '', $tags);
    $tagsArray = (!empty($tags))? explode(',', $tags) : array();

    $category = (isset($_REQUEST['category']))? trim($_REQUEST['category']) : null;
    $purpose = (isset($_REQUEST['purpose']))? trim($_REQUEST['purpose']) : null;
    $gibbonYearGroupID = (isset($_REQUEST['gibbonYearGroupID']))? trim($_REQUEST['gibbonYearGroupID']) : null;

    //Display filters

    $form = Form::create('resourcesView', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/resources_view.php');

    $sql = "SELECT tag as value, CONCAT(tag, ' <i>(', count, ')</i>') as name FROM gibbonResourceTag WHERE count>0 ORDER BY tag";
    $row = $form->addRow();
        $row->addLabel('tag', __('Tags'));
        $row->addFinder('tag')->fromQuery($pdo, $sql)->setParameter('hintText', __('Type a tag...'))->selected($tagsArray);

    $categories = getSettingByScope($connection2, 'Resources', 'categories');
    $row = $form->addRow();
        $row->addLabel('category', __('Category'));
        $row->addSelect('category')->fromString($categories)->placeholder()->selected($category);

    $purposesGeneral = getSettingByScope($connection2, 'Resources', 'purposesGeneral');
    $purposesRestricted = getSettingByScope($connection2, 'Resources', 'purposesRestricted');
    $row = $form->addRow();
        $row->addLabel('purpose', __('Purpose'));
        $row->addSelect('purpose')->fromString($purposesGeneral)->fromString($purposesRestricted)->placeholder()->selected($purpose);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    //Set pagination variable
    $page = null;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    echo '<h3>';
    echo __('View');
    echo '</h3>';

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'WHERE ';
        if ($tags != '') {
            $tagCount = 0;
            $tagArray = explode(',', $tags);
            foreach ($tagArray as $atag) {
                $data['tag'.$tagCount] = "%,".$atag.",%";
                $sqlWhere .= "concat(',', tags, ',') LIKE :tag".$tagCount." AND ";
                ++$tagCount;
            }
        }
        if ($category != '') {
            $data['category'] = $category;
            $sqlWhere .= 'category=:category AND ';
        }
        if ($purpose != '') {
            $data['purpose'] = $purpose;
            $sqlWhere .= 'purpose=:purpose AND ';
        }
        if ($gibbonYearGroupID != '') {
            $data['gibbonYearGroupIDList'] = "%$gibbonYearGroupID%";
            $sqlWhere .= 'gibbonYearGroupIDList LIKE :gibbonYearGroupIDList AND ';
        }
        if ($sqlWhere == 'WHERE ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }
        $sql = "SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) $sqlWhere ORDER BY timestamp DESC";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo " <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/resources_manage_add.php'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "&tags=$tags&category=$category&purpose=$purpose&gibbonYearGroupID=$gibbonYearGroupID");
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Name').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('Contributor').'</span>';
        echo '</th>';
        echo '<th>';
        echo __('Type');
        echo '</th>';
        echo '<th>';
        echo __('Category').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('Purpose').'</span>';
        echo '</th>';
        echo '<th>';
        echo __('Tags');
        echo '</th>';
        echo '<th>';
        echo __('Year Groups');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
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
            echo "<span style='font-size: 85%; font-style: italic'>".Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</span>';
            echo '</td>';
            echo '<td>';
            echo $row['type'];
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['category'].'</b><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['purpose'].'</span>';
            echo '</td>';
            echo '<td>';
            $output = '';
            $tagsInner = explode(',', $row['tags']);
            natcasesort($tagsInner);
            foreach ($tagsInner as $tag) {
                $output .= trim($tag).'<br/>';
            }
            echo substr($output, 0, -2);
            echo '</td>';
            echo '<td>';
            
                $dataYears = array();
                $sqlYears = 'SELECT gibbonYearGroupID, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber';
                $resultYears = $connection2->prepare($sqlYears);
                $resultYears->execute($dataYears);
            $years = explode(',', $row['gibbonYearGroupIDList']);
            if (count($years) > 0 and $years[0] != '') {
                if (count($years) == $resultYears->rowCount()) {
                    echo '<i>'.__('All Years').'</i>';
                } else {
                    $count3 = 0;
                    $count4 = 0;
                    while ($rowYears = $resultYears->fetch()) {
                        for ($i = 0; $i < count($years); ++$i) {
                            if ($rowYears['gibbonYearGroupID'] == $years[$i]) {
                                if ($count3 > 0 and $count4 > 0) {
                                    echo ', ';
                                }
                                echo __($rowYears['nameShort']);
                                ++$count4;
                            }
                        }
                        ++$count3;
                    }
                }
            } else {
                echo '<i>'.__('None').'</i>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "&tags=$tags&category=$category&purpose=$purpose&gibbonYearGroupID=$gibbonYearGroupID");
        }
    }

    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraResources($guid, $connection2);
}
?>
