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

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manageRiskTemplates.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {

    $edit = false;

    if (isset($_GET['tripPlannerRiskTemplateID'])) {
        $tripPlannerRiskTemplateID = $_GET['tripPlannerRiskTemplateID'];
        if($tripPlannerRiskTemplateID != '' && $tripPlannerRiskTemplateID != null) {
            $edit = true;
        }
    }

    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manageRiskTemplates.php'>" . _("Risk Assessment Templates") . "</a> > </div><div class='trailEnd'>" . _(($edit ? "Edit" : "Add") . ' Risk Assessment Templates') . "</div>";
    print "</div>";

    print "<h3>";
        print __m(($edit ? "Edit" : "Add") . " Risk Assessment Template");
    print "</h3>";

    if (isset($_GET['return'])) {
        $editLink = null;
        if(isset($_GET['tripPlannerRiskTemplateID'])) {
            $editLink = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_addRiskTemplate.php&tripPlannerRiskTemplateID=" . $_GET['tripPlannerRiskTemplateID'];
        }
        returnProcess($guid, $_GET['return'], $editLink, null);
    }   

    if ($edit) {
        try {
            $dataTemplates = array("tripPlannerRiskTemplateID" => $tripPlannerRiskTemplateID);
            $sqlTemplates = "SELECT name, body FROM tripPlannerRiskTemplates WHERE tripPlannerRiskTemplateID=:tripPlannerRiskTemplateID";
            $resultTemplates = $connection2->prepare($sqlTemplates);
            $resultTemplates->execute($dataTemplates);
            $template = $resultTemplates->fetch();
        } catch(PDOException $e) {

        }
    }

    $form = Form::create("addRiskTemplate", $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_addRiskTemplateProcess.php" . ($edit ? "?tripPlannerRiskTemplateID=$tripPlannerRiskTemplateID" : ""));

    $row = $form->addRow();
        $row->addLabel("nameLabel", "Name *");
        $row->addTextfield('name')->setRequired(true)->setValue((isset($template) ? $template['name'] : ""));

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel("bodyLabel", "Body *");
        $column->addEditor('body', $guid)->setRequired(true)->setValue((isset($template) ? $template['body'] : ""));
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    print $form->getOutput();
}   
?>
