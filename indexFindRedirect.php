<?php
@session_start() ;
include "./config.php" ;

$type=substr($_GET["id"],0,3) ;
$id=substr($_GET["id"],4) ;

if ($_SESSION[$guid]["absoluteURL"]=="") {
	$URL="./index.php" ;
}
else {
	if ($type=="Stu") {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $id ;
	}
	else if ($type=="Act") {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $id ;
	}
	else if ($type=="Sta") {
		$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=" . $id ;
	}
}

header("Location: {$URL}") ;
?>