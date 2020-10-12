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

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manageRiskTemplates.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Risk Assessment Templates') . "</div>";
    print "</div>";

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    ?>

    <div class="linkTop">
        <a style='position:relative; bottom:10px; float:right;' href='<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_addRiskTemplate.php" ?>'>
            <?php
                print __m("Add");
            ?>
            <img style='margin-left: -2px' title='<?php print __m("Add");?>' src='<?php print $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png" ?>'/>
        </a>
    </div>

    <?php

    print "<h3>";
        print __m("Risk Assessment Templates");
    print "</h3>";

    try {
        $sqlTemplates = "SELECT tripPlannerRiskTemplateID, name, body FROM tripPlannerRiskTemplates ORDER BY name ASC";
        $resultTemplates = $connection2->prepare($sqlTemplates);
        $resultTemplates->execute();
    } catch(PDOException $e) {

    }

    print "<table cellspacing='0' style='width: 100%'>";
        print "<tr class='head'>";
            print "<th>";
                print __m("Name");
            print "</th>";
            print "<th>";
                print __m("Body");
            print "</th>";
            print "<th>";
                print __m("Action");
            print "</th>";
        print "</tr>";
        if ($resultTemplates->rowCount() > 0) {
            $rowCount = 0;
            while ($template = $resultTemplates->fetch()) {
                $class = "odd";
                if ($rowCount % 2 == 0) {
                    $class = "even";
                }
                print "<tr class='$class'>";
                    print "<td style='width:25%'>";
                        print $template['name'];
                    print "</td>";
                    print "<td>";
                        print $template['body'];
                    print "</td>";
                    print "<td style='width:15%'>";
                        print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_addRiskTemplate.php&tripPlannerRiskTemplateID=" . $template["tripPlannerRiskTemplateID"] . "'><img title='" . __m('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> ";

                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_deleteRiskTemplateProcess.php?tripPlannerRiskTemplateID=" . $template["tripPlannerRiskTemplateID"] . "'><img title='" . __m('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> ";
                    print "</td>";
                print "</tr>";
                $rowCount++;
            }
        } else {
            print "<tr>";
                print "<td colspan=3>";
                    print _("There are no records to display.");
                print "</td>";
            print "</tr>";
        }
    print "</table>";
}   
?>
