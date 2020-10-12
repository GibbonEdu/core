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

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_reportToday.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Today\'s Trips') . "</div>";
    print "</div>";

    try {
        $data = array('date' => date('Y-m-d'));
        $sql = "SELECT
                tripPlannerRequests.tripPlannerRequestID, tripPlannerRequests.timestampCreation, tripPlannerRequests.title, tripPlannerRequests.description, tripPlannerRequests.status, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID
            FROM tripPlannerRequests
                JOIN tripPlannerRequestDays ON (tripPlannerRequestDays.tripPlannerRequestID=tripPlannerRequests.tripPlannerRequestID)
                LEFT JOIN gibbonPerson ON tripPlannerRequests.creatorPersonID = gibbonPerson.gibbonPersonID
            WHERE
                tripPlannerRequestDays.startDate <= :date
                AND tripPlannerRequestDays.endDate >= :date
                AND tripPlannerRequests.status IN ('Requested','Approved','Awaiting Final Approval')
            ";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    ?>

    <h3>
        Today's Trips
    </h3>

    <table cellspacing = '0' style = 'width: 100% !important'>
        <tr>
            <th>
                Title
            </th>
            <th>
                Description
            </th>
            <th>
                Owner
            </th>
            <th>
                Status
            </th>
            <th>
                Action
            </th>
        </tr>
    <?php
    if ($result->rowCount() == 0) {
        ?>
        <tr>
            <td colspan=5>
                There are no records to display
            </td>
        </tr>
    <?php
    } else {
        $rowCount = 0;
        $descriptionLength = 100;
        while ($row = $result->fetch()) {
            $show = true;
            if ($relationFilter == "AMA" && $ama) {
                if (!($row["status"] == "Requested" && needsApproval($connection2, $row["tripPlannerRequestID"], $_SESSION[$guid]["gibbonPersonID"])) == 0 && !($row["status"] == "Awaiting Final Approval" && isApprover($connection2, $_SESSION[$guid]["gibbonPersonID"], true))) {
                    $show = false;
                }
            }

            if ($eutFilter) {
                $startDate = getFirstDayOfTrip($connection2, $row["tripPlannerRequestID"]);
                if (strtotime($startDate) < mktime(0, 0, 0) && $row["status"] != "Approved") {
                    $show = false;
                }
            }
            if ($show) {
                $class = "odd";
                if ($rowCount % 2 == 0) {
                    $class = "even";
                }
                print "<tr class='$class'>";
                    print "<td style='width:20%'>" . $row['title'] . "</td>";
                    $descriptionText = strip_tags($row['description']);
                    if (strlen($descriptionText)>$descriptionLength) {
                        $descriptionText = substr($descriptionText, 0, $descriptionLength) . "...";
                    }
                    print "<td>" . $descriptionText . "</td>";
                    print "<td style='width:20%'>" . $row['preferredName'] . " " . $row["surname"] . "</td>";
                    print "<td style='width:12%'>";
                        print $row['status'] . "</br>";
                    print "</td>";
                    print "<td style='width:16.5%'>";
                        print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_requestView.php&tripPlannerRequestID=" . $row["tripPlannerRequestID"] . "'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> ";
                    print "</td>";
                print "</tr>";
                $rowCount++;
            }
        }

        if($rowCount == 0) {
              ?>
            <tr>
                <td colspan=5>
                    There are no records to display
                </td>
            </tr>
        <?php
        }
    }
    ?>
    </table>
    <?php
}
?>
