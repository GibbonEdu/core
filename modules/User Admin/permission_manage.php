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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/permission_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Permissions') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=sprintf(_('Your PHP environment cannot handle all of the fields in this form (the current limit is %1$s). Ask your web host or system administrator to increase the value of the max_input_vars in php.ini.'), ini_get("max_input_vars")) ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	try {
		$dataModules=array(); 
		$sqlModules="SELECT * FROM gibbonModule ORDER BY name" ;
		$resultModules=$connection2->prepare($sqlModules);
		$resultModules->execute($dataModules);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	try {
		$dataRoles=array(); 
		$sqlRoles="SELECT * FROM gibbonRole ORDER BY type, nameShort" ;
		$resultRoles=$connection2->prepare($sqlRoles);
		$resultRoles->execute($dataRoles);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	try {
		$dataPermissions=array(); 
		$sqlPermissions="SELECT * FROM gibbonPermission" ;
		$resultPermissions=$connection2->prepare($sqlPermissions);
		$resultPermissions->execute($dataPermissions);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	if ($resultRoles->rowCount()<1 OR $resultModules->rowCount()<1) {
		print "<div class='error'>" ;
		print _("Your request failed due to a database error.") ;	
		print "</div>" ;
	}
	else {
		//Fill role array
		$roleArray=array() ;
		$count=0 ;
		while ($rowRoles=$resultRoles->fetch()) {
			$roleArray["$count"][0]=$rowRoles["gibbonRoleID"];
			$roleArray["$count"][1]=$rowRoles["nameShort"];
			$roleArray["$count"][2]=$rowRoles["category"];
			$roleArray["$count"][3]=$rowRoles["name"];
			$count++ ;
		}
		
		//Fill permission array
		$permissionsArray=array() ;
		$count=0 ;
		while ($rowPermissions=$resultPermissions->fetch()) {
			$permissionsArray["$count"][0]=$rowPermissions["gibbonRoleID"];
			$permissionsArray["$count"][1]=$rowPermissions["gibbonActionID"];
			$count++ ;
		}
	
		$totalCount=0 ;
		print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/permission_manageProcess.php'>" ;
			print "<input type='hidden' name='address' value='" .$_SESSION[$guid]["address"] . "'>" ;
			print "<table class='mini' cellspacing='0' style='width: 100%'>" ;
			while ($rowModules=$resultModules->fetch()) {
				print "<tr class='break'>" ;
					print "<td colspan=" . ($resultRoles->rowCount()+1) . ">" ;
						print "<h3>" . _($rowModules["name"]) . "</h3>";
					print "</td>" ;
				print "</tr>" ;
				
				try {
					$dataActions=array("gibbonModuleID"=>$rowModules["gibbonModuleID"]); 
					$sqlActions="SELECT * FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID ORDER BY name" ;
					$resultActions=$connection2->prepare($sqlActions);
					$resultActions->execute($dataActions);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}		
				
				if ($resultActions->rowCount()>0) {
					print "<tr class='head'>" ;
						print "<th class='width: 60px!important'>Action</td>";
						for ($i=0;$i<count($roleArray);$i++) {
							print "<th style='padding: 0!important'><span title='" . htmlPrep(_($roleArray[$i][3])) . "'>" . _($roleArray[$i][1]) . "</span></th>";
						}
					print "</tr>" ;
					while ($rowActions=$resultActions->fetch()) {
						print "<tr>" ;
						print "<td><span title='" . htmlPrep(_($rowActions["description"])) . "'>" . _($rowActions["name"]) . "</span></td>";
							for ($i=0;$i<$resultRoles->rowCount();$i++) {
								print "<td>" ;
									$checked="" ;
									for ($x=0;$x<count($permissionsArray);$x++) {
										if ($permissionsArray[$x][0]==$roleArray[$i][0] AND $permissionsArray[$x][1]==$rowActions["gibbonActionID"]) {
											$checked="checked" ;
										}
									}
									
									$readonly="" ;
									if ($roleArray[$i][2]=="Staff") {
										if ($rowActions["categoryPermissionStaff"]=="N") {
											$readonly="disabled" ;
											$checked="" ;
										}
									}
									if ($roleArray[$i][2]=="Student") {
										if ($rowActions["categoryPermissionStudent"]=="N") {
											$readonly="disabled" ;
											$checked="" ;
										}
									}
									if ($roleArray[$i][2]=="Parent") {
										if ($rowActions["categoryPermissionParent"]=="N") {
											$readonly="disabled" ;
											$checked="" ;
										}
									}
									if ($roleArray[$i][2]=="Other") {
										if ($rowActions["categoryPermissionOther"]=="N") {
											$readonly="disabled" ;
											$checked="" ;
										}
									}
									
									print "<input $readonly $checked name='" . $rowActions["gibbonActionID"] . "-" . $roleArray[$i][0] . "' type='checkbox'/>" ;
									print "<input type='hidden' name='$totalCount' value='" . $rowActions["gibbonActionID"] . "-" . $roleArray[$i][0] . "'/>" ;
									$totalCount++ ;
								print "</td>";
							}
						print "</tr>" ;
					}
				}
			}
			$max_input_vars=ini_get('max_input_vars') ;
			$total_vars=(($totalCount*2)+10) ;
			$total_vars_rounded=(ceil($total_vars/1000)*1000)+1000;
			if ($total_vars>$max_input_vars) {
				print "<tr>" ;
					print "<td colspan=" . ($resultRoles->rowCount()+1) . ">" ;
						print "<div class='error'>" ;
						print "php.ini max_input_vars=" . $max_input_vars . "<br />";
						print _("Number of inputs on this page") . "=" . $total_vars . "<br/>";
						print sprintf(_('This form is very large and data will be truncated unless you edit php.ini. Add the line <i>max_input_vars=%1$s</i> to your php.ini file on your server.'), $total_vars_rounded) ;
						print "</div>" ;	
					print "</td>" ;
				print "</tr>" ;
			}
			else{			
				print "<tr>" ;
					print "<td style='padding-top: 20px' class='right' colspan=" . (count($roleArray)+1) . ">" ;
						print "<input type='submit' value='Submit'>" ;
					print "</td>" ;
				print "</tr>" ;
			}			
			print "</table>" ;
		print "</form>" ;
	}
}
?>