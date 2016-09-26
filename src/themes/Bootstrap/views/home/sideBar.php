<?php
use Gibbon\core\trans ;

$sidebar = true;
if ((defined('NO_SIDEBAR_MENU') && NO_SIDEBAR_MENU) || $this->session->get('sidebar') === 'false')
	$sidebar = false;
if ($sidebar) 
{
	$preSideBar = '';
	$slider = '';
	$postSideBar = '';
	
	$preSideBar .= $this->renderReturn('default.sidebar.start');
	$preSideBar .= $this->renderReturn('default.sidebar.loginReturn');
	$preSideBar .= $this->renderReturn('default.sidebar.extra.top');

	$preSideBar .= $this->renderReturn('home.login');
	
	
	
	$slider .= $this->getModuleMenu()->setMenu();
	if (! empty($slider))
		$slider .= $this->renderReturn('default.sidebar.pageAnchors');
	
	
	$security = $this->getSecurity();
	

	//Show custom sidebar content on homepage for logged in users
	if ($this->session->isEmpty("address") AND $this->session->notEmpty("username")) {
		if ($this->session->isEmpty("index_customSidebar")) {
			if (is_file(GIBBON_ROOT."/index_customSidebar.php")) {
				$this->session->set("index_customSidebar", file_get_contents(GIBBON_ROOT."/index_customSidebar.php")) ;
			}
			else {
				$this->session->clear("index_customSidebar") ;
			}
		}
		if ($this->session->notEmpty("index_customSidebar")) {
			$postSideBar .= $this->session->get("index_customSidebar") ;
		}
	}

	//Show parent photo uploader
	if ($this->session->isEmpty("address") AND $this->session->notEmpty("username"))
		$postSideBar .= $this->renderReturn('home.parentPhoto');

	//Show homescreen widget for message wall
	if ($this->session->isEmpty("address")) {
		if ( $this->session->notEmpty("messageWallOutput")) {
			if ($this->getSecurity()->isActionAccessible("/modules/Messenger/messageWall_view.php")) {
				$attainmentAlternativeName=$this->config->getSettingByScope("Messenger", "enableHomeScreenWidget") ;
				if ($attainmentAlternativeName=="Y") {
					$postSideBar .= $this->h2('Message Wall', array(), true);

					if (count($this->session->get("messageWallOutput"))<1) {
						$postSideBar .= $this->returnMessage("There are no records to display.", 'warning');
					}
					else if (is_array($this->session->get("messageWallOutput"))==FALSE) {
						$postSideBar .= $this->returnMessage("An error occurred.");
					}
					else {
						$height=283 ;
						if (count($this->session->get("messageWallOutput"))==1) {
							$height=94 ;
						}
						else if (count($this->session->get("messageWallOutput"))==2) {
							$height=197 ;
						}
						$postSideBar .= "<table id='messageWallWidget' style='width: 100%; height: " . $height . "px; border: 1px solid grey; padding: 6px; background-color: #eeeeee'>" ;
							//Content added by JS
							$rand=rand(0, count($this->session->get("messageWallOutput")));
							$total=count($this->session->get("messageWallOutput")) ;
							$order = "";
							for ($i=0; $i < $total; $i++) {
								$pos=($rand+$i)%$total;
								$order.="$pos, ";
								$message=$this->session->get("messageWallOutput.".$pos);

								//COLOR ROW BY STATUS!
								$postSideBar .= "<tr id='messageWall" . $pos . "' style='z-index: 1;'>" ;
									$postSideBar .= "<td style='font-size: 95%; letter-spacing: 85%;'>" ;
										//Image
										$style="style='width: 45px; height: 60px; float: right; margin-left: 6px; border: 1px solid black'" ;
										if ($message["photo"]=="" OR file_exists($this->session->get("absolutePath") . "/" . $message["photo"])==FALSE) {
											$postSideBar .= "<img $style  src='" . $this->session->get("absoluteURL") . "/themes/" . $this->session->get("theme.Name") . "/img/anonymous_75.jpg'/>" ;
										}
										else {
											$postSideBar .= "<img $style src='" . $this->session->get("absoluteURL") . "/" . $message["photo"] . "'/>" ;
										}

										//Message number
										$postSideBar .= "<div style='margin-bottom: 4px; text-transform: uppercase; font-size: 70%; color: #888'>Message " . ($pos+1) . "</div>" ;

										//Title
										$URL=$this->session->get("absoluteURL") . "/index.php?q=/modules/Messenger/messageWall_view.php#" . $message["gibbonMessengerID"] ;
										if (strlen($message["subject"])<=16) {
											$postSideBar .= "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>"  . $message["subject"] . "</a><br/>" ;
										}
										else {
											$postSideBar .= "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>"  . substr($message["subject"], 0, 16) . "...</a><br/>" ;
										}

										//Text
										$postSideBar .= "<div style='margin-top: 5px'>" ;
											$message=preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $message);
											if (strlen(strip_tags($message["details"]))<=40) {
												$postSideBar .= strip_tags($message["details"]) . "<br/>" ;
											}
											else {
												$postSideBar .= substr(strip_tags($message["details"]), 0, 40) . "...<br/>" ;
											}
										$postSideBar .= "</div>" ;
									$postSideBar .= "</td>" ;
								$postSideBar .= "</tr>" ;
								$this->addScript( "
									<script type=\"text/javascript\">
										$(document).ready(function(){
											$(\"#messageWall$pos\").hide();
										});
									</script>" );
							}
						$postSideBar .= "</table>" ;
						$order = substr($order, 0, strlen($order)-2);
						$this->addScript(  "
							<script type=\"text/javascript\">
								$(document).ready(function(){
									var order=[". $order . "];
									var interval = 1;

										for(var i=0; i<order.length; i++) {
											var tRow = $(\"#messageWall\".concat(order[i].toString()));
											if(i<3) {
												tRow.show();
											}
											else {
												tRow.hide();
											}
										}
										$(\"#messageWall\".concat(order[0].toString())).attr('class', 'even');
										$(\"#messageWall\".concat(order[1].toString())).attr('class', 'odd');
										$(\"#messageWall\".concat(order[2].toString())).attr('class', 'even');

									setInterval(function() {
										if(order.length > 3) {
											$(\"#messageWall\".concat(order[0].toString())).hide();
											var fRow = $(\"#messageWall\".concat(order[0].toString()));
											var lRow = $(\"#messageWall\".concat(order[order.length-1].toString()));
											fRow.insertAfter(lRow);
											order.push(order.shift());
											$(\"#messageWall\".concat(order[2].toString())).show();

											if(interval%2===0) {
												$(\"#messageWall\".concat(order[0].toString())).attr('class', 'even');
												$(\"#messageWall\".concat(order[1].toString())).attr('class', 'odd');
												$(\"#messageWall\".concat(order[2].toString())).attr('class', 'even');
											}
											else {
												$(\"#messageWall\".concat(order[0].toString())).attr('class', 'odd');
												$(\"#messageWall\".concat(order[1].toString())).attr('class', 'even');
												$(\"#messageWall\".concat(order[2].toString())).attr('class', 'odd');
											}

											interval++;
										}
									}, 8000);
								});
							</script>" );
					}
					$postSideBar .= "<p style='padding-top: 5px; text-align: right'>" ;
					$postSideBar .= "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Messenger/messageWall_view.php'>" . $this->__('View Message Wall') . "</a>" ;
					$postSideBar .= "</p>" ;

				}
			}
		}
	}

	//Show upcoming deadlines
	if ($this->session->get("address")=="" AND $this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) {
		$highestAction = $security->getHighestGroupedAction("/modules/Planner/planner.php") ;
		if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
			$postSideBar .= $this->h2("Homework & Deadlines", array(), true) ;

			$data=array("gibbonSchoolYearID"=>$this->session->get("gibbonSchoolYearID"), "gibbonPersonID"=>$this->session->get("gibbonPersonID"));
			$sql="(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, 
			gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, 
			viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
			JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
			JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
			 WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' 
			 AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' 
			 AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
			UNION
			(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, 
			gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, 
			gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry 
			JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
			JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
			JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
			JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID 
			AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
			AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' 
			OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' 
			AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')))
			 ORDER BY homeworkDueDateTime, type" ;
			$result=$this->pdo->executeQuery($data, $sql, '{message}');
			if ($result->rowCount()<1) {
				$postSideBar .= $this->returnMessage("No upcoming deadlines. Yay!", 'success') ;
			}
			else {
				$postSideBar .= "<ol>" ;
				$count=0 ;
				while ($row=$result->fetch()) {
					if ($count<5) {
						$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
						$category=getRoleCategory($this->session->get("gibbonRoleIDCurrent"), $connection2) ;
						$style="padding-right: 3px;" ;
						if ($category=="Student") {
							//Calculate style for student-specified completion of teacher-recorded homework
							$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$this->session->get("gibbonPersonID"));
							$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
							$resultCompletion=$this->pdo->executeQuery($dataCompletion, $sqlCompletion);
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}

							//Calculate style for student-specified completion of student-recorded homework
							$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$this->session->get("gibbonPersonID"));
							$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
							$resultCompletion=$this->pdo->executeQuery($dataCompletion, $sqlCompletion);
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}

							//Calculate style for online submission completion
							$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$this->session->get("gibbonPersonID"));
							$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'" ;
							$resultCompletion=$this->pdo->executeQuery($dataCompletion, $sqlCompletion);
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
						}

						//Calculate style for deadline
						if ( $diff < 2 ) {
							$style.="; border-right: 10px solid #cc0000" ;
						}
						else if ($diff<4) {
							$style.="; border-right: 10px solid #D87718" ;
						}

						$postSideBar .= "<li style='$style'>" ;
						$postSideBar .= "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&date=" . $row["date"] . "'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
						$postSideBar .= "<span style='font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack($guid, substr($row["homeworkDueDateTime"],0,10)) ;
						$postSideBar .= "</li>" ;
					}
					$count++ ;
				}
				$postSideBar .= "</ol>" ;
			}

			$postSideBar .= "<p style='padding-top: 0px; text-align: right'>" ;
			$postSideBar .= "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Planner/planner_deadlines.php'>" . $this->__('View Homework') . "</a>" ;
			$postSideBar .= "</p>" ;
		}
	}

	//Show recent results
	if ($this->session->get("address")=="" AND $this->getSecurity()->isActionAccessible("/modules/Markbook/markbook_view.php", null, '')) {
		$highestAction = $security->getHighestGroupedAction("/modules/Markbook/markbook_view.php") ;
		if ($highestAction=="View Markbook_myMarks") {
			$dataEntry=array("gibbonSchoolYearID"=>$this->session->get("gibbonSchoolYearID"), "gibbonPersonID"=>$this->session->get("gibbonPersonID"));
			$sqlEntry="SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableStudents='Y' ORDER BY completeDate DESC, name" ;
			$resultEntry=$this->pdo->executeQuery($dataEntry, $sqlEntry);

			if ($resultEntry->rowCount()>0) {
				$postSideBar .= $this->h2("Recent Marks", array(), true) ;

				$postSideBar .= "<ol>" ;
				$count=0 ;

				while ($rowEntry=$resultEntry->fetch() AND $count<5) {
					$postSideBar .= "<li><a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Markbook/markbook_view.php#" . $rowEntry["gibbonMarkbookEntryID"] . "'>" . $rowEntry["course"] . "." . $rowEntry["class"] . "<br/><span style='font-size: 85%; font-style: italic'>" . $rowEntry["name"] . "</span></a></li>" ;
					$count++ ;
				}

				$postSideBar .= "</ol>" ;
			}
		}
	}

	$postSideBar .= $this->renderReturn('default.sidebar.myClasses');

	//Show tag cloud
	if ($this->session->get("address")=="" AND $this->getSecurity()->isActionAccessible("/modules/Resources/resources_view.php", null, '')) {
		$resource = new Module\Resources\Functions\functions($this);
		$postSideBar .= $this->h2("Resource Tags", array(), true) ;
		$postSideBar .= $resource->getTagCloud(20) ;
		$postSideBar .= "<p style='margin-bototm: 20px; text-align: right'>" ;
		$postSideBar .= "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Resources/resources_view.php'>" . $this->__('View Resources') . "</a>" ;
		$postSideBar .= "</p>" ;
	}

	//Show role switcher if user has more than one role
	if ($this->session->get("username") !== null) {
		if (count($this->session->get("gibbonRoleIDAll"))>1 AND $this->session->get("address")=="") {
			$postSideBar .= $this->h2("Role Switcher", array(), true) ;

			if (isset($_GET["switchReturn"])) {
				$switchReturn=$_GET["switchReturn"] ;
			}
			else {
				$switchReturn="" ;
			}
			$switchReturnMessage="" ;
			$class="error" ;
			if (!($switchReturn=="")) {
				if ($switchReturn=="fail0") {
					$switchReturnMessage=$this->__("Role ID not specified.") ;
				}
				else if ($switchReturn=="fail1") {
					$switchReturnMessage=$this->__("You do not have access to the specified role.") ;
				}
				else if ($switchReturn=="success0") {
					$switchReturnMessage=$this->__("Role switched successfully.") ;
					$class="success" ;
				}
				$postSideBar .= $this->returnMessage($switchReturnMessage, $class);
			}

			$postSideBar .= "<p>" ;
				$postSideBar .= $this->__("You have multiple roles within the system. Use the list below to switch role:") ;
			$postSideBar .= "</p>" ;

			$postSideBar .= "<ul>" ;
			$roleIDAll = $this->session->get("gibbonRoleIDAll");
			for ($i=0; $i<count($roleIDAll); $i++) {
				if ($roleIDAll[$i][0]==$this->session->get("gibbonRoleIDCurrent")) {
					$postSideBar .= "<li><a href='roleSwitcherProcess.php?gibbonRoleID=" . $roleIDAll[$i][0] . "'>" . $this->__( $roleIDAll[$i][1]) . "</a> <i>" . $this->__('(Active)') . "</i></li>" ;
				}
				else {
					$postSideBar .= "<li><a href='roleSwitcherProcess.php?gibbonRoleID=" . $roleIDAll[$i][0] . "'>" . $this->__( $roleIDAll[$i][1]) . "</a></li>" ;
				}
			}
			$postSideBar .= "</ul>" ;
		}
	}

	$postSideBar .= $this->renderReturn('default.sidebar.extra.bottom');
	$postSideBar .= $this->renderReturn('default.sidebar.end');
	$el = new \stdClass();
	$el->preSideBar = $preSideBar;
	$el->slider = $slider;
	$el->postSideBar = $postSideBar;
	$this->render('default.sidebar', $el);
}
