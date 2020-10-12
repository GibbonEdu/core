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

include "./modules/Trip Planner/moduleFunctions.php";

use Gibbon\Forms\Form;

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manageSettings.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Settings') . "</div>";
    print "</div>";

    print "<h3>";
        print "Settings";
    print "</h3>";

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $sql = "SELECT name, nameDisplay, description, value FROM gibbonSetting WHERE scope='Trip Planner' ORDER BY gibbonSettingID ASC";
        $result = $connection2->prepare($sql);
        $result->execute();
    } catch(PDOException $e) {
    }

    $templates = array("-1" => "None", "0" => "Custom");

    try {
        $sqlTemplates = "SELECT tripPlannerRiskTemplateID, name FROM tripPlannerRiskTemplates ORDER BY name ASC";
        $resultTemplates = $connection2->prepare($sqlTemplates);
        $resultTemplates->execute();
    } catch(PDOException $e) {
    }

    while ($rowTemplates = $resultTemplates->fetch()) {
        $templates[$rowTemplates["tripPlannerRiskTemplateID"]] = $rowTemplates["name"];
    }

    $form = Form::create("tripPlannerSettings", $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_manageSettingsProcess.php");

    while ($row = $result->fetch()) {
        if ($row["name"] == "requestEditing") continue;
        $fRow = $form->addRow();
            if ($row["name"] == "riskAssessmentTemplate" || $row["name"] == "letterToParentsTemplate") {
                $col = $fRow->addColumn();
                $col->addLabel($row["name"], $row["nameDisplay"])->description($row["description"]);
            } else {
                $fRow->addLabel($row["name"], $row["nameDisplay"])->description($row["description"]);
            }

            switch($row["name"]) {
                case "requestApprovalType":
                    $fRow->addSelect($row["name"])->fromArray(array("One Of", "Two Of", "Chain Of All"))->selected($row["value"])->setRequired(true);
                    break;
                case "riskAssessmentTemplate":
                    $col->addEditor($row["name"], $guid)->setValue($row["value"])->setRows(15);
                    break;
                case "missedClassWarningThreshold":
                    $fRow->addNumber($row["name"])->minimum(0)->setRequired(true)->decimalPlaces(0)->setValue($row["value"]);
                    break;
                case "expiredUnapprovedFilter":
                case "riskAssessmentApproval":
                    $fRow->addCheckBox($row["name"])->checked((int)$row["value"]);
                    break;
                case "defaultRiskTemplate":
                    $fRow->addSelect($row["name"])->fromArray($templates)->selected($row["value"])->setRequired(true);
                    break;
                case "letterToParentsTemplate":
                    $col->addEditor($row["name"], $guid)->setValue($row["value"])->setRows(15);
                    break;
                default:
                    break;
            }
    }

    $fRow = $form->addRow();
        $fRow->addFooter();
        $fRow->addSubmit();

    print $form->getOutput();
}   
?>
