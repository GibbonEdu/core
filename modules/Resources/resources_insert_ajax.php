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

//Gibbon system-wide includes
include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Setup variables
$output = '';
$id = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
}
$category = null;
if (isset($_GET['category'])) {
    $category = $_GET['category'];
}
if (isset($_POST['category'.$id])) {
    $category = $_POST['category'.$id];
}
$purpose = null;
if (isset($_GET['purpose'])) {
    $purpose = $_GET['purpose'];
}
if (isset($_POST['purpose'.$id])) {
    $purpose = $_POST['purpose'.$id];
}
$tags = null;
if (isset($_GET['tags'])) {
    $tags = $_GET['tags'];
}
if (isset($_POST['tag'.$id])) {
    $tags = $_POST['tag'.$id];
}
$gibbonYearGroupID = null;
if (isset($_GET['gibbonYearGroupID'])) {
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
}
if (isset($_POST['gibbonYearGroupID'.$id])) {
    $gibbonYearGroupID = $_POST['gibbonYearGroupID'.$id];
}
$alpha = null;
if (isset($_GET['alpha'])) {
    $alpha = $_GET['alpha'];
}

if (isActionAccessible($guid, $connection2, '/modules/Resources/resources_view.php') == false) {
    //Acess denied
    $output .= "<div class='error'>";
    $output .= __($guid, 'Your request failed because you do not have access to this action.');
    $output .= '</div>';
} else {
    $output .= "<script type='text/javascript'>";
    $output .= '$(document).ready(function() {';
    $output .= 'var optionsSearch={';
    $output .= 'target: $(".'.$id.'resourceSlider"),';
    $output .= "url: '".$_SESSION[$guid]['absoluteURL']."/modules/Resources/resources_insert_ajax.php?id=$id',";
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

    $output .= "<table cellspacing='0' style='width: 100%'>";
    $output .= "<tr><td style='width: 30%; height: 1px; padding-top: 0px; padding-bottom: 0px'></td><td style='padding-top: 0px; padding-bottom: 0px'></td></tr>";
    $output .= "<tr id='".$id."resourceInsert'>";
    $output .= "<td colspan=2 style='padding-top: 0px'>";
    $output .= "<div style='margin: 0px' class='linkTop'><a href='javascript:void(0)' onclick='formResetSearch(); \$(\".".$id."resourceSlider\").slideUp();'><img title='".__($guid, 'Close')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/></a></div>";
    $output .= "<h3 style='margin-top: 0px; font-size: 140%'>Insert A Resource</h3>";
    $output .= '<p>'.sprintf(__($guid, 'The table below shows shared resources drawn from the %1$sResources%2$s section of Gibbon. You will see the 50 most recent resources that match the filters you have used.'), "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Resources/resources_view.php'>", '</a>').'</p>';
    $output .= "<form id='".$id."ajaxFormSearch' name='".$id."ajaxFormSearch'>";
    $output .= "<table cellspacing='0' style='width: 200px'>";
    $output .= '<tr>';
    $output .= '<td colspan=4>';
    $output .= '<b>'.__($guid, 'Tags').'</b>';
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '<tr>';
    $output .= "<td style='padding: 0px 2px 0px 0px' colspan=4>";
                                //Tag selector
                                try {
                                    $dataList = array();
                                    $sqlList = 'SELECT * FROM gibbonResourceTag WHERE count>0 ORDER BY tag';
                                    $resultList = $connection2->prepare($sqlList);
                                    $resultList->execute($dataList);
                                } catch (PDOException $e) {
                                }
    $list = '';
    while ($rowList = $resultList->fetch()) {
        $list = $list.'{id: "'.$rowList['tag'].'", name: "'.$rowList['tag'].' <i>('.$rowList['count'].')</i>"},';
    }
    $output .= '<style>ul.token-input-list-facebook { margin-left: 2px; width: 275px; height: 25px!important; float: none }</style>';
    $output .= "<input type='text' id='tagSearch".$id."' name='tag".$id."' />";
    $output .= "<script type='text/javascript'>";
    $output .= '$(document).ready(function() {';
    $output .= ' $("#tagSearch'.$id.'").tokenInput([';
    $output .= substr($list, 0, -1);
    $output .= '],';
    $output .= '{theme: "facebook",';
    $output .= 'hintText: "Start typing a tag...",';
    $output .= 'allowCreation: false,';
    $output .= 'preventDuplicates: true,';
    $tagString = '';
    if ($tags != '') {
        $tagList = explode(',', $tags);
        foreach ($tagList as $tag) {
            $tagString .= "{id: '$tag', name: '$tag'},";
        }
    }
    $output .= 'prePopulate: ['.substr($tagString, 0, -1).'],';
    $output .= 'tokenLimit: 1});';
    $output .= '});';
    $output .= '</script>';
    $output .= '</td>';
    $output .= '</tr>';

    $output .= '<tr>';
    $output .= '<td>';
    $output .= '<b>'.__($guid, 'Category').'</b>';
    $output .= '</td>';
    $output .= '<td>';
    $output .= '<b>'.__($guid, 'Purpose').'</b>';
    $output .= '</td>';
    $output .= '<td>';
    $output .= '<b>'.__($guid, 'Year Group').'</b>';
    $output .= '</td>';
    $output .= '<td>';

    $output .= '</td>';
    $output .= '</tr>';
    $output .= '<tr>';
    $output .= "<td style='padding: 0px 2px 0px 0px'>";
    try {
        $dataCategory = array();
        $sqlCategory = "SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='categories'";
        $resultCategory = $connection2->prepare($sqlCategory);
        $resultCategory->execute($dataCategory);
    } catch (PDOException $e) {
    }
    $output .= "<select name='category".$id."' id='category".$id."' style='width:200px; height: 27px; margin-left: 0px'>";
    $output .= "<option value=''></option>";
    if ($resultCategory->rowCount() == 1) {
        $rowCategory = $resultCategory->fetch();
        $options = $rowCategory['value'];
        if ($options != '') {
            $options = explode(',', $options);

            for ($i = 0; $i < count($options); ++$i) {
                $selected = '';
                if (trim($options[$i]) == $category) {
                    $selected = 'selected';
                }
                $output .= "<option $selected value='".trim($options[$i])."'>".trim($options[$i]).'</option>';
            }
        }
    }
    $output .= '</select>';
    $output .= '</td>';
    $output .= "<td style='padding: 0px 2px 0px 0px'>";
    try {
        $dataPurpose = array();
        $sqlPurpose = "(SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesGeneral') UNION (SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesRestricted')";
        $resultPurpose = $connection2->prepare($sqlPurpose);
        $resultPurpose->execute($dataPurpose);
    } catch (PDOException $e) {
    }
    if ($resultPurpose->rowCount() > 0) {
        $options = '';
        while ($rowPurpose = $resultPurpose->fetch()) {
            $options .= $rowPurpose['value'].',';
        }
        $options = substr($options, 0, -1);

        if ($options != '') {
            $options = explode(',', $options);
        }
    }
    $output .= "<select name='purpose".$id."' id='purpose".$id."' style='width:200px; height: 27px; margin-left: 0px'>";
    $output .= "<option value=''></option>";
    for ($i = 0; $i < count($options); ++$i) {
        $selected = '';
        if (trim($options[$i]) == $purpose) {
            $selected = 'selected';
        }
        $output .= "<option $selected value='".trim($options[$i])."'>".trim($options[$i]).'</option>';
    }
    $output .= '</select>';
    $output .= '</td>';
    $output .= "<td style='padding: 0px 2px 0px 0px'>";
    try {
        $dataPurpose = array();
        $sqlPurpose = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber';
        $resultPurpose = $connection2->prepare($sqlPurpose);
        $resultPurpose->execute($dataPurpose);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    $output .= "<select name='gibbonYearGroupID".$id."' id='gibbonYearGroupID".$id."' style='width:220px; height: 27px; margin-left: 0px'>";
    $output .= "<option value=''></option>";
    while ($rowPurpose = $resultPurpose->fetch()) {
        $selected = '';
        if ($rowPurpose['gibbonYearGroupID'] == $gibbonYearGroupID) {
            $selected = 'selected';
        }
        $output .= "<option $selected value='".$rowPurpose['gibbonYearGroupID']."'>".__($guid, $rowPurpose['name']).'</option>';
    }
    $output .= '</select>';
    $output .= '</td>';
    $output .= "<td style='padding: 0px 0px 0px 2px'>";
    $output .= "<input type='submit' value='".__($guid, 'Go')."'>";
    $output .= '</td>';
    $output .= '</tr>';
    $output .= '</table>';
    $output .= '</form>';

                //Search with filters applied
                try {
                    $data = array();
                    $sqlWhere = 'WHERE ';
                    if ($tags != '') {
                        $tagCount = 0;
                        $tagArray = explode(',', $tags);
                        foreach ($tagArray as $atag) {
                            $data['tag'.$tagCount] = "'%".$atag."%'";
                            $sqlWhere .= 'tags LIKE :tag'.$tagCount.' AND ';
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
                        $data['gibbonYearGroupID'] = "%$gibbonYearGroupID%";
                        $sqlWhere .= 'gibbonYearGroupIDList LIKE :gibbonYearGroupID AND ';
                    }
                    if ($sqlWhere == 'WHERE ') {
                        $sqlWhere = '';
                    } else {
                        $sqlWhere = substr($sqlWhere, 0, -5);
                    }
                    if ($alpha != 'true') {
                        $sql = "SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) $sqlWhere ORDER BY timestamp DESC LIMIT 50";
                    } else {
                        $sql = "SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) $sqlWhere ORDER BY name LIMIT 50";
                    }
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
                $output .= "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/modules/Resources/resources_view_standalone.php?gibbonResourceID='.$row['gibbonResourceID']."'>".$row['name'].'</a><br/>';
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
                $tagoutput .= substr(trim($tag), 1, -1).'<br/>';
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
