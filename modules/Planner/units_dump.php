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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_dump.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . _('Manage Units') . "</a> > </div><div class='trailEnd'>" . _('Dump Unit') . "</div>" ;
		print "</div>" ;
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Units_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
				}
				else if ($highestAction=="Manage Units_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonDepartment.gibbonDepartmentID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
	
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$yearName=$row["name"] ;
				$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
				
				//Check if unit specified
				if ($gibbonUnitID=="") {
					print "<div class='error'>" ;
						print _("You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					if ($gibbonUnitID=="") {
						print "<div class='error'>" ;
							print _("You have not specified one or more required parameters.") ;
						print "</div>" ;
					}
					else {
						try {
							$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
							$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($result->rowCount()!=1) {
							print "<div class='error'>" ;
								print _("The specified record cannot be found.") ;
							print "</div>" ;
						}
						else {
							//Let's go!
							$row=$result->fetch() ;
							
							print "<p>" ;
							print sprintf(_('This page allows you to view all of the content of a selected unit (%1$s). If you wish to take this unit out of Gibbon, simply copy and paste the contents into a word processing application.'), "<b><u>" . $row["courseName"] . " - " . $row["name"] . "</u></b>") ;
							print "</p>" ;
							
							?>
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
	
							print "<div id='tabs' style='margin: 20px 0'>" ;
								//Prep classes in this unit
								try {
									$dataClass=array("gibbonUnitID"=>$gibbonUnitID); 
									$sqlClass="SELECT gibbonUnitClass.gibbonCourseClassID, gibbonCourseClass.nameShort FROM gibbonUnitClass JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonUnitID=:gibbonUnitID ORDER BY nameShort" ; 
									$resultClass=$connection2->prepare($sqlClass);
									$resultClass->execute($dataClass);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								//Tab links
								print "<ul>" ;
									print "<li><a href='#tabs1'>" . _('Unit Overview') . "</a></li>" ;
									print "<li><a href='#tabs2'>" . _('Smart Blocks') . "</a></li>" ;
									print "<li><a href='#tabs3'>" . _('Resources') . "</a></li>" ;
									print "<li><a href='#tabs4'>" . _('Outcomes') . "</a></li>" ;
									$classes=array() ;
									$classCount=0 ;
									while ($rowClass=$resultClass->fetch()) {
										print "<li><a href='#tabs" . ($classCount+5) . "'>" . $row["courseName"] . "." . $rowClass["nameShort"] . "</a></li>" ;
										$classes[$classCount][0]=$rowClass["nameShort"] ;
										$classes[$classCount][1]=$rowClass["gibbonCourseClassID"] ;
										$classCount++ ;
									}
								print "</ul>" ;
							
								//Tabs
								print "<div id='tabs1'>" ;
									if ($row["details"]=="") {
										print "<div class='error'>" ;
											print _("There are no records to display.") ;
										print "</div>" ;
									}
									else {
										print "<p>" ;
											print $row["details"] ;
										print "</p>" ;
									}
								print "</div>" ;
								print "<div id='tabs2'>" ;
									try {
										$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID); 
										$sqlBlocks="SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber" ; 
										$resultBlocks=$connection2->prepare($sqlBlocks );
										$resultBlocks->execute($dataBlocks);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									$resourceContents="" ;
							
									while ($rowBlocks=$resultBlocks->fetch()) {
										if ($rowBlocks["title"]!="" OR $rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
											print "<div class='blockView' style='min-height: 35px'>" ;
												if ($rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
													$width="69%" ;
												}
												else {
													$width="100%" ;
												}
												print "<div style='padding-left: 3px; width: $width; float: left;'>" ;
													if ($rowBlocks["title"]!="") {
														print "<h5 style='padding-bottom: 2px'>" . $rowBlocks["title"] . "</h5>" ;
													}
												print "</div>" ;
												if ($rowBlocks["type"]!="" OR $rowBlocks["length"]!="") {
													print "<div style='float: right; width: 29%; padding-right: 3px; height: 55px'>" ;
														print "<div style='text-align: right; font-size: 85%; font-style: italic; margin-top: 12px; border-bottom: 1px solid #ddd; height: 21px'>" ; 
															if ($rowBlocks["type"]!="") {
																print $rowBlocks["type"] ;
																if ($rowBlocks["length"]!="") {
																	print " | " ;
																}
															}
															if ($rowBlocks["length"]!="") {
																print $rowBlocks["length"] . " min" ;
															}
														print "</div>" ;
													print "</div>" ;
												}
											print "</div>" ;
										}
										if ($rowBlocks["contents"]!="") {
											print "<div style='padding: 15px 3px 10px 3px; width: 100%; text-align: justify; border-bottom: 1px solid #ddd'>" . $rowBlocks["contents"] . "</div>" ;
											$resourceContents.=$rowBlocks["contents"] ;
										}
										if ($rowBlocks["teachersNotes"]!="") {
											print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . _("Teacher's Notes") . ":</b></p> " . $rowBlocks["teachersNotes"] . "</div>" ;
											$resourceContents.=$rowBlocks["teachersNotes"] ;
										}
									}
									
								print "</div>" ;
								print "<div id='tabs3'>" ;
									//Resources
									$noReosurces=TRUE ;
									
									//Links
									$links="" ;
									$linksArray=array() ;
									$linksCount=0;
									$dom=new DOMDocument;
									$dom->loadHTML($resourceContents);
									foreach ($dom->getElementsByTagName('a') as $node) {
										if ($node->nodeValue!="") {
											$linksArray[$linksCount]="<li><a href='" .$node->getAttribute("href") . "'>" . $node->nodeValue . "</a></li>" ;
											$linksCount++ ;
										}
									}
									
									$linksArray=array_unique($linksArray) ;
									natcasesort($linksArray) ;
									
									foreach ($linksArray AS $link) {
										$links.=$link ;
									}
									
									if ($links!="" ) {
										print "<h2>" ;
											print "Links" ;
										print "</h2>" ;
										print "<ul>" ;
											print $links ;
										print "</ul>" ;
										$noReosurces=FALSE ;
									}
									
									//Images
									$images="" ;
									$imagesArray=array() ;
									$imagesCount=0;
									$dom2=new DOMDocument;
									$dom2->loadHTML($resourceContents);
									foreach ($dom2->getElementsByTagName('img') as $node) {
										if ($node->getAttribute("src")!="") {
											$imagesArray[$imagesCount]="<img class='resource' style='margin: 10px 0; max-width: 560px' src='" . $node->getAttribute("src") . "'/><br/>" ;
											$imagesCount++ ;
										}
									}
									
									$imagesArray=array_unique($imagesArray) ;
									natcasesort($imagesArray) ;
									
									foreach ($imagesArray AS $image) {
										$images.=$image ;
									}
									
									if ($images!="" ) {
										print "<h2>" ;
											print "Images" ;
										print "</h2>" ;
										print $images ;
										$noReosurces=FALSE ;
									}
									
									//Embeds
									$embeds="" ;
									$embedsArray=array() ;
									$embedsCount=0;
									$dom2=new DOMDocument;
									$dom2->loadHTML($resourceContents);
									foreach ($dom2->getElementsByTagName('iframe') as $node) {
										if ($node->getAttribute("src")!="") {
											$embedsArray[$embedsCount]="<iframe style='max-width: 560px' width='" . $node->getAttribute("width") . "' height='" . $node->getAttribute("height") . "' src='" . $node->getAttribute("src") . "' frameborder='" . $node->getAttribute("frameborder") . "'></iframe>" ;
											$embedsCount++ ;
										}
									}
									
									$embedsArray=array_unique($embedsArray) ;
									natcasesort($embedsArray) ;
									
									foreach ($embedsArray AS $embed) {
										$embeds.=$embed ."<br/><br/>" ;
									}
									
									if ($embeds!="" ) {
										print "<h2>" ;
											print "Embeds" ;
										print "</h2>" ;
										print $embeds ;
										$noReosurces=FALSE ;
									}
									
									//No resources!
									if ($noReosurces) {
										print "<div class='error'>" ;
											print _("There are no records to display.") ;
										print "</div>" ;
									}
								print "</div>" ;
								print "<div id='tabs4'>" ;
									//Spit out outcomes
									try {
										$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID);  
										$sqlBlocks="SELECT gibbonUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber" ;
										$resultBlocks=$connection2->prepare($sqlBlocks);
										$resultBlocks->execute($dataBlocks);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultBlocks->rowCount()>0) {
										print "<table cellspacing='0' style='width: 100%'>" ;
											print "<tr class='head'>" ;
												print "<th>" ;
													print _("Scope") ;
												print "</th>" ;
												print "<th>" ;
													print _("Category") ;
												print "</th>" ;
												print "<th>" ;
													print _("Name") ;
												print "</th>" ;
												print "<th>" ;
													print _("Year Groups") ;
												print "</th>" ;
												print "<th>" ;
													print _("Actions") ;
												print "</th>" ;
											print "</tr>" ;
								
											$count=0;
											$rowNum="odd" ;
											while ($rowBlocks=$resultBlocks->fetch()) {
												if ($count%2==0) {
													$rowNum="even" ;
												}
												else {
													$rowNum="odd" ;
												}
									
												//COLOR ROW BY STATUS!
												print "<tr class=$rowNum>" ;
													print "<td>" ;
														print "<b>" . $rowBlocks["scope"] . "</b><br/>" ;
														if ($rowBlocks["scope"]=="Learning Area" AND $gibbonDepartmentID!="") {
															try {
																$dataLearningArea=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
																$sqlLearningArea="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
																$resultLearningArea=$connection2->prepare($sqlLearningArea);
																$resultLearningArea->execute($dataLearningArea);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															if ($resultLearningArea->rowCount()==1) {
																$rowLearningAreas=$resultLearningArea->fetch() ;
																print "<span style='font-size: 75%; font-style: italic'>" . $rowLearningAreas["name"] . "</span>" ;
															}
														}
													print "</td>" ;
													print "<td>" ;
														print "<b>" . $rowBlocks["category"] . "</b><br/>" ;
													print "</td>" ;
													print "<td>" ;
														print "<b>" . $rowBlocks["nameShort"] . "</b><br/>" ;
														print "<span style='font-size: 75%; font-style: italic'>" . $rowBlocks["name"] . "</span>" ;
													print "</td>" ;
													print "<td>" ;
														print getYearGroupsFromIDList($connection2, $rowBlocks["gibbonYearGroupIDList"]) ;
													print "</td>" ;
													print "<td>" ;
														print "<script type='text/javascript'>" ;	
															print "$(document).ready(function(){" ;
																print "\$(\".description-$count\").hide();" ;
																print "\$(\".show_hide-$count\").fadeIn(1000);" ;
																print "\$(\".show_hide-$count\").click(function(){" ;
																print "\$(\".description-$count\").fadeToggle(1000);" ;
																print "});" ;
															print "});" ;
														print "</script>" ;
														if ($rowBlocks["content"]!="") {
															print "<a title='" . _('View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-left: 0px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
														}
													print "</td>" ;
												print "</tr>" ;
												if ($rowBlocks["content"]!="") {
													print "<tr class='description-$count' id='description-$count'>" ;
														print "<td colspan=6>" ;
															print $rowBlocks["content"] ;
														print "</td>" ;
													print "</tr>" ;
												}
												print "</tr>" ;
									
												$count++ ;
											}
										print "</table>" ;
									}
									
								print "</div>" ;
								$classCount=0 ;
								foreach($classes AS $class) {
									print "<div id='tabs" . ($classCount+5) . "'>" ;
										
										//Print Lessons
										print "<h2>" . _("Lessons") . "</h2>" ;
										try {
											$dataLessons=array("gibbonCourseClassID"=>$class[1], "gibbonUnitID"=>$gibbonUnitID); 
											$sqlLessons="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID" ;
											$resultLessons=$connection2->prepare($sqlLessons) ;
											$resultLessons->execute($dataLessons) ;
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ;
										}	
								
										if ($resultLessons->rowCount()<1) {
											print "<div class='warning'>" ;
											print _("There are no records to display.") ;
											print "</div>" ;
										}
										else {
											while ($rowLessons=$resultLessons->fetch()) {
												print "<h3>" . $rowLessons["name"] . "</h3>" ;
												print $rowLessons["description"] . "<br/>" ;
												if ($rowLessons["teachersNotes"]!="") {
													print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . _("Teacher's Notes") . ":</b></p> " . $rowLessons["teachersNotes"] . "</div>" ;
												}
												
												try {
													$dataBlock=array("gibbonPlannerEntryID"=>$rowLessons["gibbonPlannerEntryID"]); 
													$sqlBlock="SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID ORDER BY sequenceNumber" ; 
													$resultBlock=$connection2->prepare($sqlBlock);
													$resultBlock->execute($dataBlock);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
						
												while ($rowBlock=$resultBlock->fetch()) {
													print "<h5 style='font-size: 85%'>" . $rowBlock["title"] . "</h5>" ;
													print "<p>" ;
													print "<b>" . _('Type') . "</b>: " . $rowBlock["type"] . "<br/>" ;
													print "<b>" . _('Length') . "</b>: " . $rowBlock["length"] . "<br/>" ;
													print "<b>" . _('Contents') . "</b>: " . $rowBlock["contents"] . "<br/>" ;
													if ($rowBlock["teachersNotes"]!="") {
														print "<div style='background-color: #F6CECB; padding: 0px 3px 10px 3px; width: 98%; text-align: justify; border-bottom: 1px solid #ddd'><p style='margin-bottom: 0px'><b>" . _("Teacher's Notes") . ":</b></p> " . $rowBlock["teachersNotes"] . "</div>" ;
													}
													print "</p>" ;
												}
												
												//Print chats
												print "<h5 style='font-size: 85%'>" . _("Chat") . "</h5>" ;
												print getThread($guid, $connection2, $rowLessons["gibbonPlannerEntryID"], NULL, 0, NULL, NULL, NULL, NULL, NULL, $class[1], $_SESSION[$guid]["gibbonPersonID"], "Teacher", FALSE) ;
											}
										}
									print "</div>" ;
									$classCount++ ;
								}
							print "</div>" ;
						
						}
					}
				}
			}
		}
	} 
}		
?>