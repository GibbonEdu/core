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
$id = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

$category = isset($_POST['category'.$id])? $_POST['category'.$id] : (isset($_GET['category'])? $_GET['category'] : '');
$purpose = isset($_POST['purpose'.$id])? $_POST['purpose'.$id] : (isset($_GET['purpose'])? $_GET['purpose'] : '');
$gibbonYearGroupID = isset($_POST['gibbonYearGroupID'.$id])? $_POST['gibbonYearGroupID'.$id] : (isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '');
$tags = isset($_POST['tags'.$id])? $_POST['tags'.$id] : (isset($_GET['tags'])? $_GET['tags'] : null);

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_view.php') == false) {
    //Acess denied
    $output .= "<div class='error'>";
    $output .= __($guid, 'Your request failed because you do not have access to this action.');
    $output .= '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Planner/resources_manage.php', $connection2);

    $output .= "<script type='text/javascript'>";
    $output .= '$(document).ready(function() {';
    $output .= 'var optionsSearch={';
    $output .= 'target: $(".'.$id.'resourceSlider"),';
    $output .= "url: '".$_SESSION[$guid]['absoluteURL']."/modules/Planner/resources_insert_ajax.php?id=$id',";
    $output .= "type: 'POST'";
    $output .= '};';

    $output .= "$('#".$id."ajaxFormSearch').submit(function() {";
    $output .= '$(this).ajaxSubmit(optionsSearch);';
    $output .= 'return false;';
    $output .= '});';
    $output .= '});';

    $output .= 'var formResetSearch=function() {';
    $output .= "$('#".$id."resourceInsert').css('display','none');";
    $output .= '};';
    $output .= '</script>';

    $output .= '<style>#'.$id.'ajaxFormSearch ul.token-input-list-facebook { margin: 0 10px; width: 610px !important; float: none }</style>';

    $output .= "<table cellspacing='0' style='width: 100%'>";
    $output .= "<tr id='".$id."resourceInsert'>";
    $output .= "<td colspan=2 style='padding-top: 0px'>";
    $output .= "<div style='margin: 0px' class='linkTop'><a href='javascript:void(0)' onclick='formResetSearch(); \$(\".".$id."resourceSlider\").slideUp();'><img title='".__($guid, 'Close')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/></a></div>";
    $output .= "<h3 style='margin-top: 0px; font-size: 140%'>Insert A Resource</h3>";
    $output .= '<p>'.sprintf(__($guid, 'The table below shows shared resources drawn from the %1$sPlanner%2$s section of Gibbon. You will see the 50 most recent resources that match the filters you have used.'), "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/resources_view.php'>", '</a>').'</p>';
    
    $form = Form::create($id.'ajaxFormSearch', '');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');
            
    $row = $form->addRow();
    
    $categories = getSettingByScope($connection2, 'Resources', 'categories');
    $col = $row->addColumn();
        $col->addLabel('category'.$id, __('Category'));
        $col->addSelect('category'.$id)->fromString($categories)->placeholder()->setClass('mediumWidth')->selected($category);

    $purposesGeneral = getSettingByScope($connection2, 'Resources', 'purposesGeneral');
    $purposesRestricted = ($highestAction == 'Manage Resources_all')? getSettingByScope($connection2, 'Resources', 'purposesRestricted') : '';
    $col = $row->addColumn();
        $col->addLabel('purpose'.$id, __('Purpose'));
        $col->addSelect('purpose'.$id)->fromString($purposesGeneral)->fromString($purposesRestricted)->placeholder()->setClass('mediumWidth')->selected($purpose);

    $col = $row->addColumn();
        $col->addLabel('gibbonYearGroupID'.$id, __('Year Groups'));
        $col->addSelectYearGroup('gibbonYearGroupID'.$id)->placeholder()->setClass('mediumWidth')->selected($gibbonYearGroupID);

    $row = $form->addRow();

    $sql = "SELECT tag as value, CONCAT(tag, ' <i>(', count, ')</i>') as name FROM gibbonResourceTag WHERE count>0 ORDER BY tag";
    $col = $row->addColumn()->addClass('inline');
        $col->addLabel('tags'.$id, __('Tags'));
        $col->addFinder('tags'.$id)
            ->fromQuery($pdo, $sql)
            ->setParameter('hintText', __('Type a tag...'))
            ->addClass('floatNone')
            ->selected($tags);
    
    $col->addSubmit(__('Go'));
    
    $output .= $form->getOutput();
    $output .= '<br/>';

	//Search with filters applied
	try {
		$data = array();
		$sqlWhere = 'WHERE ';
		if ($tags != '') {
            $tagArray = explode(',', $tags);
			foreach ($tagArray as $tagCount => $atag) {
				$data['tag'.$tagCount] = '%'.$atag.'%';
				$sqlWhere .= 'tags LIKE :tag'.$tagCount.' AND ';
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
			$data['gibbonYearGroupID'] = "%$gibbonYearGroupID%";
			$sqlWhere .= 'gibbonYearGroupIDList LIKE :gibbonYearGroupID AND ';
		}
		if ($sqlWhere == 'WHERE ') {
			$sqlWhere = '';
		} else {
			$sqlWhere = substr($sqlWhere, 0, -5);
		}

		$sql = "SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) $sqlWhere ORDER BY timestamp DESC LIMIT 50";

		$result = $connection2->prepare($sql);
		$result->execute($data);
	} catch (PDOException $e) {
		echo "<div class='error'>".$e->getMessage().'</div>';
	}

    if ($result->rowCount() < 1) {
        $output .= "<div class='error'>";
        $output .= __($guid, 'There are no records to display.');
        $output .= '</div>';
    } else {
        $output .= "<table cellspacing='0' style='width: 100%'>";
        $output .= "<tr class='head'>";
        $output .= '<th>';
        $output .= __($guid, 'Name').'<br/>';
        $output .= "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Contributor').'</span>';
        $output .= '</th>';
        $output .= '<th>';
        $output .= __($guid, 'Type');
        $output .= '</th>';
        $output .= '<th>';
        $output .= __($guid, 'Category').'<br/>';
        $output .= "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Purpose').'</span>';
        $output .= '</th>';
        $output .= '<th>';
        $output .= __($guid, 'Tags');
        $output .= '</th>';
        $output .= '<th>';
        $output .= __($guid, 'Year Groups');
        $output .= '</th>';
        $output .= '<th>';
        $output .= __($guid, 'Insert');
        $output .= '</th>';
        $output .= '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

			//COLOR ROW BY STATUS!
			$output .= "<tr class=$rowNum>";
            $output .= '<td>';
            if ($row['type'] == 'Link') {
                $output .= "<a target='_blank' style='font-weight: bold' href='".$row['content']."'>".$row['name'].'</a><br/>';
            } elseif ($row['type'] == 'File') {
                $output .= "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['content']."'>".$row['name'].'</a><br/>';
            } elseif ($row['type'] == 'HTML') {
                $output .= "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/modules/Planner/resources_view_standalone.php?gibbonResourceID='.$row['gibbonResourceID']."'>".$row['name'].'</a><br/>';
            }
            $output .= "<span style='font-size: 85%; font-style: italic'>".formatName($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</span>';
            $output .= '</td>';
            $output .= '<td>';
            $output .= $row['type'];
            $output .= '</td>';
            $output .= '<td>';
            $output .= '<b>'.$row['category'].'</b><br/>';
            $output .= "<span style='font-size: 85%; font-style: italic'>".$row['purpose'].'</span>';
            $output .= '</td>';
            $output .= '<td>';
            $tagoutput = '';
            $tags = explode(',', $row['tags']);
            natcasesort($tags);
            foreach ($tags as $tag) {
                $tagoutput .= trim($tag).'<br/>';
            }
            $output .= substr($tagoutput, 0, -2);
            $output .= '</td>';
            $output .= '<td>';
            try {
                $dataYears = array();
                $sqlYears = 'SELECT gibbonYearGroupID, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber';
                $resultYears = $connection2->prepare($sqlYears);
                $resultYears->execute($dataYears);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            $years = explode(',', $row['gibbonYearGroupIDList']);
            $sqlWhere = '';
            if (count($years) > 0 and $years[0] != '') {
                if (count($years) == $resultYears->rowCount()) {
                    $output .= '<i>All Years</i>';
                } else {
                    $count3 = 0;
                    $count4 = 0;
                    while ($rowYears = $resultYears->fetch()) {
                        for ($i = 0; $i < count($years); ++$i) {
                            if ($rowYears['gibbonYearGroupID'] == $years[$i]) {
                                if ($count3 > 0 and $count4 > 0) {
                                    $output .= ', ';
                                }
                                $output .= $rowYears['nameShort'];
                                ++$count4;
                            }
                        }
                        ++$count3;
                    }
                }
            } else {
                $output .= '<i>'.__($guid, 'None').'</i>';
            }
            $output .= '</td>';
            $output .= '<td>';
            $html = '';
            $extension = '';
            if ($row['type'] == 'Link') {
                $extension = strrchr($row['content'], '.');
                if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) {
                    $html = "<a target='_blank' style='font-weight: bold' href='".$row['content']."'><img class='resource' style='max-width: 500px' src='".$row['content']."'></a>";
                } else {
                    $html = "<a target='_blank' style='font-weight: bold' href='".$row['content']."'>".$row['name'].'</a>';
                }
            } elseif ($row['type'] == 'File') {
                $extension = strrchr($row['content'], '.');
                if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) {
                    $html = "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['content']."'><img class='resource' style='max-width: 500px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$row['content']."'></a>";
                } else {
                    $html = "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['content']."'>".$row['name'].'</a>';
                }
            } elseif ($row['type'] == 'HTML') {
                $html = $row['content'];
            }
            $output .= "<a href='javascript:void(0)' onclick='tinymce.execCommand(\"mceFocus\",false,\"$id\"); tinyMCE.execCommand(\"mceInsertContent\", 0, \"".htmlPrep(addslashes($html)).'"); formResetSearch(); $(".'.$id."resourceSlider\").slideUp();'><img title='".__($guid, 'Insert')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
            $output .= '</td>';
            $output .= '</tr>';
        }
        $output .= '</table>';
    }
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '</table>';
}

echo $output;
