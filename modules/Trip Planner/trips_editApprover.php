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

//Module includes
include "./modules/Trip Planner/moduleFunctions.php";

use Gibbon\Forms\Form;

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_editApprover.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {

    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manageApprovers.php'>" . _("Manage Approver") . "</a></div> <div class='trailEnd'>> " . _('Edit Approver') . "</div>";
    print "</div>";

    if (isset($_GET["tripPlannerApproverID"])) {
        if ($_GET["tripPlannerApproverID"] != null && $_GET["tripPlannerApproverID"] != "") {
            $tripPlannerApproverID = $_GET["tripPlannerApproverID"];
        }
    }

    $approver = getApprover($connection2, $tripPlannerApproverID);

    print "<h3>";
        print "Edit Approver";
    print "</h3>";

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute();
    } catch (PDOException $e) {
    }

    $staff = array();

    $approverName = null;

    while ($rowSelect = $resultSelect->fetch()) {
        if (!isApprover($connection2, $rowSelect["gibbonPersonID"]) || $rowSelect["gibbonPersonID"] == $approver["gibbonPersonID"]) {
            if($rowSelect["gibbonPersonID"] == $approver["gibbonPersonID"]) $approverName = formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Staff", true, true);
            $staff[$rowSelect["gibbonPersonID"]] = formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Staff", true, true);
        }
    }

    $form = Form::create("editApprover", $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_editApproverProcess.php?tripPlannerApproverID=$tripPlannerApproverID");

    $row = $form->addRow();
        $row->addLabel("staffLabel", "Staff *");
        $row->addSelect("gibbonPersonID")->fromArray($staff)->setRequired(true)->selected($approverName);

    $requestApprovalType = getSettingByScope($connection2, "Trip Planner", "requestApprovalType");
    if ($requestApprovalType == "Chain Of All") {
        $row = $form->addRow();
            $row->addLabel("sequenceNumberLabel", "Sequence Number *")->description("Must be unique.");
            $row->addNumber("sequenceNumber")->minimum(0)->decimalPlaces(0)->setRequired(true)->value($approver['sequenceNumber']);
    }

    $riskAssessmentApproval = getSettingByScope($connection2, "Trip Planner", "riskAssessmentApproval");
    if($riskAssessmentApproval) {
        $row = $form->addRow();
            $row->addLabel("finalApproverLabel", "Final Approver");
            $row->addCheckbox("finalApprover")->checked($approver['finalApprover']);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    print $form->getOutput();
}   
?>
