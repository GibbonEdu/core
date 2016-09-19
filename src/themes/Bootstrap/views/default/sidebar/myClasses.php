<?php

//Show My Classes
if ($this->session->isEmpty("address") && $this->session->notEmpty("username")) {
	$data = array("gibbonSchoolYearID"=>$this->session->get("gibbonSchoolYearID"), "gibbonPersonID"=> $this->session->get("gibbonPersonID"));
	$sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID 
		FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson 
		WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
			AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID 
			AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID 
			AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID 
			AND NOT role LIKE '% - Left%' ORDER BY course, class" ;
	$result = $this->pdo->executeQuery($data, $sql);

	if ($result->rowCount()>0) {
		$this->render('default.sidebar.myClasses.start');
		?>
			<?php
			while ($row=$result->fetchObject()) {
				$this->render('default.sidebar.myClasses.member', $row);
			}
		?></table><?php
	}
}
