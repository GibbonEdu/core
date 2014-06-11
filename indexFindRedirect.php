<?php
@session_start() ;
include "./config.php" ;

$role=substr($_GET["gibbonPersonID"],0,3) ;
$gibbonPersonID=substr($_GET["gibbonPersonID"],4) ;

if ($_SESSION[$guid]["absoluteURL"]=="") {
	$URL="./index.php" ;
}
else {
	if ($role=="Stu") {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $gibbonPersonID ;
	}
	else {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=" . $gibbonPersonID ;
	}
}

header("Location: {$URL}") ;
?>