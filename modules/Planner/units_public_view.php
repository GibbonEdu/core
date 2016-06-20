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

$makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
if ($makeUnitsPublic != 'Y') {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/units_public.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Learn With Us')."</a> > </div><div class='trailEnd'>".__($guid, 'View Unit').'</div>';
    echo '</div>';

    //Check if courseschool year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonUnitID = $_GET['gibbonUnitID'];
    if ($gibbonUnitID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonUnitID' => $gibbonUnitID);
            $sql = "SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND sharedPublic='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch(); ?>
			<script type='text/javascript'>
				$(function() {
					$( "#tabs" ).tabs({
						ajaxOptions: {
							error: function( xhr, status, index, anchor ) {
								$( anchor.hash ).html(
									"Couldn't load this tab." );
							}
						}
					});
				});
			</script>
			
			<?php
            echo '<h2>';
            echo $row['name'];
            echo '</h2>';

            echo "<div id='tabs' style='width: 100%; margin: 20px 0'>";
                //Prep classes in this unit
                try {
                    $dataClass = array('gibbonUnitID' => $gibbonUnitID);
                    $sqlClass = 'SELECT gibbonUnitClass.gibbonCourseClassID, gibbonCourseClass.nameShort FROM gibbonUnitClass JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID ORDER BY nameShort';
                    $resultClass = $connection2->prepare($sqlClass);
                    $resultClass->execute($dataClass);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                //Tab links
                echo '<ul>';
            echo "<li><a href='#tabs1'>".__($guid, 'Overview').'</a></li>';
            echo "<li><a href='#tabs2'>".__($guid, 'Content').'</a></li>';
            echo "<li><a href='#tabs3'>".__($guid, 'Resources').'</a></li>';
            echo "<li><a href='#tabs4'>".__($guid, 'Outcomes').'</a></li>';
            echo '</ul>';

                //Tabs
                echo "<div id='tabs1'>";
            echo '<h4>';
            echo __($guid, 'Description');
            echo '</h4>';
            if ($row['description'] == '') {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo '<p>';
                echo $row['description'];
                echo '</p>';
            }

            if ($row['license'] != '') {
                echo '<h4>';
                echo __($guid, 'License');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'This work is shared under the following license:').' '.$row['license'];
                echo '</p>';
            }
            echo '</div>';
            echo "<div id='tabs2'>";
            try {
                $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                $resultBlocks = $connection2->prepare($sqlBlocks);
                $resultBlocks->execute($dataBlocks);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            $resourceContents = '';

            while ($rowBlocks = $resultBlocks->fetch()) {
                if ($rowBlocks['title'] != '' or $rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                    echo '<hr/>';
                    echo "<div class='blockView' style='min-height: 35px'>";
                    if ($rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                        $width = '69%';
                    } else {
                        $width = '100%';
                    }
                    echo "<div style='padding-left: 3px; width: $width; float: left;'>";
                    if ($rowBlocks['title'] != '') {
                        echo "<h5 style='padding-bottom: 2px'>".$rowBlocks['title'].'</h5>';
                    }
                    echo '</div>';
                    if ($rowBlocks['type'] != '' or $rowBlocks['length'] != '') {
                        echo "<div style='float: right; width: 29%; padding-right: 3px; height: 25px'>";
                        echo "<div style='text-align: right; font-size: 75%; font-style: italic; margin-top: 5px; border-bottom: 1px solid #ddd; height: 21px'>";
                        if ($rowBlocks['type'] != '') {
                            echo $rowBlocks['type'];
                            if ($rowBlocks['length'] != '') {
                                echo ' | ';
                            }
                        }
                        if ($rowBlocks['length'] != '') {
                            echo $rowBlocks['length'].' min';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                if ($rowBlocks['contents'] != '') {
                    echo "<div style='padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>".$rowBlocks['contents'].'</div>';
                    $resourceContents .= $rowBlocks['contents'];
                }
            }
            echo '</div>';
            echo "<div id='tabs3'>";
			//Resources
			$noReosurces = true;

			//Links
			$links = '';
            $linksArray = array();
            $linksCount = 0;
            $dom = new DOMDocument();
            $dom->loadHTML($resourceContents);
            foreach ($dom->getElementsByTagName('a') as $node) {
                if ($node->nodeValue != '') {
                    $linksArray[$linksCount] = "<li><a href='".$node->getAttribute('href')."'>".$node->nodeValue.'</a></li>';
                    ++$linksCount;
                }
            }

            $linksArray = array_unique($linksArray);
            natcasesort($linksArray);

            foreach ($linksArray as $link) {
                $links .= $link;
            }

            if ($links != '') {
                echo '<h2>';
                echo 'Links';
                echo '</h2>';
                echo '<ul>';
                echo $links;
                echo '</ul>';
                $noReosurces = false;
            }

			//Images
			$images = '';
            $imagesArray = array();
            $imagesCount = 0;
            $dom2 = new DOMDocument();
            $dom2->loadHTML($resourceContents);
            foreach ($dom2->getElementsByTagName('img') as $node) {
                if ($node->getAttribute('src') != '') {
                    $imagesArray[$imagesCount] = "<img class='resource' style='margin: 10px 0; max-width: 560px' src='".$node->getAttribute('src')."'/><br/>";
                    ++$imagesCount;
                }
            }

            $imagesArray = array_unique($imagesArray);
            natcasesort($imagesArray);

            foreach ($imagesArray as $image) {
                $images .= $image;
            }

            if ($images != '') {
                echo '<h2>';
                echo 'Images';
                echo '</h2>';
                echo $images;
                $noReosurces = false;
            }

			//Embeds
			$embeds = '';
            $embedsArray = array();
            $embedsCount = 0;
            $dom2 = new DOMDocument();
            $dom2->loadHTML($resourceContents);
            foreach ($dom2->getElementsByTagName('iframe') as $node) {
                if ($node->getAttribute('src') != '') {
                    $embedsArray[$embedsCount] = "<iframe style='max-width: 560px' width='".$node->getAttribute('width')."' height='".$node->getAttribute('height')."' src='".$node->getAttribute('src')."' frameborder='".$node->getAttribute('frameborder')."'></iframe>";
                    ++$embedsCount;
                }
            }

            $embedsArray = array_unique($embedsArray);
            natcasesort($embedsArray);

            foreach ($embedsArray as $embed) {
                $embeds .= $embed.'<br/><br/>';
            }

            if ($embeds != '') {
                echo '<h2>';
                echo 'Embeds';
                echo '</h2>';
                echo $embeds;
                $noReosurces = false;
            }

			//No resources!
			if ($noReosurces) {
				echo "<div class='error'>";
				echo __($guid, 'There are no records to display.');
				echo '</div>';
			}
            echo '</div>';
            echo "<div id='tabs4'>";
				//Spit out outcomes
				try {
					$dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
					$sqlBlocks = "SELECT gibbonUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
					$resultBlocks = $connection2->prepare($sqlBlocks);
					$resultBlocks->execute($dataBlocks);
				} catch (PDOException $e) {
					echo "<div class='error'>".$e->getMessage().'</div>';
				}
            if ($resultBlocks->rowCount() > 0) {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Scope');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Category');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Name');
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
                while ($rowBlocks = $resultBlocks->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

					//COLOR ROW BY STATUS!
					echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo '<b>'.$rowBlocks['scope'].'</b><br/>';
                    echo '</td>';
                    echo '<td>';
                    echo '<b>'.$rowBlocks['category'].'</b><br/>';
                    echo '</td>';
                    echo '<td>';
                    echo '<b>'.$rowBlocks['nameShort'].'</b><br/>';
                    echo "<span style='font-size: 75%; font-style: italic'>".$rowBlocks['name'].'</span>';
                    echo '</td>';
                    echo '<td>';
                    echo getYearGroupsFromIDList($guid, $connection2, $rowBlocks['gibbonYearGroupIDList']);
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
                    if ($rowBlocks['content'] != '') {
                        echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                    }
                    echo '</td>';
                    echo '</tr>';
                    if ($rowBlocks['content'] != '') {
                        echo "<tr class='description-$count' id='description-$count'>";
                        echo '<td colspan=6>';
                        echo $rowBlocks['content'];
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tr>';

                    ++$count;
                }
                echo '</table>';
            }

            echo '</div>';
            echo '</div>';
        }
    }
}
?>