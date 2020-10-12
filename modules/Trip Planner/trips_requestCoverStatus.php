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

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    //Acess denied
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    if (isset($_GET["tripPlannerRequestID"])) {
        $tripPlannerRequestID = $_GET["tripPlannerRequestID"];
        if(isset($_GET["gibbonCourseClassID"]) && isset($_GET["date"])) {
            $gibbonCourseClassID = $_GET["gibbonCourseClassID"];
            $date = $_GET["date"];
            if (isOwner($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"])) {
                $requiresCover = false;
                if (isset($_GET["requiresCover"])) {
                    $requiresCover = ($_GET["requiresCover"] == 1);
                }

                print "<h3>";
                    print "Change Class Cover Status";
                print "</h3>";

                $form = Form::create("editCoverStatus", $_SESSION[$guid]["absoluteURL"] . "/modules/Trip Planner/trips_requestCoverStatusProcess.php?tripPlannerRequestID=$tripPlannerRequestID&gibbonCourseClassID=$gibbonCourseClassID&date=$date");

                $row = $form->addRow();
                    $row->addLabel("requiresCoverLabel", "Requires Cover?");
                    $row->addCheckBox("requiresCover")->checked($requiresCover);

                $row = $form->addRow();
                    $row->addSubmit();

                print $form->getOutput();
            } else {
                print "<div class='error'>";
                    print "You do not have access to this action.";
                print "</div>";
            }
        } else {    
            print "<div class='error'>";
                print "No class selected.";
            print "</div>";
        }
    } else {    
        print "<div class='error'>";
            print "No request selected.";
        print "</div>";
    }
}   
?>
