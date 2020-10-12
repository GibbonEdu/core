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
include "./modules/Trip Planner/src/Domain/TripPlanner/TripGateway.php";

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\TripPlanner\TripGateway;
if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_manage.php', $connection2);
    if ($highestAction != false) {
        print "<div class='trail'>";
            print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Trip Requests') . "</div>";
        print "</div>";

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $expenseApprovalType = getSettingByScope($connection2, "Trip Planner", "requestApprovalType");
        $riskAssessmentApproval = getSettingByScope($connection2, "Trip Planner", "riskAssessmentApproval");
        $eutFilter = getSettingByScope($connection2, "Trip Planner", "expiredUnapprovedFilter");

        $ama = (isApprover($connection2, $_SESSION[$guid]["gibbonPersonID"]) && $expenseApprovalType == "Chain Of All") || ($riskAssessmentApproval && isApprover($connection2, $_SESSION[$guid]["gibbonPersonID"], true));
        $departments = getHOD($connection2, $_SESSION[$guid]["gibbonPersonID"]);
        $isHOD = $departments->rowCount() > 0;

        $data = array();
        $sql = "SELECT tripPlannerRequests.tripPlannerRequestID, tripPlannerRequests.timestampCreation, tripPlannerRequests.title, tripPlannerRequests.description, tripPlannerRequests.status, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID FROM tripPlannerRequests LEFT JOIN gibbonPerson ON tripPlannerRequests.creatorPersonID = gibbonPerson.gibbonPersonID";
        $connector = " WHERE ";

        $relations = array();
        $relationFilter = "MR";

        if ($highestAction == "Manage Trips_full") {
            $relations[""] = "All Requests";
            $relationFilter = "";
        }

        $relations["MR"] = "My Requests";
        $relations["I"] = "Involved";

        if ($isHOD) {
            while ($department = $departments->fetch()) {
                $relations["DR" . $department["gibbonDepartmentID"]] = "Department Requests - " . $department["nameShort"];
            }
        }

        if ($ama) {
            $relations["AMA"] = "Awaiting My Approval";
            $relationFilter = "AMA";
        }

        $statusFilter = "Requested";
        $yearFilter = $_SESSION[$guid]["gibbonSchoolYearID"];

        if (isset($_POST["statusFilter"])) {
            $statusFilter = $_POST["statusFilter"];
        }

        if (isset($_POST["yearFilter"])) {
            $yearFilter = $_POST["yearFilter"];
        }

        if (isset($_POST["relationFilter"])) {
            $relationFilter = $_POST["relationFilter"];
        }


        //This must be the FIRST filter check!
        if ($relationFilter == "I") {
            $data["teacherPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
            $sql .= " JOIN tripPlannerRequestPerson ON (tripPlannerRequestPerson.tripPlannerRequestID = tripPlannerRequests.tripPlannerRequestID) WHERE (tripPlannerRequestPerson.role='Teacher' AND :teacherPersonID = tripPlannerRequestPerson.gibbonPersonID OR teacherPersonIDs LIKE CONCAT('%', :teacherPersonID, '%'))";
            $connector = " AND ";
        } elseif ($relationFilter == "MR") {
            $data["creatorPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
            $sql .= $connector . "tripPlannerRequests.creatorPersonID=:creatorPersonID";
            if ($connector == " WHERE ") {
                $connector = " AND ";
            }
        } elseif ($relationFilter == "AMA") {
            $statusFilter = "All";
        } elseif (strpos($relationFilter, "DR") !== false) {
            $data["gibbonDepartmentID"] = substr($relationFilter, 2);
            $sql .= $connector . ":gibbonDepartmentID IN (SELECT gibbonDepartmentID FROM gibbonDepartmentStaff WHERE gibbonPersonID = tripPlannerRequests.creatorPersonID)";
            if ($connector == " WHERE ") {
                $connector = " AND ";
            }
        }

        if ($statusFilter != "All") {
            $data["status"] = $statusFilter;
            $sql .= $connector . "tripPlannerRequests.status=:status";
            if ($connector == " WHERE ") {
                $connector = " AND ";
            }
        }

        if ($yearFilter != "All Years") {
            $data["gibbonSchoolYearID"] = $yearFilter;
            $sql .= $connector . "tripPlannerRequests.gibbonSchoolYearID=:gibbonSchoolYearID";
            if ($connector == " WHERE ") {
                $connector = " AND ";
            }
        }

        try {
            $sqlYear = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear";
            $resultYear = $connection2->prepare($sqlYear);
            $resultYear->execute();
        } catch (PDOException $e) {
        }

        $years = array();

        while ($row = $resultYear->fetch()) {
            $years[$row['gibbonSchoolYearID']] = $row['name'];
        }

        $form = Form::create("tripFilters", $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"]);

        $form->setTitle(__("Filter"));

        $row = $form->addRow();
            $row->addLabel("statusFilterLabel", "Status Filter");
            $row->addSelect("statusFilter")->fromArray(array("All", "Requested", "Approved", "Rejected", "Cancelled", "Awaiting Final Approval"))->selected($statusFilter);

        $row = $form->addRow();
            $row->addLabel("relationFilterLabel", "Relation Filter");
            $row->addSelect("relationFilter")->fromArray($relations)->selected($relationFilter);

        $row = $form->addRow();
            $row->addLabel("yearFilterLabel", "Year Filter");
            $row->addSelect("yearFilter")->fromArray($years)->selected($yearFilter);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        print $form->getOutput();

        try {
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        
        $tripGateway = $container->get(TripGateway::class);
        $criteria = $tripGateway->newQueryCriteria()
              ->filterBy('status',$statusFilter)
              ->filterBy('relation',$relationFilter.':'.$gibbon->session->get('gibbonPersonID'))
              ->filterBy('year')
             ->filterBy('eutfilter',$eutFilter)
          ->fromPOST();

        $trips = $tripGateway->queryTrips($criteria);

        $table = DataTable::createPaginated('trips',$criteria);
        $table->setTitle(__("Requests"));
        $table
          ->addHeaderAction('add',__('Submit Request'))
          ->setURL('/modules/Trip Planner/trips_submitRequest.php');
        $table->addColumn('tripTitle',__('Title'));
        $table->addColumn('description',__('Description'));
        $table
          ->addColumn('owner',__('Owner'))
          ->format(function($row) {
            return Format::name($row['title'],$row['preferredName'],$row['surname']);
          });
        $table->addColumn('status',__('Status'));
        $table
          ->addActionColumn()
          ->addParam('tripPlannerRequestID')
          ->format(function($row,$actions) use($connection2,$gibbon) {
              $actions
                ->addAction('view',__('View Details'))
                ->setURL('/modules/Trip Planner/trips_requestView.php');

              if(
                  $gibbon->session->get('gibbonPersonID') == $row['creatorPersonID'] &&
                  $row['status'] != 'Cancelled' &&
                  $row['status'] != 'Rejected'
              )
              {
                $actions
                  ->addAction('edit',__('Edit'))
                  ->addParam('mode','edit')
                  ->setURL('/modules/Trip Planner/trips_submitRequest.php');
              }

              if(isApprover($connection2,$gibbon->session->get('gibbonPersonID')))
              {
                $actions
                  ->addAction('approve',__('Approve/Reject'))
                  ->setURL('/modules/Trip Planner/trips_requestApprove.php')
                  ->setIcon('iconTick');
              }
          });
        /*
        $table->modifyRows(function($row) use ($eutFilter,$connection2) {
            if ($eutFilter) {
                $startDate = getFirstDayOfTrip($connection2, $row["tripPlannerRequestID"]);
                if (strtotime($startDate) < mktime(0, 0, 0) && $row["status"] != "Approved") {
                    return $row;
                }
            }
            return $row;
        });
         */
        echo $table->render($trips);
    }
}
?>
