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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../gibbon.php';

include "./moduleFunctions.php";

$addInfo = explode(":", $_GET["gibbonCourseClassID"]);
$type = $_GET["type"];
$typeVal = "false";
if ($type == "Add") {
    $typeVal = "true";
}

try {
    $data = array("id" => $addInfo[1]);
    if ($addInfo[0] == "Class") {
        $sql = "SELECT gibbonPersonID FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:id AND role='Student'";
    }
    else if ($addInfo[0] == "Activity") { 
        $sql = "SELECT gibbonPersonID FROM gibbonActivityStudent WHERE gibbonActivityID=:id AND status='Accepted'";
    }
    else { //Group
        $sql = "SELECT gibbonGroupPerson.gibbonPersonID FROM gibbonGroupPerson JOIN gibbonPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonGroupID=:id AND gibbonPerson.status='Full'";
    }
    $result = $connection2->prepare($sql);
    $result->execute($data);
    $students = array();
    while ($row = $result->fetch()) {
        $students[] = $row['gibbonPersonID'];
    }
    $js_array = json_encode($students);

    ?>
    <script type='text/javascript'>
        var students = <?php print $js_array ?>;
        var source = $('#studentsSource');
        var destination = $('#students');
        if (!<?php print $typeVal ?>) {
            var temp = destination;
            destination = source;
            source = temp;
        }
        source.find("option").each(function(){
            if (students.indexOf($(this).val()) >= 0) {
                destination.append($(this).clone());
                $(this).detach().remove();
            }
        });
        sortSelects("students");
    </script>   
    <?php

} catch(PDOException $e) {
    exit();
}

?>