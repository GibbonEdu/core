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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Resources/resources_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Resources').'</div>';
    echo '</div>';

    echo '<h3>';
    echo __($guid, 'Filters');
    echo '</h3>';

    //Get current filter values
    $tags = null;
    if (isset($_POST['tag'])) {
        $tags = trim($_POST['tag']);
    } elseif (isset($_GET['tag'])) {
        $tags = trim($_GET['tag']);
    }
    $category = null;
    if (isset($_POST['category'])) {
        $category = trim($_POST['category']);
    } elseif (isset($_GET['category'])) {
        $category = trim($_GET['category']);
    }
    $purpose = null;
    if (isset($_POST['purpose'])) {
        $purpose = trim($_POST['purpose']);
    } elseif (isset($_GET['purpose'])) {
        $purpose = trim($_GET['purpose']);
    }
    $gibbonYearGroupID = null;
    if (isset($_POST['gibbonYearGroupID'])) {
        $gibbonYearGroupID = $_POST['gibbonYearGroupID'];
    } elseif (isset($_GET['gibbonYearGroupID'])) {
        $gibbonYearGroupID = trim($_GET['gibbonYearGroupID']);
    }

    //Display filters
    echo "<form method='post'>";
    echo "<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
    echo '<tr>';
    echo '<td>';
    echo '<b>'.__($guid, 'Tags').'</b>';
    echo '</td>';
    echo "<td style='padding: 0px 2px 0px 0px'>";
	//Tag selector
	try {
		$dataList = array();
		$sqlList = 'SELECT * FROM gibbonResourceTag WHERE count>0 ORDER BY tag';
		$resultList = $connection2->prepare($sqlList);
		$resultList->execute($dataList);
	} catch (PDOException $e) {
		echo "<div class='error'>".$e->getMessage().'</div>';
	}

    $list = '';
    while ($rowList = $resultList->fetch()) {
        $list = $list.'{id: "'.addslashes($rowList['tag']).'", name: "'.addslashes($rowList['tag']).' <i>('.$rowList['count'].')</i>"},';
    }
    ?>
	<style>
		ul.token-input-list-facebook { width: 300px; height: 25px!important; float: right }
		div.token-input-dropdown-facebook { width: 300px }
	</style>
	<input type="text" id="tag" name="tag" />
	<script type="text/javascript">
		$(document).ready(function() {
			 $("#tag").tokenInput([
				<?php echo substr($list, 0, -1) ?>
			],
				{theme: "facebook",
				hintText: "Type a tag...",
				allowCreation: false,
				preventDuplicates: true,
				<?php
				$tagString = '';
				if ($tags != '') {
					$tagList = explode(',', $tags);
					foreach ($tagList as $tag) {
						$tagString .= "{id: '".addslashes($tag)."', name: '".addslashes($tag)."'},";
					}
				}
				echo 'prePopulate: ['.substr($tagString, 0, -1).'],';?>
				tokenLimit: null});
				});
			</script>
			<?php
		echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<b>'.__($guid, 'Category').'</b>';
    echo '</td>';
    echo "<td style='padding: 0px 2px 0px 0px'>";
    try {
        $dataCategory = array();
        $sqlCategory = "SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='categories'";
        $resultCategory = $connection2->prepare($sqlCategory);
        $resultCategory->execute($dataCategory);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    echo "<select name='category' id='category' style='width:302px'>";
    echo "<option value=''></option>";
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
                echo "<option $selected value='".trim($options[$i])."'>".trim($options[$i]).'</option>';
            }
        }
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<b>'.__($guid, 'Purpose').'</b>';
    echo '</td>';
    echo "<td style='padding: 0px 2px 0px 0px'>";
    try {
        $dataPurpose = array();
        $sqlPurpose = "(SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesGeneral') UNION (SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesRestricted')";
        $resultPurpose = $connection2->prepare($sqlPurpose);
        $resultPurpose->execute($dataPurpose);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
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
    echo "<select name='purpose' id='purpose' style='width:302px'>";
    echo "<option value=''></option>";
    for ($i = 0; $i < count($options); ++$i) {
        $selected = '';
        if (trim($options[$i]) == $purpose) {
            $selected = 'selected';
        }
        echo "<option $selected value='".trim($options[$i])."'>".trim($options[$i]).'</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>';
    echo '<b>'.__($guid, 'Year Group').'</b>';
    echo '</td>';
    echo "<td style='padding: 0px 2px 0px 0px'>";
    try {
        $dataPurpose = array();
        $sqlPurpose = 'SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber';
        $resultPurpose = $connection2->prepare($sqlPurpose);
        $resultPurpose->execute($dataPurpose);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    echo "<select name='gibbonYearGroupID' id='gibbonYearGroupID' style='width:302px'>";
    echo "<option value=''></option>";
    while ($rowPurpose = $resultPurpose->fetch()) {
        $selected = '';
        if ($rowPurpose['gibbonYearGroupID'] == $gibbonYearGroupID) {
            $selected = 'selected';
        }
        echo "<option $selected value='".$rowPurpose['gibbonYearGroupID']."'>".__($guid, $rowPurpose['name']).'</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo "<td class='right' colspan=2>";
    echo "<input type='hidden' name='q' value='/modules/Resources/resources_view.php'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Resources/resources_view.php'>".__($guid, 'Clear Filters').'</a> ';
    echo "<input style='height: 27px; width: 20px!important; margin: 0px;' type='submit' value='".__($guid, 'Go')."'>";
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    //Set pagination variable
    $page = null;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    echo '<h3>';
    echo __($guid, 'View');
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
    echo " <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/resources_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "&tags=$tags&category=$category&purpose=$purpose&gibbonYearGroupID=$gibbonYearGroupID");
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
            echo '<td>';
            $output = '';
            $tags = explode(',', $row['tags']);
            natcasesort($tags);
            foreach ($tags as $tag) {
                $output .= trim($tag).'<br/>';
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
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "&tags=$tags&category=$category&purpose=$purpose&gibbonYearGroupID=$gibbonYearGroupID");
        }
    }

    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2);
}
?>
