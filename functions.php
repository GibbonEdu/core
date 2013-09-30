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

//Accepts birthday in mysql date (YYYY-MM-DD) ;
function daysUntilNextBirthday($birthday) {
	$today=date("Y-m-d") ;
	$btsString=substr($today,0,4) . "-" . substr($birthday, 5) ;
	$bts = strtotime($btsString);
	$ts = time();
	
	if ($bts < $ts) {
		$bts = strtotime(date("y",strtotime("+1 year")) . "-" . substr($birthday, 5));
	}

	$days=ceil(($bts - $ts) / 86400);
	if ($days==365) {
		$days=0 ;
	}
	return $days ;
}

function getSmartWorkflowHelp($connection2, $guid, $step="") {
	$output=false ;
	
	try {
		$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sql="SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		if ($row["smartWorkflowHelp"]=="Y") {
			$output="<div id='smartWorkflowHelp' class='message' style='padding-top: 14px'>" ;
				$output.="<div style='padding: 0 7px'>" ;
					$output.="<span style='font-size: 175%'><i><b>Smart Workflow</b></i> Getting Started</span><br/>" ;
					$output.="Designed and built by teachers, Gibbon's Smart Workflow takes care of the boring stuff, so you can get on with teaching.<br/>" ;
				$output.="</div>" ;
				$output.="<table cellspacing='0' style='width: 100%; margin: 10px 0px; border-spacing: 4px;'>" ;
					$output.="<tr>" ;
						if ($step==1) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>One</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>Create<br/>Outcomes</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>One</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/outcomes.php'>Create<br/>Outcomes</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==2) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>Two</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>Plan & Deploy<br/>Smart Units</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>Two</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/units.php'>Plan & Deploy<br/>Smart Units</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==3) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>Three</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>Share, Teach<br/>& Interact</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>Three</span><br/>" ;
								$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>Share, Teach<br/>& Interact</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==4) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>Four</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>Assign & Collect<br/>Work</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>Four</span><br/>" ;
							$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php'>Assign & Collect<br/>Work</span><br/></a>" ;
							$output.="</td>" ;
						}
						if ($step==5) {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid rgba(255,255,255,0.0); background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='color: #c00; font-size: 270%; font-weight: bold; letter-spacing: 70%'>Five</span><br/>" ;
								$output.="<span style='color: #c00; font-size: 140%; letter-spacing: 70%'>Assess & Give<br/>Feedback</span><br/></span>" ;
							$output.="</td>" ;
						}
						else {
							$output.="<td style='width: 20%; border-top: 3px solid #fff; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 4px'>" ;
								$output.="<span style='font-size: 270%; font-weight: bold; letter-spacing: 70%'>Five</span><br/>" ;
							$output.="<span style='font-size: 140%; letter-spacing: 70%'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php'>Assess & Give<br/>Feedback</span><br/></a>" ;
							$output.="</td>" ;
						}
					$output.="</tr>" ;
					if ($step!="") {
						$output.="<tr>" ;
							$output.="<td style='text-align: justify; font-size: 125%; border-bottom: 2px solid #fff; background-color: rgba(255,255,255,0.25); padding: 15px 4px' colspan=5>" ;
								if ($step==1) {
									$output.="<b>Outcomes</b> provide a way to plan and track what is being taught in school, and so are a great place to get started.<br/><br/>Click on the \"Add\" button (below this message, on the right) to add a new outcome, which can either be school-wide, or attached to a particular department.<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'><b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this. Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a> for help.</div>" ;
								}
								else if ($step==2) {
									$output.="<b>Smart Units</b> support you in the design of course content, and can be quickly turned into individual lesson plans using intuitive drag and drop. Smart Units cut planning time dramatically, and support ongoing improvement and reuse of content.<br/><br/>Choose a course, using the dropdown menu on the right, and then click on the \"Add\" button (below this message, on the right) to add a new unit. Once your master unit is complete, deploy it to a class to create your lesson plans.<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'><b>Note</b>: You need to be in a department, with the correct permissions, in order to be able to do this. Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a> for help.</div>" ;
								}
								else if ($step==3) {
									$output.="<b>Planner</b> supports online lesson plans which can be shared with students, parents and other teachers. Create your lesson by hand, or automatically via <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/units.php'>Smart Units</a>. Lesson plans facilitate sharing of course content, homework assignment and submission, text chat, and attendance taking.<br/><br/>Choose a date or class, using the menu on the right, and then click on the \"Add\" button (below this message, on the right) to add a new unit.<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'><b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this. Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a> for help.</div>" ;
								}
								else if ($step==4) {
									$output.="<b>Homework + Deadlines</b> allows teachers and students to see upcoming deadlines, cleanly displayed in one place. Click on an entry to view the details for that piece of homework, and the lesson it is attached to.<br/><br/>Homework can be assigned using the <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>Planner</a>, which also allows teachers to view all submitted work, and records late and incomplete work.<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'><b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this. Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a> for help.</div>" ;
								}
								else if ($step==5) {
									$output.="<b>Markbook</b> provides an organised way to assess, record and report on student progress. Use grade scales, rubrics, comments and file uploads to keep students and parents up to date. Link markbooks to the <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php'>Planner</a>, and see student work as you are marking it.<br/><br/>Choose a class from the menu on the right, and then click on the \"Add\" button (below this message, on the right) to create a new markbook column .<br/>" ;
									$output.="<div style='font-size: 75%; font-style: italic; margin-top: 10px'><b>Note</b>: You need to have classes assigned to you, with the correct permissions, in order to be able to do this. Please contact <a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a> for help.</div>" ;
								}
							$output.="</td>" ;
						$output.="</tr>" ;
					}
				$output.="</table>" ;
				$output.="<div style='text-align: right; font-size: 90%; padding: 0 7px'>" ;
					$output.="<a title='Dismiss Smart Workflow Help' onclick='$(\"#smartWorkflowHelp\").fadeOut(1000); $.ajax({ url: \"" . $_SESSION[$guid]["absoluteURL"] . "/index_SmartWorkflowHelpAjax.php\"})' href='#'>Dismiss Smart Workflow Help</a>" ;
				$output.="</div>" ;
			$output.="</div>" ;
		}
	}
	
	return $output ;	
}

function doesPasswordMatchPolicy($connection2, $passwordNew) {
	$output=TRUE ;
	
	$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
	$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
	$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
	$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
	
	if ($alpha==FALSE OR $numeric==FALSE OR $punctuation==FALSE OR $minLength==FALSE) {
		$output=FALSE ;
	}
	else {
		if ($alpha!="N" OR $numeric!="N" OR $punctuation!="N" OR $minLength>=0) {
			if ($alpha=="Y") {
				if (preg_match('`[A-Z]`',$passwordNew)==FALSE OR preg_match('`[a-z]`',$passwordNew)==FALSE) {
					$output=FALSE ;
				}
			}
			if ($numeric=="Y") {
				if (preg_match('`[0-9]`',$passwordNew)==FALSE) {
					$output=FALSE ;
				}
			}  
			if ($punctuation=="Y") {
				if (preg_match('/[^a-zA-Z0-9]/',$passwordNew)==FALSE AND strpos($passwordNew, " ")==FALSE) {
					$output=FALSE ;
				}
			}
			if ($minLength>0) {
				if (strLen($passwordNew)<$minLength) {
					$output=FALSE ;
				}
			}
		}
	}
	
	return $output ;
}

function getPasswordPolicy($connection2) {
	$output=FALSE ;
	
	$alpha=getSettingByScope( $connection2, "System", "passwordPolicyAlpha" ) ;
	$numeric=getSettingByScope( $connection2, "System", "passwordPolicyNumeric" ) ;
	$punctuation=getSettingByScope( $connection2, "System", "passwordPolicyNonAlphaNumeric" ) ;
	$minLength=getSettingByScope( $connection2, "System", "passwordPolicyMinLength" ) ;
	
	if ($alpha==FALSE OR $numeric==FALSE OR $punctuation==FALSE OR $minLength==FALSE) {
		$output.="An error occurred whilst obtaining the password policy" ;
	}
	else if ($alpha!="N" OR $numeric!="N" OR $punctuation!="N" OR $minLength>=0) {
		$output.="The password policy stipulates that passwords must:<br/>" ;
		$output.="<ul>" ;
			if ($alpha=="Y") {
				$output.="<li>Contain at least one lowercase letter, and one uppercase letter.</li>" ;
			}
			if ($numeric=="Y") {
				$output.="<li>Contain at least one number.</li>" ;
			}
			if ($punctuation=="Y") {
				$output.="<li>Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).</li>" ;
			}
			if ($minLength>=0) {
				$output.="<li>Must be at least $minLength characters in length.</li>" ;
			}
		$output.="</ul>" ;
	}
	
	return $output ;
}

function getStudentFastFinder($connection2, $guid) {
	$output=FALSE ;
	
	$highestAction=getHighestGroupedAction($guid, "/modules/Students/student_view_details.php", $connection2) ;
	if ($highestAction=="View Student Profile_full") {
		//Get student list
		try {
			$dataList=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlList="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
			$resultList=$connection2->prepare($sqlList);
			$resultList->execute($dataList); 
		}
		catch(PDOException $e) {}

		$output.="<h2>" ;
		$output.="Fast Student Finder<br/>" ;
		if (getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2)=="Staff") {
			$output.="<span style='font-size: 50%; font-weight: normal; font-style: italic; line-height: 80%'>Total Enrollment: " . $resultList->rowCount() . "</span>" ;
		}
		$output.="</h2>" ;
		
		
		$list="" ;
		while ($rowList=$resultList->fetch()) {
			$list=$list . "{id: \"" . $rowList["gibbonPersonID"] . "\", name: \"" . formatName("", htmlPrep($rowList["preferredName"]), htmlPrep($rowList["surname"]), "Student", true) . " (" . htmlPrep($rowList["name"]) . ")\"}," ;
		}
		$output.="<style>" ;
			$output.="ul.token-input-list-facebook { width: 157px; float: left; height: 25px!important; }" ;
			$output.="div.token-input-dropdown-facebook { width: 157px }" ;
		$output.="</style>" ;
		$output.="<div style='padding-bottom: 15px; height: 40px; margin-top: 0px'>" ;
			$output.="<form method='get'>" ;
				$output.="<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>" ;	
					$output.="<tr>" ;
						$output.="<td style='vertical-align: top'>" ; 
							$output.="<input type='text' id='gibbonPersonID' name='gibbonPersonID' />" ;
							$output.="<script type='text/javascript'>" ;
								$output.="$(document).ready(function() {" ;
									 $output.="$(\"#gibbonPersonID\").tokenInput([" ;
										$output.=substr($list,0,-1) ;
									$output.="]," ; 
										$output.="{theme: \"facebook\"," ;
										$output.="hintText: \"Start typing a name...\"," ;
										$output.="allowCreation: false," ;
										$output.="preventDuplicates: true," ;
										$output.="tokenLimit: 1});" ;
								$output.="});" ;
							$output.="</script>" ;
							$output.="<script type='text/javascript'>" ;
								$output.="var gibbonPersonID = new LiveValidation('gibbonPersonID');" ;
								$output.="gibbonPersonID.add(Validate.Presence);" ;
							 $output.="</script>" ;
						$output.="</td>" ;
						$output.="<td class='right' style='vertical-align: top'>" ;
							$output.="<input style='height: 27px; width: 20px!important; margin-top: 0px;' type='submit' value='Go'>" ;
							$output.="<input type='hidden' name='q' value='/modules/Students/student_view_details.php'>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
				$output.="</table>" ;
			$output.="</form>" ;
		$output.="</div>" ;
	}
	
	return $output ;
}

function getAlert($connection2, $gibbonAlertLevelID) {
	$output=FALSE; 
	
	try {
		$dataAlert=array("gibbonAlertLevelID"=>$gibbonAlertLevelID); 
		$sqlAlert="SELECT * FROM gibbonAlertLevel WHERE gibbonAlertLevelID=:gibbonAlertLevelID" ;
		$resultAlert=$connection2->prepare($sqlAlert);
		$resultAlert->execute($dataAlert);
	}
	catch(PDOException $e) { }
	if ($resultAlert->rowCount()==1) {
		$rowAlert=$resultAlert->fetch() ;
		$output=array() ;
		$output["name"]=$rowAlert["name"] ;
		$output["nameShort"]=$rowAlert["nameShort"] ;
		$output["color"]=$rowAlert["color"] ;
		$output["colorBG"]=$rowAlert["colorBG"] ;
		$output["description"]=$rowAlert["description"] ;
		$output["sequenceNumber"]=$rowAlert["sequenceNumber"] ;
	}
	
	return $output ;
}

function getSalt() {
  $c = explode(" ", ". / a A b B c C d D e E f F g G h H i I j J k K l L m M n N o O p P q Q r R s S t T u U v V w W x X y Y z Z 0 1 2 3 4 5 6 7 8 9");
  $ks = array_rand($c, 22);
  $s = "";
  foreach($ks as $k) { $s .= $c[$k]; }
  return $s;
}

//Get information on a unit of work, inlcuding the possibility that it is a hooked unit
function getUnit($connection2, $gibbonUnitID, $gibbonHookID, $gibbonCourseClassID) {
	$output=array() ;
	$unitType=false ;
	if ($gibbonUnitID!="") {
		//Check for hooked unit (will have - in value)
		if ($gibbonHookID=="") {
			//No hook
			try {
				$dataUnit=array("gibbonUnitID"=>$gibbonUnitID); 
				$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
				$resultUnit=$connection2->prepare($sqlUnit);
				$resultUnit->execute($dataUnit); 
				if ($resultUnit->rowCount()==1) {
					$rowUnit=$resultUnit->fetch() ;
					$unitType=$rowUnit["type"] ;
					$output[0]=$rowUnit["name"] ;
					$output[1]="" ;
				}
			}
			catch(PDOException $e) { }
		}
		else {
			//Hook!
			try {
				$dataHooks=array("gibbonHookID"=>$gibbonHookID); 
				$sqlHooks="SELECT * FROM gibbonHook WHERE gibbonHookID=:gibbonHookID" ;
				$resultHooks=$connection2->prepare($sqlHooks);
				$resultHooks->execute($dataHooks); 
				if ($resultHooks->rowCount()==1) {
					$rowHooks=$resultHooks->fetch() ;
					$hookOptions=unserialize($rowHooks["options"]) ;
					if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
						try {
							$dataHookUnits=array(); 
							$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $gibbonUnitID . " AND " . $hookOptions["classLinkJoinFieldClass"] . "=" . $gibbonCourseClassID . " ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
							$resultHookUnits=$connection2->prepare($sqlHookUnits);
							$resultHookUnits->execute($dataHookUnits);
							if ($resultHookUnits->rowCount()==1) {
								$rowHookUnits=$resultHookUnits->fetch() ;
								$output[0]=$rowHookUnits[$hookOptions["unitNameField"]] ;
								$output[1]=$rowHooks["name"] ;
							}
						}
						catch(PDOException $e) { }
					}
				}
			}
			catch(PDOException $e) { }
		}
	}
	
	return $output ;
}

function getWeekNumber($date, $connection2, $guid) {
	$week=0 ;
	try {
		$dataWeek=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlWeek="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$resultWeek=$connection2->prepare($sqlWeek);
		$resultWeek->execute($dataWeek); 
		while ($rowWeek=$resultWeek->fetch()) {
			$firstDayStamp=strtotime($rowWeek["firstDay"]) ;
			$lastDayStamp=strtotime($rowWeek["lastDay"]) ;
			while (date("D",$firstDayStamp)!="Mon") {
				$firstDayStamp=$firstDayStamp-86400 ;
			}
			$head=$firstDayStamp ;
			while ($head<=($date) AND $head<($lastDayStamp+86399)) {
				$head=$head+(86400*7) ;
				$week++ ;
			}
			if ($head<($lastDayStamp+86399)) {
				break ;
			}
		}
	}
	catch(PDOException $e) { }
	
	if ($week<=0) {
		return false ;
	}
	else {
		return $week ;
	}
}

function getModuleEntry($address, $connection2, $guid) {
	$output=false ;
	
	try {
		$data=array("moduleName"=>getModuleName($address),"gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
		$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM `gibbonModule`, gibbonAction, gibbonPermission WHERE gibbonModule.name=:moduleName AND (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) ORDER BY category, name";
		$result=$connection2->prepare($sql);
		$result->execute($data);
		if ($result->rowCount()==1) {
			$row=$result->fetch() ;
			$entryURL=$row["entryURL"] ;
			if (isActionAccessible($guid, $connection2, "/modules/" . $row["name"] . "/" . $entryURL)==FALSE AND $entryURL!="index.php") {
				try {
					$dataEntry=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "moduleName"=>$row["name"]); 
					$sqlEntry="SELECT DISTINCT gibbonAction.entryURL FROM gibbonModule, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND gibbonModule.name=:moduleName ORDER BY gibbonAction.name";
					$resultEntry=$connection2->prepare($sqlEntry);
					$resultEntry->execute($dataEntry);
					if ($resultEntry->rowCount()>0) {
						$rowEntry=$resultEntry->fetch() ;
						$entryURL=$rowEntry["entryURL"] ;
					}
				}
				catch(PDOException $e) {}
			}
		}
	}
	catch(PDOException $e) {}
	
	if ($entryURL!="") {
		$output=$entryURL ;
	}
	return $output ;
}

function formatName( $title, $preferredName, $surname, $roleCategory, $reverse=FALSE, $informal=FALSE ) {
	$output=false ;
	
	if ($roleCategory=="Staff" OR $roleCategory=="Other") {
		if ($informal==FALSE) {
			if ($reverse==TRUE) {
				$output=$title . $surname . ", " . strtoupper(substr($preferredName,0,1)) ;
			}
			else {
				$output=$title . strtoupper(substr($preferredName,0,1)) . ". " . $surname ;
			}
		}
		else {
			if ($reverse==TRUE) {
				$output=$surname . ", " . $preferredName ;
			}
			else {
				$output=$preferredName . " " . $surname ;
			}
		}
	}
	else if ($roleCategory=="Parent") {
		if ($informal==FALSE) {
			if ($reverse==TRUE) {
				$output=$title . $surname . ", " . $preferredName ;
			}
			else {
				$output=$title . $preferredName . " " . $surname ;
			}
		}
		else {
			if ($reverse==TRUE) {
				$output=$surname . ", " . $preferredName ;
			}
			else {
				$output=$preferredName . " " . $surname ;
			}
		}
	}
	else if ($roleCategory=="Student") {
		if ($reverse==TRUE) {
			$output=$surname. ", " . $preferredName ;
		}
		else {
			$output=$preferredName. " " . $surname ;
		}
		
	}

	return $output ;
}

//$tinymceInit indicates whether or not tinymce should be initialised, or whether this will be done else where later (this can be used to improve page load.
function getEditor($guid, $tinymceInit=TRUE, $id, $value="", $rows=10, $showMedia=false, $required=false, $initiallyHidden=false, $allowUpload=true, $initialFilter="", $resourceAlphaSort=false ) {
	$output=false ;
	if ($resourceAlphaSort==false) {
		$resourceAlphaSort="false" ;
	}
	else {
		$resourceAlphaSort="true" ;
	}
	
	$output.="<a name='" . $id . "editor'>" ;
	$output.="<div id='editor-toolbar'>" ;
		$output.="<a style='margin-top:-4px' id='" . $id . "edButtonHTML' class='hide-if-no-js edButtonHTML'>HTML</a>" ;
		$output.="<a style='margin-top:-4px' id='" . $id . "edButtonPreview' class='active hide-if-no-js edButtonPreview'>Visual</a>" ;
		
		$output.="<div id='media-buttons'>" ;
			$output.="<div style='padding-top: 2px; height: 15px'>" ;
				if ($showMedia==TRUE) {
					$output.="<div id='" . $id . "mediaInner' style='text-align: left'>" ;
						$output.="<script type='text/javascript'>" ;	
							$output.="$(document).ready(function(){" ;
								$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
								$output.="\$(\"." .$id . "resourceAddSlider\").hide();" ;
								$output.="\$(\"." .$id . "resourceQuickSlider\").hide();" ;
								$output.="\$(\"." . $id . "show_hide\").show();" ;
								$output.="\$(\"." . $id . "show_hide\").unbind('click').click(function(){" ;
									$output.="\$(\"." .$id . "resourceSlider\").slideToggle();" ;
									$output.="\$(\"." .$id . "resourceAddSlider\").hide();" ;
									$output.="\$(\"." .$id . "resourceQuickSlider\").hide();" ;
								$output.="});" ;
								$output.="\$(\"." . $id . "show_hideAdd\").show();" ;
								$output.="\$(\"." . $id . "show_hideAdd\").unbind('click').click(function(){" ;
									$output.="\$(\"." .$id . "resourceAddSlider\").slideToggle();" ;
									$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
									$output.="\$(\"." .$id . "resourceQuickSlider\").hide();" ;
								$output.="});" ;
								$output.="\$(\"." . $id . "show_hideQuickAdd\").show();" ;
								$output.="\$(\"." . $id . "show_hideQuickAdd\").unbind('click').click(function(){" ;
									$output.="\$(\"." .$id . "resourceQuickSlider\").slideToggle();" ;
									$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
									$output.="\$(\"." .$id . "resourceAddSlider\").hide();" ;
								$output.="});" ;
							$output.="});" ;
						$output.="</script>" ;
					
						$output.="<div style='float: left; padding-top:1px; margin-right: 5px'><u>Shared Resources</u>:</div> " ;
						$output.="<a title='Insert Existing Resource' style='float: left' class='" . $id . "show_hide' onclick='\$(\"." .$id . "resourceSlider\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_insert_ajax.php?alpha=" . $resourceAlphaSort . "&" . $initialFilter . "\",\"id=" . $id . "&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/search_mini.png' alt='Insert a Resource' onclick='return false;' /></a>" ;
						if ($allowUpload==true) {
							$output.="<a title='Create & Insert New Resource' style='float: left' class='" . $id . "show_hideAdd' onclick='\$(\"." .$id . "resourceAddSlider\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_add_ajax.php?alpha=" . $resourceAlphaSort . "&" . $initialFilter . "\",\"id=" . $id . "&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/upload_mini.png' alt='Add a Resource' onclick='return false;' /></a>" ;
						}
						$output.="<div style='float: left; padding-top:1px; margin-right: 5px'><u>Quick Add</u>:</div> " ;
						$output.="<a title='Quick Insert' style='float: left' class='" . $id . "show_hideQuickAdd' onclick='\$(\"." .$id . "resourceQuickSlider\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_addQuick_ajax.php?alpha=" . $resourceAlphaSort . "&" . $initialFilter . "\",\"id=" . $id . "&allowUpload=$allowUpload\");' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_new_mini.png' alt='Add a Resource' onclick='return false;' /></a>" ;
					$output.="</div>" ;
				}
			$output.="</div>" ;
		$output.="</div>" ;
		
		$output.="<div id='editorcontainer' style='margin-top: 4px'>" ;
			$output.="<textarea name='" . $id . "' id='" . $id . "' rows=$rows style='width: 100%; margin-left: 0px'>" . htmlPrep($value) . "</textarea>" ;
			if ($required) {
				$output.="<script type='text/javascript'>" ;
					$output.="var " . $id ."='';" ;
					$output.=$id . " = new LiveValidation('" . $id . "');" ;
					$output.=$id . ".add(Validate.Presence, { tinymce: true, tinymceField: '" . $id . "'});" ;
					if ($initiallyHidden==true) {
						$output.= $id . ".disable();" ;
					}
				$output.="</script>" ;
			}
		$output.="</div>" ;
		
		$output.="<script type='text/javascript'>" ;
			$output.="$(document).ready(function(){" ;
				if ($tinymceInit) {
					$output.="tinyMCE.execCommand('mceAddControl', false, '" . $id . "');" ;
				}
				$output.="$('#" . $id . "edButtonPreview').addClass('active') ;" ;
				 $output.="$('#" . $id . "edButtonHTML').click(function(){" ;
					$output.="tinyMCE.execCommand('mceRemoveControl', false, '" . $id . "');" ; 
					$output.="$('#" . $id . "edButtonHTML').addClass('active') ;" ;
					$output.="$('#" . $id . "edButtonPreview').removeClass('active') ;" ;
					$output.="\$(\"." .$id . "resourceSlider\").hide();" ;
					$output.="\$(\"#" .$id . "mediaInner\").hide();" ;
					if ($required) {
						$output.=$id . ".destroy();" ;
						$output.="$('.LV_validation_message').css('display','none');" ;
						$output.=$id . " = new LiveValidation('" . $id . "');" ;
						$output.=$id . ".add(Validate.Presence);" ;
					}
				 $output.="}) ;" ;
				 $output.="$('#" . $id . "edButtonPreview').click(function(){" ;
					$output.="tinyMCE.execCommand('mceAddControl', false, '" . $id . "');" ;
					$output.="$('#" . $id . "edButtonPreview').addClass('active') ;" ;
					$output.="$('#" . $id . "edButtonHTML').removeClass('active') ; " ;
					$output.="\$(\"#" .$id . "mediaInner\").show();" ;
					if ($required) {
						$output.=$id . ".destroy();" ;
						$output.="$('.LV_validation_message').css('display','none');" ;
						$output.=$id . " = new LiveValidation('" . $id . "');" ;
						$output.=$id . ".add(Validate.Presence, { tinymce: true, tinymceField: '" . $id . "'});" ;
					}
				 $output.="}) ;" ;
			$output.="});" ;
		$output.="</script>" ;	
		
		if ($showMedia==TRUE) {
			//DEFINE MEDIA INPUT DISPLAY
			$output.="<div class='" . $id . "resourceSlider' style='min-height: 60px; background-color: #DEECF7; border:1px solid #aaa; -moz-box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);-webkit-box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);}'>" ;
				$output.="<div style='text-align: center'>" ;
					$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='Loading' onclick='return false;' /><br/>" ;
					$output.="Loading" ;
				$output.="</div>" ;
			$output.="</div>" ;
			
			//DEFINE QUICK INSERT
			$output.="<div class='" . $id . "resourceQuickSlider' style='min-height: 60px; background-color: #DEECF7; border:1px solid #aaa; -moz-box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);-webkit-box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);}'>" ;
				$output.="<div style='text-align: center'>" ;
					$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='Loading' onclick='return false;' /><br/>" ;
					$output.="Loading" ;
				$output.="</div>" ;
			$output.="</div>" ;
		}
		
		if ($showMedia==TRUE AND $allowUpload==TRUE) {
			//DEFINE MEDIA ADD DISPLAY
			$output.="<div class='" . $id . "resourceAddSlider' style='min-height: 60px; background-color: #DEECF7; border:1px solid #aaa; -moz-box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);-webkit-box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);box-shadow:inset 1px 1px 2px rgba(0,0,0,0.1);}'>" ;
				$output.="<div style='text-align: center'>" ;
					$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='Loading' onclick='return false;' /><br/>" ;
					$output.="Loading" ;
				$output.="</div>" ;
			$output.="</div>" ;
		}
		
	$output.="</div>" ;
	
	return $output ;
}

function getYearGroups( $connection2 ) {
	$output=FALSE ;
	//Scan through year groups
	//SELECT NORMAL
	try {
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$result=$connection2->query($sql);  
		while ($row=$result->fetch()) {
			$output=$output . $row["gibbonYearGroupID"] . "," ;
			$output=$output . $row["name"] . "," ;
		}
	}
	catch(PDOException $e) { }		
	
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	return $output ;
}

function getYearGroupsFromIDList ( $connection2, $ids, $vertical=false ) {
	$output=FALSE ;
	
	//SELECT NORMAL
	try {
		$sqlYears="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$resultYears=$connection2->query($sqlYears);  
		
		$years=explode(",", $ids) ;
		if (count($years)>0 AND $years[0]!="") {
			if (count($years)==$resultYears->rowCount()) {
				$output="<i>All</i>" ;
			}
			else {
				try {
					$dataYears=array() ;
					$sqlYearsOr="" ;
					for ($i=0; $i<count($years); $i++) {
						if ($i==0) {
							$dataYears[$years[$i]]=$years[$i] ;
							$sqlYearsOr=$sqlYearsOr . " WHERE gibbonYearGroupID=:" . $years[$i] ;
						}
						else {
							$dataYears[$years[$i]]=$years[$i] ;
							$sqlYearsOr=$sqlYearsOr . " OR gibbonYearGroupID=:" . $years[$i] ;
						}
					}
					
					$sqlYears="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonYearGroup $sqlYearsOr ORDER BY sequenceNumber" ;
					$resultYears=$connection2->prepare($sqlYears); 
					$resultYears->execute($dataYears); 
				}
				catch(PDOException $e) { }		
						
				$count3=0 ;
				while ($rowYears=$resultYears->fetch()) {
					if ($count3>0) {
						if ($vertical==false) {
							$output.=", " ;
						}
						else {
							$output.="<br/>" ;
						}
					}
					$output.=$rowYears["nameShort"] ;
					$count3++ ;
				}
			}
		}
		else {
			$output="<i>None</i>" ;
		}
	}
	catch(PDOException $e) { }		
	
	return $output ;
}

//Gets terms in the specified school year
function getTerms( $connection2, $gibbonSchoolYearID, $short=FALSE ) {
	$output=FALSE ;
	//Scan through year groups
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	
	while ($row=$result->fetch()) {
		$output=$output . $row["gibbonSchoolYearTermID"] . "," ;
		if ($short==TRUE) {
			$output=$output . $row["nameShort"] . "," ;
		}
		else {
			$output=$output . $row["name"] . "," ;
		}
	}
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	return $output ;
}

//Array sort for multidimensional arrays
function msort($array, $id="id", $sort_ascending=true) {
	$temp_array = array();
	while(count($array)>0) {
		$lowest_id = 0;
		$index=0;
		foreach ($array as $item) {
			if (isset($item[$id])) {
				if ($array[$lowest_id][$id]) {
					if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
						$lowest_id = $index;
					}
				}
			}
			$index++;
		}
		$temp_array[] = $array[$lowest_id];
		$array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
	}
	if ($sort_ascending) {
		return $temp_array;
	} else {
		return array_reverse($temp_array);
	}
}

//Create the sidebar
function sidebar($connection2, $guid) {
	$loginReturn = $_GET["loginReturn"] ;
	$loginReturnMessage ="" ;
	if (!($loginReturn=="")) {
		if ($loginReturn=="fail0b") {
			$loginReturnMessage ="Username or password not set." ;	
		}
		else if ($loginReturn=="fail1") {
			$loginReturnMessage ="Incorrect username and password." ;	
		}
		else if ($loginReturn=="fail2") {
			$loginReturnMessage ="You do not have sufficient privileges to login." ;	
		}
		else if ($loginReturn=="fail5") {
			$loginReturnMessage ="Login failed due to a database error." ;	
		}
		else if ($loginReturn=="fail6") {
			$loginReturnMessage ="Too many failed logins: please <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/passwordReset.php'>reset password</a>." ;	
		}
		print "<div class='error'>" ;
			print $loginReturnMessage;
		print "</div>" ;
	} 
	
	if ($_SESSION[$guid]["sidebarExtra"]!="" AND $_SESSION[$guid]["sidebarExtraPosition"]!="bottom") {
		print "<div class='sidebarExtra'>" ;
		print $_SESSION[$guid]["sidebarExtra"] ;
		print "</div>" ;
	}
	
	if ($_SESSION[$guid]["username"]=="") {
		?>
		<h2>
			Login
		</h2>
		<form name="loginForm" method="post" action="./login.php?q=<? print $_GET["q"] ?>">
			<table class='noIntBorder' cellspacing='0' style="width: 100%; margin: 0px 0px">	
				<tr>
					<td> 
						<b>Username</b>
					</td>
					<td class="right">
						<input name="username" id="username" maxlength=20 type="text" style="width:120px">
						<script type="text/javascript">
							var username = new LiveValidation('username', {onlyOnSubmit: true });
							username.add(Validate.Presence);
						 </script> 
					</td>
				</tr>
				<tr>
					<td> 
						<b>Password</b>
					</td>
					<td class="right">
						<input name="password" id="password" maxlength=20 type="password" style="width:120px">
						<script type="text/javascript">
							var password = new LiveValidation('password', {onlyOnSubmit: true });
							password.add(Validate.Presence);
						 </script> 
					</td>
				</tr>
				<tr class='schoolYear' class='schoolYear'>
					<td> 
						<b>School Year</b>
					</td>
					<td class="right">
						<select name="gibbonSchoolYearID" id="gibbonSchoolYearID" style="width: 120px">
							<?
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonSchoolYear ORDER BY sequenceNumber" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["status"]=="Current") {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
					</td>
					<td class="right">
						<?
						print "<script type='text/javascript'>" ;	
							print "$(document).ready(function(){" ;
								print "\$(\".schoolYear\").hide();" ;
								print "\$(\".show_hide\").fadeIn(1000);" ;
								print "\$(\".show_hide\").click(function(){" ;
								print "\$(\".schoolYear\").fadeToggle(1000);" ;
								print "});" ;
							print "});" ;
						print "</script>" ;
						?>
						<span style='font-size: 10px'><a class='show_hide' onclick='false' href='#'>Options</a> . <a href="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php?q=passwordReset.php">Forgot Password?</a></span>
					</td>
				</tr>
				<tr>
					<td class="right" colspan=2>
						<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="Login">
					</td>
				</tr>
			</table>
		</form>
	<?
	}
	
	//Show Module Menu
	//Check address to see if we are in the module area
	if (substr($_SESSION[$guid]["address"],0,8)=="/modules") {
		//Get and check the module name
		$moduleID=checkModuleReady($_SESSION[$guid]["address"], $connection2 );
		if ($moduleID!=FALSE) {
			try {
				$data=array("gibbonModuleID"=>$moduleID, "gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
				$sql="SELECT gibbonModule.entryURL AS moduleEntry, gibbonModule.name AS moduleName, gibbonAction.name, gibbonAction.precedence, gibbonAction.category, gibbonAction.entryURL, URLList FROM gibbonModule, gibbonAction, gibbonPermission WHERE (gibbonModule.gibbonModuleID=:gibbonModuleID) AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND NOT gibbonAction.entryURL='' ORDER BY gibbonModule.name, category, gibbonAction.name, precedence DESC";
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
			
			if ($result->rowCount()>0) {			
				$output="<h2>" ;
				$output=$output . "Module Menu" ;
				$output=$output . "</h2>" ;
				
				$output=$output . "<ul class='moduleMenu'>" ;
				
				$currentCategory="" ;
				$lastCategory="" ;
				$currentName="" ;
				$lastName="" ;
				$count=0;
				$links=0 ;
				while ($row=$result->fetch()) {
					$moduleName=$row["moduleName"] ;
					$moduleEntry=$row["moduleEntry"] ;
					
					//Set active link class
					$style="" ;
					if (is_numeric(strpos($row["URLList"],getActionName($_SESSION[$guid]["address"])))) {
						$style="class='active'" ;
					}
					
					$currentCategory=$row["category"] ;
					if (strpos($row["name"],"_")>0) {
						$currentName=substr($row["name"],0,strpos($row["name"],"_")) ;
					}
					else {
						$currentName=$row["name"] ;
					}
							
					if ($currentName!=$lastName) {
						if ($currentCategory!=$lastCategory) {
							if ($count>0) {
								$output=$output . "</ul></li>";
							}
							$output=$output . "<li><b>$currentCategory</b>" ;
							$output=$output . "<ul>" ;
							$output=$output . "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>$currentName</a></li>" ;
						}
						else {
							$output=$output . "<li><a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["moduleName"] . "/" . $row["entryURL"] . "'>$currentName</a></li>" ;
						}
						$links++ ;
					}
					$lastCategory=$currentCategory ;
					$lastName=$currentName ;
					$count++ ;
				}
				if ($count>0) {
					$output=$output . "</ul></li>";
				}
				$output=$output . "</ul>" ;
				
				if ($links>1 OR (isActionAccessible($guid, $connection2, "/modules/$moduleName/$moduleEntry")==FALSE)) {
					print $output ;
				}
			}
		}
	}
	
	//Show student quick finder
	if ($_SESSION[$guid]["address"]=="" AND $_SESSION[$guid]["username"]!="") {
		$sidebar=getStudentFastFinder($connection2, $guid) ;
		if ($sidebar!=FALSE) {
			print $sidebar ;
		}
	}
	
	//Show recent discussion activity
	if ($_SESSION[$guid]["address"]=="" AND $_SESSION[$guid]["username"]!="" AND (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess.php") OR isActionAccessible($guid, $connection2, "/modules/Planner/planner.php"))) {
		$_SESSION[$guid]["lastTimestamp"] ;
		
		if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess.php")) {
			//Select my work with activity from Crowd Assessment
			$myWork=array() ;
			$countWork=0 ;
			
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClassPerson.gibbonPersonID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) WHERE role='Student' AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { }
			while ($row=$result->fetch()) {
				try {
					$dataWork=array("timestamp"=>$_SESSION[$guid]["lastTimestamp"], "gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$row["gibbonPersonID"],"gibbonPersonIDSelf"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlWork="SELECT DISTINCT gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID, gibbonPlannerEntry.name, gibbonPlannerEntry.gibbonPlannerEntryID FROM gibbonPlannerEntryHomework JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCrowdAssessDiscuss ON (gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID=gibbonCrowdAssessDiscuss.gibbonPlannerEntryHomeworkID) WHERE gibbonCrowdAssessDiscuss.timestamp>=:timestamp AND gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryHomework.gibbonPersonID=:gibbonPersonID AND NOT gibbonCrowdAssessDiscuss.gibbonPersonID=:gibbonPersonIDSelf ORDER BY count DESC" ;
					$resultWork=$connection2->prepare($sqlWork);
					$resultWork->execute($dataWork); 
				}
				catch(PDOException $e) { }
				while ($rowWork=$resultWork->fetch()) {
					$myWork[$countWork][0]=$row["course"] . "." . $row["class"] ;
					$myWork[$countWork][1]=$rowWork["name"] ;
					$myWork[$countWork][2]=$rowWork["gibbonPlannerEntryHomeworkID"] ;
					$myWork[$countWork][3]=$rowWork["gibbonPlannerEntryID"] ;
					$myWork[$countWork][4]=$row["gibbonPersonID"] ;	
					$countWork++ ;
				}
			}
			
			//Replies to me from Crowd Assessment
			$myReplies=array() ;
			$countReply=0 ;
			
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT gibbonCrowdAssessDiscuss.*, gibbonPlannerEntry.name, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonPlannerEntryHomework.gibbonPersonID AS owner FROM gibbonCrowdAssessDiscuss JOIN gibbonPlannerEntryHomework ON (gibbonCrowdAssessDiscuss.gibbonPlannerEntryHomeworkID=gibbonPlannerEntryHomework.gibbonPlannerEntryHomeworkID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntryHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) WHERE gibbonCrowdAssessDiscuss.gibbonPersonID=:gibbonPersonID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { }
			while ($row=$result->fetch()) {
				try {
					$dataReply=array("timestamp"=>$_SESSION[$guid]["lastTimestamp"],"gibbonCrowdAssessDiscussID"=>$row["gibbonCrowdAssessDiscussID"]); 
					$sqlReply="SELECT gibbonCrowdAssessDiscuss.*, surname, preferredName FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCrowdAssessDiscuss.timestamp>=:timestamp AND gibbonCrowdAssessDiscussIDReplyTo=:gibbonCrowdAssessDiscussID" ;
					$resultReply=$connection2->prepare($sqlReply);
					$resultReply->execute($dataReply);
				}
				catch(PDOException $e) { }
				while ($rowReply=$resultReply->fetch()) {
					$myReplies[$countReply][0]=formatName("",$rowReply["preferredName"], $rowReply["surname"], "Student", false) ;
					$myReplies[$countReply][1]=$row["name"] ;
					$myReplies[$countReply][2]=$rowReply["gibbonPlannerEntryHomeworkID"] ;
					$myReplies[$countReply][3]=$row["gibbonPlannerEntryID"] ;
					$myReplies[$countReply][4]=$row["owner"] ;	
					$myReplies[$countReply][5]=$row["gibbonCrowdAssessDiscussID"] ;	
					$countReply++ ;
				}
			}
		}
		
		if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
			//Select my work with activity from lesson plans
			$myLessons=array() ;
			$countLessons=0 ;
			
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClassPerson.gibbonPersonID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
			
			while ($row=$result->fetch()) {
				try {
					$dataWork=array("timestamp"=>$_SESSION[$guid]["lastTimestamp"], "gibbonCourseClassID"=>$row["gibbonCourseClassID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sqlWork="SELECT DISTINCT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonPlannerEntryDiscuss ON (gibbonPlannerEntry.gibbonPlannerEntryID=gibbonPlannerEntryDiscuss.gibbonPlannerEntryID) WHERE gibbonPlannerEntryDiscuss.timestamp>=:timestamp AND gibbonCourseClassID=:gibbonCourseClassID AND NOT gibbonPlannerEntryDiscuss.gibbonPersonID=:gibbonPersonID" ;
					$resultWork=$connection2->prepare($sqlWork);
					$resultWork->execute($dataWork); 
				}
				catch(PDOException $e) { }
				while ($rowWork=$resultWork->fetch()) {
					$myLessons[$countLessons][0]=$row["course"] . "." . $row["class"] ;
					$myLessons[$countLessons][1]=$rowWork["name"] ;
					$myLessons[$countLessons][2]=$rowWork["gibbonPlannerEntryHomeworkID"] ;
					$myLessons[$countLessons][3]=$rowWork["gibbonPlannerEntryID"] ;
					$myLessons[$countLessons][4]=$row["gibbonPersonID"] ;	
					$countLessons++ ;
				}
			}
			
			//Replies to me from lesson plans
			$myLessonReplies=array() ;
			$countLessonReply=0 ;
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT gibbonPlannerEntryDiscuss.*, gibbonPlannerEntry.name, gibbonPlannerEntry.gibbonPlannerEntryID FROM gibbonPlannerEntryDiscuss JOIN gibbonPlannerEntry ON (gibbonPlannerEntryDiscuss.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) WHERE gibbonPlannerEntryDiscuss.gibbonPersonID=:gibbonPersonID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { }
			while ($row=$result->fetch()) {
				try {
					$dataReply=array("timestamp"=>$_SESSION[$guid]["lastTimestamp"], "gibbonPlannerEntryDiscussID"=>$row["gibbonPlannerEntryDiscussID"]); 
					$sqlReply="SELECT gibbonPlannerEntryDiscuss.*, surname, preferredName FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryDiscuss.timestamp>=:timestamp AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussID" ;
					$resultReply=$connection2->prepare($sqlReply);
					$resultReply->execute($dataReply);
				}
				catch(PDOException $e) { } 
				
				while ($rowReply=$resultReply->fetch()) {
					$myLessonReplies[$countLessonReply][0]=formatName("",$rowReply["preferredName"], $rowReply["surname"], "Student", false) ;
					$myLessonReplies[$countLessonReply][1]=$row["name"] ;
					$myLessonReplies[$countLessonReply][2]=$rowReply["gibbonPlannerEntryHomeworkID"] ;
					$myLessonReplies[$countLessonReply][3]=$row["gibbonPlannerEntryID"] ;
					$myLessonReplies[$countLessonReply][4]=$row["owner"] ;	
					$myLessonReplies[$countLessonReply][5]=$row["gibbonCrowdAssessDiscussID"] ;	
					$countLessonReply++ ;
				}
			}
		}
		
		if (count($myLessons)>0 OR count($myLessonReplies)>0 OR count($myWork)>0 OR count($myReplies)>0) {
			print "<h2>" ;
			print "Recent Discussion" ;
			print "</h2>" ;
			
			if (count($myWork)>0 OR count($myReplies)>0) {
				print "<h5 style='margin-top: 2px'>" ;
				print "Crowd Assessment" ;
				print "</h5>" ;
				
				if (count($myWork)>0) {
					print "<p>" ;
					print "Comments on my work:" ;
					print "</p>" ;
				
					print "<ul>" ;
					for ($i=0; $i<$countWork; $i++) {
						print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=" . $myWork[$i][3] . "&gibbonPlannerEntryHomeworkID=" . $myWork[$i][2] . "&gibbonPersonID=" . $myWork[$i][4] . "'>" . $myWork[$i][0] . " - " . $myWork[$i][1] . "</a></li>" ;
					}
					print "</ul>" ;
				}
				
				if (count($myReplies)>0) {
					print "<p>" ;
					print "Replies to me:" ;
					print "</p>" ;
				
					print "<ul>" ;
					for ($i=0; $i<$countReply; $i++) {
						print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=" . $myReplies[$i][3] . "&gibbonPlannerEntryHomeworkID=" . $myReplies[$i][2] . "&gibbonPersonID=" . $myReplies[$i][4] . "#" . $myReplies[$i][5] . "'>" . $myReplies[$i][0] . " - " . $myReplies[$i][1] . "</a></li>" ;
					}
					print "</ul>" ;
				}
			}
		
			if (count($myLessons)>0 OR count($myLessonReplies)>0) {
				print "<h5 style='margin-top: 12px'>" ;
				print "Planner" ;
				print "</h5>" ;
				
				if (count($myLessons)>0) {
					print "<p>" ;
					print "Comments on my lessons:" ;
					print "</p>" ;
				
					print "<ul>" ;
					for ($i=0; $i<$countLessons; $i++) {
						print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=" . $myLessons[$i][3] . "&date=$date'>" . $myLessons[$i][0] . " - " . $myLessons[$i][1] . "</a></li>" ;
					}
					print "</ul>" ;
				}
				
				if (count($myLessonReplies)>0) {
					print "<p>" ;
					print "Replies to me:" ;
					print "</p>" ;
				
					print "<ul>" ;
					for ($i=0; $i<$countLessonReply; $i++) {
						print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=" . $myLessonReplies[$i][3] . "&date=$date'>" . $myLessonReplies[$i][0] . " - " . $myLessonReplies[$i][1] . "</a></li>" ;
					}
					print "</ul>" ;
				}
			}
		}
	}
	
	//Show upcoming deadlines
	if ($_SESSION[$guid]["address"]=="" AND isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
		$highestAction=getHighestGroupedAction($guid, "/modules/Planner/planner.php", $connection2) ;
		if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
			print "<h2>" ;
			print "Homework + Deadlines" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'" . date("Y-m-d H:i:s") . "' AND ((date<'" . date("Y-m-d") . "') OR (date='" . date("Y-m-d") . "' AND timeEnd<='" . date("H:i:s") . "')) ORDER BY homeworkDueDateTime" ;
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { }
			if ($result->rowCount()<1) {
				print "<div class='success'>" ;
					print "No upcoming deadlines!" ;
				print "</div>" ;
			}
			else {
				print "<ol>" ;
				$count=0 ;
				while ($row=$result->fetch()) {
					if ($count<3) {
						$diff=(strtotime(substr($row["homeworkDueDateTime"],0,10)) - strtotime(date("Y-m-d")))/86400 ;
						$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
						$style="padding-right: 3px;" ;
						if ($category=="Student") {
							//Calculate style for student-specified completion
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryStudentTracker WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND homeworkComplete='Y'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion);
							}
							catch(PDOException $e) { }
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
							
							//Calculate style for online submission completion
							try {
								$dataCompletion=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlCompletion="SELECT gibbonPlannerEntryID FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND version='Final'" ;
								$resultCompletion=$connection2->prepare($sqlCompletion);
								$resultCompletion->execute($dataCompletion); 
							}
							catch(PDOException $e) { }
							if ($resultCompletion->rowCount()==1) {
								$style.="; background-color: #B3EFC2" ;
							}
						}
						
						//Calculate style for deadline
						if ($diff<2) {
							$style.="; border-right: 10px solid #cc0000" ;	
						}
						else if ($diff<4) {
							$style.="; border-right: 10px solid #D87718" ;	
						}
						
						print "<li style='$style'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&date=" . $row["date"] . "'>" . $row["course"] . "." . $row["class"] . "</a><br/>" ;
						print "<span style='font-style: italic'>Due at " . substr($row["homeworkDueDateTime"],11,5) . " on " . dateConvertBack(substr($row["homeworkDueDateTime"],0,10)) ;
						print "</li>" ;
					}
					$count++ ;
				}
				print "</ol>" ;
			}
						
			print "<p style='padding-top: 15px; text-align: right'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php'>View Homework</a>" ;
			print "</p>" ;
		}
	}
	
	//Show recent results
	if ($_SESSION[$guid]["address"]=="" AND isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")) {
		$highestAction=getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2) ;
		if ($highestAction=="View Markbook_myMarks") {
			try {
				$dataEntry=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlEntry="SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableStudents='Y' ORDER BY completeDate DESC, name" ;
				$resultEntry=$connection2->prepare($sqlEntry);
				$resultEntry->execute($dataEntry);
			}
			catch(PDOException $e) { }
			
			if ($resultEntry->rowCount()>0) {
				print "<h2>" ;
				print "Recent Marks" ;
				print "</h2>" ;
				
				print "<ol>" ;
				$count=0 ;
				
				while ($rowEntry=$resultEntry->fetch() AND $count<5) {
					print "<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php#" . $rowEntry["gibbonMarkbookEntryID"] . "'>" . $rowEntry["course"] . "." . $rowEntry["class"] . "<br/><span style='font-size: 85%; font-style: italic'>" . $rowEntry["name"] . "</span></a></li>" ;
					$count++ ;
				}
				
				print "</ol>" ;
			}
		}
	}
	
	
	//Show My Classes
	if ($_SESSION[$guid]["address"]=="" AND $_SESSION[$guid]["username"]!="") {
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' ORDER BY course, class" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		
		if ($result->rowCount()>0) {
			print "<h2 class='sidebar'>" ;
			print "My Classes" ;
			print "</h2>" ;
			
			print "<table class='mini' cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
						print "<th style='width: 40%'>" ;
						print "Class" ;
					print "</th>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
						print "<th style='width: 20%; font-size: 75%; text-align: center'>" ;
							print "Planner" ;
						print "</th>" ;
					}
					if (getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2)=="View Markbook_allClassesAllData") {
						print "<th style='width: 20%; font-size: 75%; text-align: center'>" ;
							print "Markbook" ;
						print "</th>" ;
					}
					print "<th style='width: 20%; font-size: 75%; text-align: center'>" ;
						print "People" ;
					print "</th>" ;
					if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
						print "<th style='width: 20%; font-size: 75%; text-align: center'>" ;
							print "Homework" ;
						print "</th>" ;
					}
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $row["course"] . "." . $row["class"] ;
						print "</td>" ;
						if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
							print "<td style='text-align: center'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&viewBy=class'><img style='margin-top: 3px' title='View Planner' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.gif'/></a> " ;
							print "</td>" ;
						}
						if (getHighestGroupedAction($guid, "/modules/Markbook/markbook_view.php", $connection2)=="View Markbook_allClassesAllData") {
							print "<td style='text-align: center'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'><img style='margin-top: 3px' title='View Markbook' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.gif'/></a> " ;
							print "</td>" ;
						}
						print "<td style='text-align: center'>" ;
							print "<a href='index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&subpage=Participants'><img title='Participants' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.gif'/></a>" ;
						print "</td>" ;
						if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")) {
							print "<td style='text-align: center'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter=" . $row["gibbonCourseClassID"] . "'><img style='margin-top: 3px' title='View Planner' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/homework.png'/></a> " ;
							print "</td>" ;
						}
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
	
	//Show tag cloud
	if ($_SESSION[$guid]["address"]=="" AND isActionAccessible($guid, $connection2, "/modules/Resources/resources_view.php")) {
		include "./modules/Resources/moduleFunctions.php" ;
		print "<h2 class='sidebar'>" ;
			print "Resource Tags" ;
		print "</h2>" ;
		print getTagCloud($guid, $connection2, 20) ;
		print "<p style='margin-bototm: 20px; text-align: right'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Resources/resources_view.php'>View Resources</a>" ;
		print "</p>" ;
	}
	
	//Show role switcher if user has more than one role
	if (count($_SESSION[$guid]["gibbonRoleIDAll"])>1 AND $_SESSION[$guid]["address"]=="") {
		print "<h2 class='sidebar'>" ;
		print "Role Switcher" ;
		print "</h2>" ;
		
		$switchReturn = $_GET["switchReturn"] ;
		$switchReturnMessage ="" ;
		$class="error" ;
		if (!($switchReturn=="")) {
			if ($switchReturn=="fail0") {
				$switchReturnMessage ="Role ID not specified." ;	
			}
			else if ($switchReturn=="fail1") {
				$switchReturnMessage ="You do not have access to the specified role." ;	
			}
			else if ($switchReturn=="success0") {
				$switchReturnMessage ="Role switched successfully." ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $switchReturnMessage;
			print "</div>" ;
		} 
		
		print "<p>" ;
			print "You have multiple roles within the system. Use the list below to switch role:" ;
		print "</p>" ;
		
		print "<ul>" ;
		for ($i=0; $i<count($_SESSION[$guid]["gibbonRoleIDAll"]); $i++) {
			if ($_SESSION[$guid]["gibbonRoleIDAll"][$i][0]==$_SESSION[$guid]["gibbonRoleIDCurrent"]) {
				print "<li><a href='roleSwitcherProcess.php?gibbonRoleID=" . $_SESSION[$guid]["gibbonRoleIDAll"][$i][0] . "'>" . $_SESSION[$guid]["gibbonRoleIDAll"][$i][1] . "</a> <i>(Active)</i></li>" ;
			}
			else {
				print "<li><a href='roleSwitcherProcess.php?gibbonRoleID=" . $_SESSION[$guid]["gibbonRoleIDAll"][$i][0] . "'>" . $_SESSION[$guid]["gibbonRoleIDAll"][$i][1] . "</a></li>" ;
			}
		}
		print "</ul>" ;
	}	
	
	if ($_SESSION[$guid]["sidebarExtra"]!="" AND $_SESSION[$guid]["sidebarExtraPosition"]=="bottom") {
		print "<div class='sidebarExtra'>" ;
		print $_SESSION[$guid]["sidebarExtra"] ;
		print "</div>" ;
	}
}

//Create the main menu
function mainMenu($connection2, $guid) {
	$output="" ;
	
	try {
		$data=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
		$sql="SELECT DISTINCT gibbonModule.name, gibbonModule.category, gibbonModule.entryURL FROM `gibbonModule`, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) ORDER BY category, name";
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$output.="<ul id='nav'>" ;
		$output.="<li class='active'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>Home</a></li>" ;
		$output.="</ul>" ;
	}
	
	if ($result->rowCount()<1) {
		$output.="<ul id='nav'>" ;
		$output.="<li class='active'><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>Home</a></li>" ;
		$output.="</ul>" ;
	}
	else {
		$style="" ;
		if ($_GET["q"]=="" or is_null($_GET["q"])) {
			$style="class='active'" ;
		}
		$output.="<ul id='nav'>" ;
		$output.="<li $style><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php'>Home</a></li>" ;
		
		$currentCategory="" ;
		$lastCategory="" ;
		$count=0;
		while ($row=$result->fetch()) {
			$currentCategory=$row["category"] ;
			
			$style="" ;
			if (getModuleCategory($_GET["q"], $connection2)==$currentCategory) {
				$style="class='active'" ;
			}
			
			$entryURL=$row["entryURL"] ;
			if (isActionAccessible($guid, $connection2, "/modules/" . $row["name"] . "/" . $entryURL)==FALSE AND $entryURL!="index.php") {
				try {
					$dataEntry=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"],"name"=>$row["name"]); 
					$sqlEntry="SELECT DISTINCT gibbonAction.entryURL FROM gibbonModule, gibbonAction, gibbonPermission WHERE (active='Y') AND (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND gibbonModule.name=:name ORDER BY gibbonAction.name";
					$resultEntry=$connection2->prepare($sqlEntry);
					$resultEntry->execute($dataEntry);
				}
				catch(PDOException $e) { }
				if ($resultEntry->rowCount()>0) {
					$rowEntry=$resultEntry->fetch() ;
					$entryURL=$rowEntry["entryURL"] ;
				}
			}
					
			if ($currentCategory!=$lastCategory) {
				if ($count>0) {
					$output.="</ul></li>";
				}
				$output.="<li $style><a href='#'>$currentCategory</a>" ;
				$output.="<ul>" ;
				$output.="<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . $row["name"] . "</a></li>" ;
			}
			else {
				$output.="<li><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $row["name"] . "/" . $entryURL . "'>" . $row["name"] . "</a></li>" ;
			}
			$lastCategory=$currentCategory ;
			$count++ ;
		}
		if ($count>0) {
			$output.="</ul></li>";
		}
		$output.="</ul>" ;
	}
	return $output ;
}

//Format address according to supplied inputs
function addressFormat( $address, $addressDistrict, $addressCountry ) {
	$return=FALSE ;
	
	if ($address!="") {
		$return=$return . $address ;
		if ($addressDistrict!="") {
			$return=$return . ", " . $addressDistrict ;
		}
		if ($addressCountry!="") {
			$return=$return . ", " . $addressCountry ;
		}
	}
	
	return $return ;
}

//Print out, preformatted indicator of max file upload size
function getMaxUpload( $multiple="" ) {
	$output="" ;
	$post=substr(ini_get("post_max_size"),0,(strlen(ini_get("post_max_size"))-1)) ;
	$file=substr(ini_get("upload_max_filesize"),0,(strlen(ini_get("upload_max_filesize"))-1)) ;
	
	$output=$output . "<div style='margin-top: 5px; font-style: italic; text-align: right; color: #c00'>" ;
	if ($multiple==TRUE) {
		if ($post<$file) {
			$output=$output . "Maximum size for all files: " . $post . "MB<br/>" ;
		}
		else {
			$output=$output . "Maximum size for all files: " . $file . "MB<br/>" ;
		}
	}
	else {
		if ($post<$file) {
			$output=$output . "Maximum file size: " . $post . "MB<br/>" ;
		}
		else {
			$output=$output . "Maximum file size: " . $file . "MB<br/>" ;
		}
	}
	$output=$output . "</div>" ; 
	
	return $output ;
}


//Encode strring using htmlentities with the ENT_QUOTES option
function htmlPrep($str) {
	return htmlentities($str, ENT_QUOTES, "UTF-8") ;
}

/*TO BE DELETED
//This function provides a clean, secure way to deal with the issue of escaping certain chars (such as ')
function conditional_escape($str) {
	if (get_magic_quotes_gpc()) {
		return mysql_real_escape_string(stripslashes($str));
	}
	else {
		return mysql_real_escape_string($str) ;
	}
}
*/

//Returns the risk level of the highest-risk condition for an individual
function getHighestMedicalRisk( $gibbonPersonID, $connection2 ) {
	$output==FALSE ;
	
	try {
		$dataAlert=array("gibbonPersonID"=>$gibbonPersonID); 
		$sqlAlert="SELECT * FROM gibbonPersonMedical JOIN gibbonPersonMedicalCondition ON (gibbonPersonMedical.gibbonPersonMedicalID=gibbonPersonMedicalCondition.gibbonPersonMedicalID) JOIN gibbonAlertLevel ON (gibbonPersonMedicalCondition.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonAlertLevel.sequenceNumber DESC" ;
		$resultAlert=$connection2->prepare($sqlAlert);
		$resultAlert->execute($dataAlert);
	}
	catch(PDOException $e) { }

	if ($resultAlert->rowCount()>0) {
		$rowAlert=$resultAlert->fetch() ;
		$output=array() ;
		$output[0]=$rowAlert["gibbonAlertLevelID"] ;
		$output[1]=$rowAlert["name"] ;
		$output[2]=$rowAlert["nameShort"] ;
		$output[3]=$rowAlert["color"] ;
		$output[4]=$rowAlert["colorBG"] ;
	}
	
	return $output ;
}

//Gets age from date of birth, in days and months, from Unix timestamp
function getAge($stamp, $short=FALSE) {
	$output="" ;
	$diff=mktime()-$stamp ;
	$years=floor($diff/31556926); 
	$months=floor(($diff-($years*31556926))/2629743.83) ;
	if ($short==TRUE) {
		$output=$years . "y, " . $months . "m" ;
	}
	else {
		$output=$years . " years, " . $months . " months" ;
	}
	return $output ;
}

//Looks at the grouped actions accessible to the user in the current module and returns the highest
function getHighestGroupedAction($guid, $address, $connection2) {
	$output=FALSE ;
	$moduleID=checkModuleReady($address, $connection2 );
	
	try {
		$data=array("actionName"=>"%" . getActionName($address) . "%", "gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "moduleID"=>$moduleID); 
		$sql="SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.URLList LIKE :actionName) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=:moduleID) ORDER BY precedence DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
		if ($result->rowCount()>0) {
			$row=$result->fetch() ;
			$output=$row["name"] ;
		}
	}
	catch(PDOException $e) { }
	
	return $output ;
}

//Returns the category of the specified role
function getRoleCategory($gibbonRoleID, $connection2) {
	$output=FALSE ;
	
	try {
		$data=array("gibbonRoleID"=>$gibbonRoleID); 
		$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		$output=$row["category"] ;
	}
	return $output ;
}

//Converts a specified date (YYYY-MM-DD) into a UNIX timestamp
function dateConvertToTimestamp( $date ) {
	list($dateYear, $dateMonth, $dateDay) = explode('-', $date);
	$timestamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
	
	return $timestamp ;
}

//Checks to see if a specified date (YYYY-MM-DD) is a day where school is open in the current academic year. There is an option to search all years
function isSchoolOpen($guid, $date, $connection2, $allYears="" ) {
	//Set test variables
	$isInTerm=FALSE ;
	$isSchoolDay=FALSE ;
	$isSchoolOpen=FALSE ;
	
	//Turn $date into UNIX timestamp and extract day of week
	$timestamp=dateConvertToTimestamp($date) ;
	$dayOfWeek=date("D",$timestamp) ;
	
	//See if date falls into a school term
	try {
		$data=array(); 
		$sqlWhere="" ;
		if ($allYears!=TRUE) {
			$data[$_SESSION[$guid]["gibbonSchoolYearID"]]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
			$sqlWhere=" AND gibbonSchoolYear.gibbonSchoolYearID=:" . $_SESSION[$guid]["gibbonSchoolYearID"] ;
		}
		
		$sql="SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID $sqlWhere" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	while ($row=$result->fetch()) {
		if ($date>=$row["firstDay"] AND $date<=$row["lastDay"]) {
			$isInTerm=TRUE ;
		}
	}
	
	//See if date's day of week is a school day
	if ($isInTerm==TRUE) {
		try {
			$data=array("nameShort"=>$dayOfWeek); 
			$sql="SELECT * FROM gibbonDaysOfWeek WHERE nameShort=:nameShort AND schoolDay='Y'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		if ($result->rowCount()>0) {
			$isSchoolDay=TRUE ;
		}
	}
	
	//See if there is a special day
	if ($isInTerm==TRUE AND $isSchoolDay==TRUE) {
		try {
			$data=array("date"=>$date); 
			$sql="SELECT * FROM gibbonSchoolYearSpecialDay WHERE type='School Closure' AND date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		
		if ($result->rowCount()<1) {
			$isSchoolOpen=TRUE ;
		}
	}
	
	return $isSchoolOpen ;
}

//Prints a given user photo, or a blank if not available
function printUserPhoto($guid, $path, $size) {
	$sizeStyle="style='width: 75px; height: 100px'" ;
	if ($size==240) {
		$sizeStyle="style='width: 240px; height: 320px'" ;
	}
	if ($path=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $path)==FALSE) {    
		print "<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_" . $size . ".jpg'/><br/>" ;
	}
	else {
		print "<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/$path'/><br/>" ;
	}
}

//Gets a given user photo, or a blank if not available
function getUserPhoto($guid, $path, $size) {
	$output="" ;
	$sizeStyle="style='width: 75px; height: 100px'" ;
	if ($size==240) {
		$sizeStyle="style='width: 240px; height: 320px'" ;
	}
	if ($path=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $path)==FALSE) {    
		$output="<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_" . $size . ".jpg'/><br/>" ;
	}
	else {
		$output="<img $sizeStyle class='user' src='" . $_SESSION[$guid]["absoluteURL"] . "/$path'/><br/>" ;
	}
	return $output ;
}

//Gets Members of a roll group and prints them as a table.
function printRollGroupTable($guid, $gibbonRollGroupID, $columns, $connection2, $confidential=TRUE) {
	try {
		$dataRollGroup=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
		$sqlRollGroup="SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
		$resultRollGroup=$connection2->prepare($sqlRollGroup);
		$resultRollGroup->execute($dataRollGroup);
	}
	catch(PDOException $e) { }
	
	print "<table class='noIntBorder' cellspacing='0' style='width:100%'>" ;
	$count=0 ;
	
	if ($confidential) {
		print "<tr>" ;
			print "<td style='text-align: right' colspan='$columns'>" ;
				print "<input checked type='checkbox' name='confidential' class='confidential' value='Yes' />" ;
				print "<span style='font-size: 85%; font-weight: normal; font-style: italic'> Show Confidential Data</span>" ;
			print "</td>" ;
		print "</tr>" ;
	}
	
	while ($rowRollGroup=$resultRollGroup->fetch()) {
		if ($count%$columns==0) {
			print "<tr>" ;
		}
		print "<td style='width:20%; text-align: center; vertical-align: top'>" ;
		
		//Alerts, if permission allows
		if ($confidential) {
			print getAlertBar($guid, $connection2, $rowRollGroup["gibbonPersonID"], $rowRollGroup["privacy"], "id='confidential$count'") ;
		}
		
		//User photo
		printUserPhoto($guid, $rowRollGroup["image_75"], 75) ;
		
		//HEY SHORTY IT'S YOUR BIRTHDAY!
		$daysUntilNextBirthday=daysUntilNextBirthday($rowRollGroup["dob"]) ;
		if ($daysUntilNextBirthday==0) {
			print "<img title='" . $rowRollGroup["preferredName"] . "&#39;s birthday today!' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift_pink.png'/>" ;
		}
		else if ($daysUntilNextBirthday>0 AND $daysUntilNextBirthday<8) {
			print "<img title='$daysUntilNextBirthday day" ;
			if ($daysUntilNextBirthday!=1) {
				print "s" ;
			}
			print " until " . $rowRollGroup["preferredName"] . "&#39;s birthday!' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift.png'/>" ;
		}
		
		print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowRollGroup["gibbonPersonID"] . "'>" . formatName("", $rowRollGroup["preferredName"], $rowRollGroup["surname"], "Student") . "</a><br/><br/></div>" ;
		print "</td>" ;
		
		if ($count%$columns==($columns-1)) {
			print "</tr>" ;
		}
		$count++ ;
	}
	
	for ($i=0;$i<$columns-($count%$columns);$i++) {
		print "<td></td>" ;
	}
	
	if ($count%$columns!=0) {
		print "</tr>" ;
	}
	
	print "</table>" ;	
	
	?>
	<script type="text/javascript">
		/* Confidential Control */
		$(document).ready(function(){
			$(".confidential").click(function(){
				if ($('input[name=confidential]:checked').val() == "Yes" ) {
					<?
					for ($i=0; $i<$count; $i++) {
						?>
						$("#confidential<? print $i ?>").slideDown("fast", $("#confidential<? print $i ?>").css("{'display' : 'table-row', 'border' : 'right'}")); 
						<?
					}
					?>
				} 
				else {
					<?
					for ($i=0; $i<$count; $i++) {
						?>
						$("#confidential<? print $i ?>").slideUp("fast"); 
						<?
					}
					?>
				}
			 });
		});
	</script>
	<?
}

//Gets Members of a roll group and prints them as a table.
function printClassGroupTable($guid, $gibbonCourseClassID, $columns, $connection2) {
	try {
		$dataClassGroup=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
		$sqlClassGroup="SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName" ;
		$resultClassGroup=$connection2->prepare($sqlClassGroup);
		$resultClassGroup->execute($dataClassGroup);
	}
	catch(PDOException $e) { }
	
	print "<table class='noIntBorder' cellspacing='0' style='width:100%'>" ;
	$count=0 ;
	while ($rowClassGroup=$resultClassGroup->fetch()) {
		if ($count%$columns==0) {
			print "<tr>" ;
		}
		print "<td style='width:20%; text-align: center; vertical-align: top'>" ;
		
		//Alerts, if permission allows
		print getAlertBar($guid, $connection2, $rowClassGroup["gibbonPersonID"], $rowClassGroup["privacy"]) ;
		
		//User photo
		printUserPhoto($guid, $rowClassGroup["image_75"], 75) ;
		
		//HEY SHORTY IT'S YOUR BIRTHDAY!
		$daysUntilNextBirthday=daysUntilNextBirthday($rowClassGroup["dob"]) ;
		if ($daysUntilNextBirthday==0) {
			print "<img title='" . $rowClassGroup["preferredName"] . "&#39;s birthday today!' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift_pink.png'/>" ;
		}
		else if ($daysUntilNextBirthday>0 AND $daysUntilNextBirthday<8) {
			print "<img title='$daysUntilNextBirthday day" ;
			if ($daysUntilNextBirthday!=1) {
				print "s" ;
			}
			print " until " . $rowClassGroup["preferredName"] . "&#39;s birthday!' style='z-index: 99; margin: -20px 0 0 74px; width: 25px; height: 25px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift.png'/>" ;
		}
		
		if ($rowClassGroup["role"]=="Student") {
			print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowClassGroup["gibbonPersonID"] . "'>" . formatName("", $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Student") . "</a></b><br/>" ;
		}
		else {
			print "<div style='padding-top: 5px'><b>" . formatName( $rowClassGroup["title"], $rowClassGroup["preferredName"], $rowClassGroup["surname"], "Staff") . "</b><br/>" ;
		}
		
		print "<i>" . $rowClassGroup["role"] . "</i><br/><br/></div>" ;
		print "</td>" ;
		
		if ($count%$columns==($columns-1)) {
			print "</tr>" ;
		}
		$count++ ;
	}
	
	for ($i=0;$i<$columns-($count%$columns);$i++) {
		print "<td></td>" ;
	}
	
	if ($count%$columns!=0) {
		print "</tr>" ;
	}
	
	print "</table>" ;	
}

function getAlertBar($guid, $connection2, $gibbonPersonID, $privacy="", $divExtras="", $div=TRUE) {
	$output="" ;
	
	$highestAction=getHighestGroupedAction($guid, "/modules/Students/student_view_details.php", $connection2) ;
	if ($highestAction=="View Student Profile_full") {
		if ($div==TRUE) {
			$output.="<div $divExtras style='width: 83px; text-align: right; height: 16px; padding: 3px 0px; margin: auto'><b>" ;
		}
		
		//Individual Needs
		try {
			$dataAlert=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlAlert="SELECT * FROM gibbonINPersonDescriptor JOIN gibbonAlertLevel ON (gibbonINPersonDescriptor.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC" ;
			$resultAlert=$connection2->prepare($sqlAlert);
			$resultAlert->execute($dataAlert);
		}
		catch(PDOException $e) { }
		if ($resultAlert->rowCount()>0) {
			$rowAlert=$resultAlert->fetch() ;
			$highestLevel=$rowAlert["name"] ;
			$highestColour=$rowAlert["color"] ;
			$highestColourBG=$rowAlert["colorBG"] ;
			if ($resultAlert->rowCount()==1) {
				$title=$resultAlert->rowCount() . " Individual Needs alert is set, with an alert level of " . $rowAlert["name"] ;
			}
			else {
				$title=$resultAlert->rowCount() . " Individual Needs alerts are set, up to a maximum alert level of " . $rowAlert["name"] ;
			}
			$output.="<a style='color: #" . $highestColour . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Individual Needs'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $highestColour . "; margin-right: 2px; background-color: #" . $highestColourBG . "'>IN</div></a>" ; 
		}
		
		//Academic
		$gibbonAlertLevelID="" ;
		try {
			$dataAlert=array("gibbonPersonIDStudent"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlAlert="SELECT * FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND (attainmentConcern='Y' OR effortConcern='Y') AND complete='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$resultAlert=$connection2->prepare($sqlAlert);
			$resultAlert->execute($dataAlert);
		}
		catch(PDOException $e) { 
			$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultAlert->rowCount()>1 AND $resultAlert->rowCount()<=4) {
			$gibbonAlertLevelID=003 ;
		}
		else if ($resultAlert->rowCount()>4 AND $resultAlert->rowCount()<=8) {
			$gibbonAlertLevelID=002 ;
		}
		else if ($resultAlert->rowCount()>8) {
			$gibbonAlertLevelID=001 ;	
		}
		if ($gibbonAlertLevelID!="") {
			$alert=getAlert($connection2, $gibbonAlertLevelID) ;
			if ($alert!=FALSE) {
				$title="Student has a " . $alert["name"] . " alert for academic concern in the current academic year." ;
				$output.="<a style='color: #" . $alert["color"] . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Markbook&filter=" . $_SESSION[$guid]["gibbonSchoolYearID"] . "'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $alert["color"] . "; margin-right: 2px; background-color: #" . $alert["colorBG"] . "'>A</div></a>" ; 
			}
		}
		
		//Behaviour
		$gibbonAlertLevelID="" ;
		try {
			$dataAlert=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlAlert="SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND type='Negative' AND date>'" . date("Y-m-d", (time()-(24*60*60*60))) . "'" ;
			$resultAlert=$connection2->prepare($sqlAlert);
			$resultAlert->execute($dataAlert);
		}
		catch(PDOException $e) { 
			$_SESSION[$guid]["sidebarExtra"].="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultAlert->rowCount()>1 AND $resultAlert->rowCount()<=4) {
			$gibbonAlertLevelID=003 ;
		}
		else if ($resultAlert->rowCount()>4 AND $resultAlert->rowCount()<=8) {
			$gibbonAlertLevelID=002 ;
		}
		else if ($resultAlert->rowCount()>8) {
			$gibbonAlertLevelID=001 ;	
		}
		if ($gibbonAlertLevelID!="") {
			$alert=getAlert($connection2, $gibbonAlertLevelID) ;
			if ($alert!=FALSE) {
				$title="Student has a " . $alert["name"] . " alert for behaviour over the past 60 days." ;
				$output.="<a style='color: #" . $alert["color"] . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Behaviour Record'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $alert["color"] . "; margin-right: 2px; background-color: #" . $alert["colorBG"] . "'>B</div></a>" ; 
			}
		}
		
		//Medical
		$alert=getHighestMedicalRisk( $gibbonPersonID, $connection2 ) ;
		if ($alert!=FALSE) {
			$highestLevel=$alert[1] ;
			$highestColour=$alert[3] ;
			$highestColourBG=$alert[4] ;
			$title="Medical alerts are set, up to a maximum of " . $highestLevel ;
			$output.="<a style='color: #" . $highestColour . "; text-decoration: none' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID . "&subpage=Medical'><div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $highestColour . "; margin-right: 2px; background-color: #" . $highestColourBG . "'><b>M</b></div></a>" ; 
		}
		
		//Privacy
		$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
		if ($privacySetting=="Y" AND $privacy!="") {
			$alert=getAlert($connection2, 001) ;
			$title="Privacy is required: $privacy" ;
			$output.="<div title='$title' style='float: right; text-align: center; vertical-align: middle; max-height: 14px; height: 14px; width: 14px; border-top: 2px solid #" . $alert["color"] . "; margin-right: 2px; color: #" . $alert["color"] . "; background-color: #" . $alert["colorBG"] . "'>P</div>" ; 
		}
		
		if ($div==TRUE) {
			$output.="</div>" ;
		}
	}
	
	return $output ;
}

//Gets system settings from database and writes them to individual session variables.
function getSystemSettings($guid, $connection2) {
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSetting WHERE scope='System'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$_SESSION[$guid]["systemSettingsSet"]=FALSE ;
	}

	while ($row=$result->fetch()) {
		$name=$row["name"] ;
		$_SESSION[$guid][$name]= $row["value"] ;
	}
	$_SESSION[$guid]["systemSettingsSet"]=TRUE ;
}

//Gets the desired setting, specified by name and scope.
function getSettingByScope( $connection2, $scope, $name ) {
	$output=FALSE ;
	
	try {
		$data=array("scope"=>$scope, "name"=>$name); 
		$sql="SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		$output=$row["value"] ;
		
	}
	
	return $output ;
}

//Converts date from dd/mm/yyyy to YYYY-MM-DD
function dateConvert ($date) {
	$output=FALSE ;
	
	if ($date!="") {
		$firstSlashPosition=2 ;
		$secondSlashPosition=5 ;
		
		$output=substr($date,($secondSlashPosition+1)) . "-" . substr($date,($firstSlashPosition+1),2) . "-" . substr($date,0,$firstSlashPosition) ; 
	}
	return $output ;
}

//Converts date from YYYY-MM-DD to dd/mm/yyyy
function dateConvertBack ($date) {
	$output=FALSE ;
	
	if ($date!="") {
		$firstSlashPosition=4 ;
		$secondSlashPosition=7 ;
		
		$output=substr($date,($secondSlashPosition+1)) . "/" . substr($date,($firstSlashPosition+1),2) . "/" . substr($date,0,$firstSlashPosition) ; 
	}
	return $output ;
}

function isActionAccessible($guid, $connection2, $address, $sub="") {
	$output=FALSE ;
	//Check user is logged in
	if ($_SESSION[$guid]["username"]!="") {
		//Check user has a current role set
		if ($_SESSION[$guid]["gibbonRoleIDCurrent"]!="") {
			//Check module ready
			$moduleID=checkModuleReady($address, $connection2);
			if ($moduleID!=FALSE) {
				//Check current role has access rights to the current action.
				try {
					$data=array("actionName"=>"%" . getActionName($address) . "%", "gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"]); 
					$sqlWhere="" ;
					if ($sub!="") {
						$data["sub"]=$sub ;
						$sqlWhere="AND gibbonAction.name=:sub" ;
					}
					$sql="SELECT gibbonAction.name FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.URLList LIKE :actionName) AND (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=$moduleID) $sqlWhere" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
					if ($result->rowCount()>0) {
						$output=TRUE ;
					}
				}
				catch(PDOException $e) {}
			}
		}
	}
	return $output ;
}

function isModuleAccessible($guid, $connection2, $address="") {
	if ($address=="") {
		$address=$_SESSION[$guid]["address"];
	}
	$output=FALSE ;
	//Check user is logged in
	if ($_SESSION[$guid]["username"]!="") {
		//Check user has a current role set
		if ($_SESSION[$guid]["gibbonRoleIDCurrent"]!="") {
			//Check module ready
			$moduleID=checkModuleReady($address, $connection2 );
			if ($moduleID!=FALSE) {
				//Check current role has access rights to an action in the current module.
				try {
					$data=array("gibbonRoleID"=>$_SESSION[$guid]["gibbonRoleIDCurrent"], "moduleID"=>$moduleID); 
					$sql="SELECT * FROM gibbonAction, gibbonPermission, gibbonRole WHERE (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID) AND (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID) AND (gibbonPermission.gibbonRoleID=:gibbonRoleID) AND (gibbonAction.gibbonModuleID=:moduleID)" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
					if ($result->rowCount()>0) {
						$output=TRUE ;
					}
				}
				catch(PDOException $e) {}
			}
		}
	}
	
	return $output ;
}

function printPagination($guid, $total, $page, $pagination, $position, $get="") {
	if ($position=="bottom") {
		$class="paginationBottom" ;
	}
	else {
		$class="paginationTop" ;
	}
	
	print "<div class='$class'>" ;
		$totalPages=ceil($total/$pagination) ;
		$i=0;
		print "Records " . (($page-1)*$_SESSION[$guid]["pagination"]+1) . "-" ;
		if (($page*$_SESSION[$guid]["pagination"])>$total) {
			print $total ;
		}
		else {
			print ($page*$_SESSION[$guid]["pagination"]) ;
		}
		print " of " . $total . " : " ;
		
		if ($totalPages<=10) {
			for ($i=0;$i<=($total/$pagination);$i++) {
				if ($i==($page-1)) {
					print "$page " ;
				}
				else {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($i+1) . "&$get'>" . ($i+1) . "</a> " ; 
				}
			}
		}
		else {
			if ($page>1) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=1&$get'>First</a> " ; 
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($page-1) . "&$get'>Previous</a> " ; 
			}
			else {
				print "First Previous " ;
			}
			
			$spread=10 ;
			for ($i=0;$i<=($total/$pagination);$i++) {
				if ($i==($page-1)) {
					print "$page " ;
				}
				else if ($i>($page-(($spread/2)+2)) AND $i<($page+(($spread/2)))) {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($i+1) . "&$get'>" . ($i+1) . "</a> " ; 
				}
			}
			
			if ($page!=$totalPages) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . ($page+1) . "&$get'>Next</a> " ; 
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_SESSION[$guid]["address"] . "&page=" . $totalPages . "&$get'>Last</a> " ; 
			}
			else {
				print "Next Last" ;
			}
			
			
		}
	print "</div>" ;
}

//Get list of user roles from database, and convert to array
function getRoleList( $gibbonRoleIDAll, $connection2 ) {
	session_start() ;
	
	$output=array() ;
	
	//Tokenise list of roles
	$roles=explode(',', $gibbonRoleIDAll) ;
	
	//Check that roles exist
	$count=0 ;
	for ($i=0; $i<count($roles); $i++) {
		try {
			$data=array("gibbonRoleID"=>$roles[$i]); 
			$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }
		if ($result->rowCount()==1) {
			$row = $result->fetch() ;
			$output[$count][0]=$row["gibbonRoleID"] ;
			$output[$count][1]=$row["name"] ;
			$count++ ;
		}
	}
	
	//Return list of roles
	return $output ;
}

//Get the module name from the address
function getModuleName($address) {
	return substr(substr($address,9),0,strpos(substr($address,9),"/")) ;
}

//Get the action name from the address
function getActionName($address) {
	return substr($address,(10+strlen(getModuleName($address)))) ;
}

//Using the current address, checks to see that a module exists and is ready to use, returning the ID if it is
function checkModuleReady($address, $connection2) {
	$output=FALSE;
	
	//Get module name from address
	$module=getModuleName($address) ;
	try {
		$data=array("name"=>$module); 
		$sql="SELECT * FROM gibbonModule WHERE name=:name AND active='Y'" ;
 		$result=$connection2->prepare($sql);
		$result->execute($data);
		if ($result->rowCount()==1) {
			$row=$result->fetch() ;
			$output=$row["gibbonModuleID"] ;
		}
	}
	catch(PDOException $e) { }
		
	return $output ;
}

//Using the current address, get's the module's category
function getModuleCategory($address, $connection2 ) {
	$output=FALSE;
	
	//Get module name from address
	$module=getModuleName($address) ;
	
	try {
		$data=array("name"=>$module); 
		$sql="SELECT * FROM gibbonModule WHERE name=:name AND active='Y'" ;
 		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		$output=$row["category"] ;
	}
		
	return $output ;
}

//GET THE CURRENT YEAR AND SET IT AS A GLOBAL VARIABLE
function setCurrentSchoolYear($guid,  $connection2 ) {
	session_start() ;

	//Run query
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSchoolYear WHERE status='Current'";
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }

	//Check number of rows returned.
	//If it is not 1, show error
	if (!($result->rowCount()==1)) {
		die("Configuration Error: there is a problem accessing the current Academic Year from the database.") ;
	}
	//Else get schoolYearID
	else {
		$row = $result->fetch() ;
		$_SESSION[$guid]["gibbonSchoolYearID"]=$row["gibbonSchoolYearID"] ;
		$_SESSION[$guid]["gibbonSchoolYearName"]=$row["name"] ;
		$_SESSION[$guid]["gibbonSchoolYearSequenceNumber"]=$row["sequenceNumber"] ;
		$_SESSION[$guid]["gibbonSchoolYearFirstDay"]=$row["firstDay"] ;
		$_SESSION[$guid]["gibbonSchoolYearLastDay"]=$row["lastDay"] ;
	}
}

function nl2brr($string) {
	return preg_replace("/\r\n|\n|\r/", "<br/>", $string);
}

//Take a school year, and return the previous one, or false if none
function getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) {
	$output==FALSE ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowcount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonSchoolYear WHERE sequenceNumber<:sequenceNumber ORDER BY sequenceNumber DESC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonSchoolYearID"] ;	
		}
	}
	
	return $output ;
}

//Take a school year, and return the previous one, or false if none
function getNextSchoolYearID($gibbonSchoolYearID, $connection2) {		
	$output==FALSE ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowcount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonSchoolYearID"] ;	
		}
	}
	
	return $output ;
}

//Take a year group, and return the next one, or false if none
function getNextYearGroupID($gibbonYearGroupID, $connection2) {
	$output==FALSE ;
	try {
		$data=array("gibbonYearGroupID"=>$gibbonYearGroupID); 
		$sql="SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonYearGroup WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonYearGroupID"] ;	
		}
	}
	
	return $output ;
}

//Return the last school year in the school, or false if none
function getLastYearGroupID($connection2) {
	$output==FALSE ;
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowCount()>1) {
		$row=$result->fetch() ;
		$output=$row["gibbonYearGroupID"] ;	
	}
	
	return $output ;
}

function randomPassword($length) {
  if (!(is_int($length))) {
  	$length=8 ;
  }
  else if ($length>255) {
  	$length=255;
  }
  
  $charList = "abcdefghijkmnopqrstuvwxyz023456789-_";
  $password = '' ;
  
  	//Generate the password
  	for ($i=0;$i<$length;$i++) {
  		$password = $password . substr($charList, rand(1,strlen($charList)),1);
  	}
  
  	return $password;
}


/*	Author: Raju Mazumder
 	email:rajuniit@gmail.com
	Class:A simple class to export mysql query and whole html and php page to excel,doc etc
 	Downloaded from: http://webscripts.softpedia.com/script/PHP-Clases/Export-To-Excel-50394.html
	License: GNU GPL 
*/
class ExportToExcel
{
	function setHeader($excel_file_name)//this function used to set the header variable
	{	
		header("Content-type: application/octet-stream");//A MIME attachment with the content type "application/octet-stream" is a binary file.
		//Typically, it will be an application or a document that must be opened in an application, such as a spreadsheet or word processor. 
		header("Content-Disposition: attachment; filename=$excel_file_name");//with this extension of file name you tell what kind of file it is.
		header("Pragma: no-cache");//Prevent Caching
		header("Expires: 0");//Expires and 0 mean that the browser will not cache the page on your hard drive
	}
	function exportWithQuery($qry,$excel_file_name,$conn)//to export with query
	{
		try {
			$tmprst=$conn->query($qry);  
			$tmprst->setFetchMode(PDO::FETCH_NUM); 
		}
		catch(PDOException $e) { }

		$header="<center><table cellspacing='0' border=1px>";
		$num_field=$tmprst->columnCount();
		while($row=$tmprst->fetch())
		{
			$body.="<tr>";
			for($i=0;$i<$num_field;$i++)
			{
				$body.="<td>".$row[$i]."</td>";
			}
			$body.="</tr>";	
		}
		
		$this->setHeader($excel_file_name);
		echo $header.$body."</table>";
	}
	//Ross Parker added the ability to specify paramaters to pass into the file, via a session variable.
	function exportWithPage($guid, $php_page,$excel_file_name,$params="")
	{
		$this->setHeader($excel_file_name);
		$_SESSION[$guid]["exportToExcelParams"]=$params ;
		require_once "$php_page";
	}
}
?>