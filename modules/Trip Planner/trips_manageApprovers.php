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

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manageApprovers.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_manageApprovers.php', $connection2);
    if ($highestAction != false) {
        print "<div class='trail'>";
            print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Approvers') . "</div>";
        print "</div>";

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        print "<h3>";
            print "Approvers";
        print "</h3>";

        $actionsAllowed = ($highestAction == "Manage Approvers_add&edit" || $highestAction == "Manage Approvers_full");

        $approvers = getApprovers($connection2);

        if ($actionsAllowed) {
            print "<div class='linkTop'>";
                print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_addApprover.php'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>";
            print "</div>";
        }

        print "<table cellspacing='0' style='width: 100%'>";
            print "<tr class='head'>";
                print "<th>";
                    print _("Name");
                print "</th>";
                $expenseApprovalType = getSettingByScope($connection2, "Trip Planner", "requestApprovalType");
                if ($expenseApprovalType == "Chain Of All") {
                    print "<th>";
                        print _("Sequence Number");
                    print "</th>";
                }
                $riskAssessmentApproval = getSettingByScope($connection2, "Trip Planner", "riskAssessmentApproval");
                if($riskAssessmentApproval) {
                    print "<th>";
                        print _("Is a Final Approver?");
                    print "</th>";
                }
                if ($actionsAllowed) {
                    print "<th>";
                        print _("Action");
                    print "</th>";
                }
            print "</tr>";
            if ($approvers->rowCount() > 0) {
                $rowCount = 0;
                while ($approver = $approvers->fetch()) {
                    $class = "odd";
                    if ($rowCount % 2 == 0) {
                        $class = "even";
                    }
                    print "<tr class='$class'>";
                        print "<td>";
                            $name = getNameFromID($connection2, $approver['gibbonPersonID']);
                            print $name['preferredName'] . " " . $name['surname'];
                        print "</td>";
                        if ($expenseApprovalType == "Chain Of All") {
                            print "<td>";
                                print $approver['sequenceNumber'];
                            print "</td>";
                        }
                        if($riskAssessmentApproval) {
                            print "<td>";
                                print ($approver['finalApprover'] ? "Yes" : "No");
                            print "</td>";
                        }
                        if ($actionsAllowed) {
                            print "<td>";
                                print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_editApprover.php&tripPlannerApproverID=" . $approver["tripPlannerApproverID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> ";

                                if ($highestAction == "Manage Approvers_full") {
                                    print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_deleteApproverProcess.php?tripPlannerApproverID=" . $approver["tripPlannerApproverID"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> ";
                                }
                            print "</td>";
                        }
                    print "</tr>";
                    $rowCount++;
                }
            } else {
                print "<tr>";
                    $colspan = 1;
                    if ($expenseApprovalType == "Chain Of All") {
                        $colspan++;
                    }

                    if ($actionsAllowed) {
                        $colspan++;
                    }

                    if($riskAssessmentApproval) {
                        $colspan++;
                    }

                    print "<td colspan=$colspan>";
                        print _("There are no records to display.");
                    print "</td>";
                print "</tr>";
            }
        print "</table>";
    }
}   
?>
