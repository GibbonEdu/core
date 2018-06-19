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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_browse.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Browse The Library').'</div>';
    echo '</div>';

    //Get display settings
    $browseBGColorStyle = null;
    $browseBGColor = getSettingByScope($connection2, 'Library', 'browseBGColor');
    if ($browseBGColor != '') {
        $browseBGColorStyle = "; background-color: #$browseBGColor";
    }
    $browseBGImageStyle = null;
    $browseBGImage = getSettingByScope($connection2, 'Library', 'browseBGImage');
    if ($browseBGImage != '') {
        $browseBGImageStyle = "; background-image: url(\"$browseBGImage\")";
    }

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    echo "<div style='width: 1050px; border: 1px solid #444; margin-bottom: 30px; background-repeat: no-repeat; min-height: 450px; $browseBGColorStyle $browseBGImageStyle'>";
    echo "<div style='width: 762px; margin: 0 auto'>";
    //Display filters
    echo "<table class='noIntBorder borderGrey' cellspacing='0' style='width: 100%; background-color: rgba(255,255,255,0.8); margin-top: 30px'>";
    echo '<tr>';
    echo "<td style='width: 10px'></td>";
    echo "<td style='width: 50%; padding-top: 5px; text-align: center; vertical-align: top'>";
    echo "<div style='color: #CC0000; margin-bottom: -2px; font-weight: bold; font-size: 135%'>".__($guid, 'Monthly Top 5').'</div>';
    try {
        $dataTop = array('timestampOut' => date('Y-m-d H:i:s', (time() - (60 * 60 * 24 * 30))));
        $sqlTop = "SELECT gibbonLibraryItem.name, producer, COUNT( * ) AS count FROM gibbonLibraryItem JOIN gibbonLibraryItemEvent ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE timestampOut>=:timestampOut AND gibbonLibraryItem.borrowable='Y' AND gibbonLibraryItemEvent.type='Loan' AND gibbonLibraryType.name='Print Publication' GROUP BY producer, name ORDER BY count DESC LIMIT 0, 5";
        $resultTop = $connection2->prepare($sqlTop);
        $resultTop->execute($dataTop);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultTop->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $count = 0;
        while ($rowTop = $resultTop->fetch()) {
            ++$count;
            if ($rowTop['name'] != '') {
                if (strlen($rowTop['name']) > 35) {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. ".substr($rowTop['name'], 0, 35).'...</div>';
                } else {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. ".$rowTop['name'].'</div>';
                }
                if ($rowTop['producer'] != '') {
                    if (strlen($rowTop['producer']) > 35) {
                        echo "<div style='font-style: italic; font-size: 85%'> by ".substr($rowTop['producer'], 0, 35).'...</div>';
                    } else {
                        echo "<div style='font-style: italic; font-size: 85%'> by ".$rowTop['producer'].'</div>';
                    }
                }
            }
        }
    }
    echo '</td>';
    echo "<td style='width: 50%; padding-top: 5px; text-align: center; vertical-align: top'>";
    echo "<div style='color: #CC0000; margin-bottom: -5px; font-weight: bold; font-size: 135%'>".__($guid, 'New Titles').'</div>';
    try {
        $dataTop = array();
        $sqlTop = "SELECT gibbonLibraryItem.name, producer FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItem.borrowable='Y' AND gibbonLibraryType.name='Print Publication'  ORDER BY timestampCreator DESC LIMIT 0, 5";
        $resultTop = $connection2->prepare($sqlTop);
        $resultTop->execute($dataTop);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultTop->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        $count = 0;
        while ($rowTop = $resultTop->fetch()) {
            ++$count;
            if ($rowTop['name'] != '') {
                if (strlen($rowTop['name']) > 35) {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. ".substr($rowTop['name'], 0, 35).'...</div>';
                } else {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. ".$rowTop['name'].'</div>';
                }
                if ($rowTop['producer'] != '') {
                    if (strlen($rowTop['producer']) > 35) {
                        echo "<div style='font-style: italic; font-size: 85%'> by ".substr($rowTop['producer'], 0, 35).'...</div>';
                    } else {
                        echo "<div style='font-style: italic; font-size: 85%'> by ".$rowTop['producer'].'</div>';
                    }
                }
            }
        }
    }
    echo '</td>';
    echo "<td style='width: 5px'></td>";
    echo '</tr>';
    echo '</table>';

    //Get current filter values
    $name = isset($_REQUEST['name'])? trim($_REQUEST['name']) : null;
    $producer = isset($_REQUEST['producer'])? trim($_REQUEST['producer']) : null;
    $category = isset($_REQUEST['category'])? trim($_REQUEST['category']) : null;
    $collection = isset($_REQUEST['collection'])? trim($_REQUEST['collection']) : null;
    $everything = isset($_REQUEST['everything'])? trim($_REQUEST['everything']) : null;
    
    $gibbonLibraryItemID = isset($_GET['gibbonLibraryItemID'])? trim($_GET['gibbonLibraryItemID']) : null;

    // Build the category/collection arrays
    $sql = "SELECT gibbonLibraryTypeID as value, name, fields FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $result = $pdo->executeQuery(array(), $sql);

    $categoryList = ($result->rowCount() > 0)? $result->fetchAll() : array();
    $collections = $collectionsChained = array();
    $categories = array_reduce($categoryList, function($group, $item) use (&$collections, &$collectionsChained) {
        $group[$item['value']] = $item['name'];
        foreach (unserialize($item['fields']) as $field) {
            if ($field['name'] == 'Collection' and $field['type'] == 'Select') {
                foreach (explode(',', $field['options']) as $collectionItem) {
                    $collectionItem = trim($collectionItem);
                    $collections[$collectionItem] = $collectionItem;
                    $collectionsChained[$collectionItem] = $item['value'];
                }
            }
        }
        return $group;
    }, array());


    $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth borderGrey');

    $form->addHiddenValue('q', '/modules/Library/library_browse.php');

    $row = $form->addRow();

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('name', __('Title'));
        $col->addTextField('name')->setClass('fullWidth')->setValue($name);
        
    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('producer', __('Author/Producer'));
        $col->addTextField('producer')->setClass('fullWidth')->setValue($producer);

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('category', __('Category'));
        $col->addSelect('category')
            ->fromArray($categories)
            ->setClass('fullWidth')
            ->selected($category)
            ->placeholder();

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('collection', __('Collection'));
        $col->addSelect('collection')
            ->fromArray($collections)
            ->chainedTo('category', $collectionsChained)
            ->setClass('fullWidth')
            ->selected($collection)
            ->placeholder();

    $col = $form->addRow()->addColumn();
        $col->addLabel('everything', __('All Fields'));
        $col->addTextField('everything')->setClass('fullWidth')->setValue($everything);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();


	//Set pagination variable
	$page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Cache TypeFields
    try {
        $dataTypeFields = array() ;
        $sqlTypeFields = "SELECT gibbonLibraryType.* FROM gibbonLibraryType";
        $resultTypeFields = $connection2->prepare($sqlTypeFields);
        $resultTypeFields->execute($dataTypeFields);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    $typeFieldsTemp = $resultTypeFields->fetchAll();

    $typeFields = array();
    foreach ($typeFieldsTemp as $typeField) {
        $typeFields[$typeField['gibbonLibraryTypeID']] = $typeField;
    }

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'AND ';
        if ($name != '') {
            $data['name'] = '%'.$name.'%';
            $sqlWhere .= 'gibbonLibraryItem.name LIKE :name AND ';
        }
        if ($producer != '') {
            $data['producer'] = '%'.$producer.'%';
            $sqlWhere .= 'producer LIKE :producer AND ';
        }
        if ($category != '') {
            $data['category'] = $category;
            $sqlWhere .= 'gibbonLibraryItem.gibbonLibraryTypeID=:category AND ';
            if ($collection != '') {
                $data['collection'] = '%s:10:"Collection";s:'.strlen($collection).':"'.$collection.'";%';
                $sqlWhere .= 'gibbonLibraryItem.fields LIKE :collection AND ';
            }
        }
        if ($gibbonLibraryItemID != '') {
            $data['gibbonLibraryItemID'] = $gibbonLibraryItemID;
            $sqlWhere .= 'gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID AND ';
        }
        if ($sqlWhere == 'AND ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }

        //SEARCH ALL FIELDS (a.k.a everything)
        try {
            $dataEverything = array();
            $sqlEverything = 'SHOW COLUMNS FROM gibbonLibraryItem';
            $resultEverything = $connection2->prepare($sqlEverything);
            $resultEverything->execute($dataEverything);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        $everythingCount = 0;
        $everythingTokens = explode(' ', $everything);
        $everythingSQL = '';
        while ($rowEverything = $resultEverything->fetch()) {
            $tokenCount = 0;
            foreach ($everythingTokens as $everythingToken) {
                if (count($everythingTokens) == 1) { //Deal with single search token
                    $data['data'.$everythingCount] = '%'.trim($everythingToken).'%';
                    $everythingSQL .= 'gibbonLibraryItem.'.$rowEverything['Field'].' LIKE :data'.$everythingCount.' OR ';
                    ++$everythingCount;
                } else { //Deal with multiple search token, ANDing them within ORs
                    if ($tokenCount == 0) { //First in a set of AND within ORs
                        $data['data'.$everythingCount] = '%'.trim($everythingToken).'%';
                        $everythingSQL .= '(gibbonLibraryItem.'.$rowEverything['Field'].' LIKE :data'.$everythingCount.' AND ';
                        ++$everythingCount;
                    } elseif (($tokenCount + 1) == count($everythingTokens)) { //Last in a set of AND within ORs
                        $data['data'.$everythingCount] = '%'.trim($everythingToken).'%';
                        $everythingSQL .= 'gibbonLibraryItem.'.$rowEverything['Field'].' LIKE :data'.$everythingCount.') OR ';
                        ++$everythingCount;
                    } else { //All others in a set of AND within ORs
                        $data['data'.$everythingCount] = '%'.trim($everythingToken).'%';
                        $everythingSQL .= 'gibbonLibraryItem.'.$rowEverything['Field'].' LIKE :data'.$everythingCount.' AND ';
                        ++$everythingCount;
                    }
                    ++$tokenCount;
                }
            }
        }
        //Find prep for search all fields
        if (strlen($everythingSQL) > 0) {
            if (count($everythingTokens) == 1) {
                $everythingSQL = ' AND ('.substr($everythingSQL, 0, -5).')';
            } else {
                $everythingSQL = ' AND ('.substr($everythingSQL, 0, -4).')';
            }
            $sqlWhere .= $everythingSQL;
        }

        $sql = "SELECT gibbonLibraryItem.* FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE (status='Available' OR status='On Loan' OR status='Repair' OR status='Reserved') AND NOT ownershipType='Individual' AND borrowable='Y' $sqlWhere ORDER BY id";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "name=$name&producer=$producer&category=$category&collection=$collection&everything=$everything");
        }

        echo "<table class='smallIntBorder borderGrey' cellspacing='0' style='width: 100%;'>";
        echo "<tr class='head' style='opacity: 0.7'>";
        echo "<th style='text-align: center'>";

        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Author/Producer').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'ID').'<br/>';
        echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Status').'</span>';
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Location');
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

			//COLOR ROW BY STATUS!
			echo "<tr class=$rowNum style='opacity: 1.0'>";
            echo "<td style='width: 260px'>";
            echo getImage($guid, $row['imageType'], $row['imageLocation'], false);
            echo '</td>';
            echo "<td style='width: 130px'>";
            echo '<b>'.$row['name'].'</b><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['producer'].'</span>';
            echo '</td>';
            echo "<td style='width: 130px'>";
            echo '<b>'.$row['id'].'</b><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>".$row['status'].'</span>';
            echo '</td>';
            echo "<td style='width: 130px'>";
            if ($row['gibbonSpaceID'] != '') {
                try {
                    $dataSpace = array('gibbonSpaceID' => $row['gibbonSpaceID']);
                    $sqlSpace = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
                    $resultSpace = $connection2->prepare($sqlSpace);
                    $resultSpace->execute($dataSpace);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($resultSpace->rowCount() == 1) {
                    $rowSpace = $resultSpace->fetch();
                    echo '<b>'.$rowSpace['name'].'</b><br/>';
                }
            }
            if ($row['locationDetail'] != '') {
                echo "<span style='font-size: 85%; font-style: italic'>".$row['locationDetail'].'</span>';
            }
            echo '</td>';
            echo '<td>';
            echo "<script type='text/javascript'>";
            echo '$(document).ready(function(){';
            echo "\$(\".description-$count\").hide();";
            echo "\$(\".show_hide-$count\").fadeIn(1000);";
            echo "\$(\".show_hide-$count\").click(function(){";
            echo "\$(\".description-$count\").fadeToggle(1000);";
            echo '});';
            echo '});';
            echo '</script>';
            if ($row['fields'] != '') {
                echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='Show Details' onclick='return false;' /></a>";
            }
            echo '</td>';
            echo '</tr>';
            if ($row['fields'] != '') {
                echo "<tr class='description-$count' id='fields-$count' style='background-color: #fff; display: none'>";
                echo '<td colspan=5>';
                echo "<table cellspacing='0' style='width: 100%'>";
                $typeFieldsInner = unserialize($typeFields[$row['gibbonLibraryTypeID']]['fields']);
                $fields = unserialize($row['fields']);
                if (is_array($typeFieldsInner) && count($typeFieldsInner) > 0) {
                    foreach ($typeFieldsInner as $typeField) {
                        if (isset($fields[$typeField['name']]) && $fields[$typeField['name']] != '') {
                            echo '<tr>';
                            echo "<td style='vertical-align: top; width: 200px'>";
                            echo '<b>'.($typeField['name']).'</b>';
                            echo '</td>';
                            echo "<td style='vertical-align: top'>";
                            if ($typeField['type'] == 'URL') {
                                echo "<a target='_blank' href='".$fields[$typeField['name']]."'>".$fields[$typeField['name']].'</a><br/>';
                            } else {
                                echo $fields[$typeField['name']].'<br/>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                }
                echo '</table>';
                echo '</td>';
                echo '</tr>';
            }

            ++$count;
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "name=$name&producer=$producer&category=$category&collection=$collection&everything=$everything");
        }
    }
    echo '</div>';
    echo '</div>';
}
