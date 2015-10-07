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

if (isActionAccessible($guid, $connection2, "/modules/Tracking/dataPoints.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Data Points') . "</div>" ;
	print "</div>" ;

	//Check Settings
	$externalAssessmentDataPoints=unserialize(getSettingByScope($connection2, "Tracking", "externalAssessmentDataPoints")) ;
	$internalAssessmentDataPoints=unserialize(getSettingByScope($connection2, "Tracking", "internalAssessmentDataPoints")) ;
	if (count($externalAssessmentDataPoints)==0 AND count($internalAssessmentDataPoints)==0) { //Seems like things are not configured, so give appropriate information according to access
		print "<div class='warning'>" ;
			if (isActionAccessible($guid, $connection2, "/modules/School Admin/trackingSettings.php")==FALSE) { //No access, just give warning
				print sprintf(_('Data Points needs to be configured before use, but you do not have permission to do this. Please contact %1$s for help with this issue.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") ;
			}
			else { //Yes access, give link to settings.
				print sprintf(_('Data Points needs to be configured before use. Please take a look at %1$sTracking Settings%2$s to set up what data points to use.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/School Admin/trackingSettings.php'>", "</a>") ;
			}
		print "</div>" ;
	}
	else { //Seems like things are configured, so give welcome message.
		print "<p>" ;
			if (isActionAccessible($guid, $connection2, "/modules/School Admin/trackingSettings.php")==FALSE) { //No access, just give warning
				print sprintf(_('Data Points has been configured to allow you to export certain key assessment data. Please contact %1$s if you are not seeing the data you need here.'), "<a href='mailto:" . $_SESSION[$guid]["organisationAdministratorEmail"] . "'>" . $_SESSION[$guid]["organisationAdministratorName"] . "</a>") . "<br/>" ;
			}
			else { //Yes access, give link to settings.
				print sprintf(_('Data Points has been configured to export certain key assessment data. Please take a look at %1$sTracking Settings%2$s to change what data points are included.'), "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/School Admin/trackingSettings.php'>", "</a>") . "<br/>" ;
			}
			print "<br/>" ;
			print _("Use the export button below to prepare your Data Points export for download.") ;
		print "</p>" ;

		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_course_classExport.php'>" . _("Export to Excel") . " <img title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
		print "</div>" ;
	}

}
?>
