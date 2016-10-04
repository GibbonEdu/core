<?php
use Gibbon\Person\guardian ;

$this->render('default.flash');

//Welcome message
if ($this->session->isEmpty("username")) {
	//Create auto timeout message
	if (isset($_GET["timeout"]) && $_GET["timeout"]=="true") {
		$this->displayMessage('Your session expired, so you were automatically logged out of the system.', 'warning');
	}
	 
	$this->h2('Welcome');       
	$this->paragraph($this->session->get("indexText"));
	
	//Publc registration permitted?
	$enablePublicRegistration = $this->config->getSettingByScope("User Admin", "enablePublicRegistration") ;
	if ($enablePublicRegistration=="Y") {
		$this->h2("Register") ;
		$this->paragraph("%1$sRegister now%2$s to join our online learning community. It's free", array("<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/publicRegistration.php'>", "</a>"));
	}

	//Public applications permitted?
	$publicApplications=$this->config->getSettingByScope("Application Form", "publicApplications" ) ; 
	if ($publicApplications=="Y") {
		$this->h2( "Applications") ;
		$this->paragraph('Parents of students interested in study at %1$s may use our %2$s online form%3$s to initiate the application process.', array($this->session->get("organisationName"), "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Students/applicationForm.php'>", "</a>")) ;
	}
	
	//Public departments permitted?
	$makeDepartmentsPublic=$this->config->getSettingByScope("Departments", "makeDepartmentsPublic" ) ; 
	if ($makeDepartmentsPublic=="Y") {
		$this->h2("Departments") ;
		$this->paragraph('Please feel free to %1$sbrowse our departmental information%2$s, to learn more about %3$s.', array("<a href='" . $this->session->get("absoluteURL") . "/?q=/modules/Departments/departments.php'>", "</a>", $this->session->get("organisationName"))) ;
	}
	
	//Public units permitted?
	$makeUnitsPublic=$this->config->getSettingByScope("Planner", "makeUnitsPublic" ) ; 
	if ($makeUnitsPublic=="Y") {
		$this->h2("Learn With Us") ;
		$this->paragraph('We are sharing some of our units of study with members of the public, so you can learn with us. Feel free to %1$sbrowse our public units%2$s.', array("<a href='" . $this->session->get("absoluteURL") . "/?q=/modules/Planner/units_public.php&sidebar=false'>", "</a>", $this->session->get("organisationName"))) ;
	}
	
	//Get any elements hooked into public home page, checking if they are turned on
	$dataHook=array(); 
	$sqlHook="SELECT * FROM `gibbonHook` WHERE type='Public Home Page' ORDER BY `name`" ;
	$resultHook=$this->pdo->executeQuery($dataHook, $sqlHook);

	while (! empty($resultHook) && $rowHook=$resultHook->fetch()) {
		$options=unserialize(str_replace("'", "\'", $rowHook["options"])) ;
		$check=$this->config->getSettingByScope($options["toggleSettingScope"], $options["toggleSettingName"]) ;
		if ($check==$options["toggleSettingValue"]) { //If its turned on, display it
			$this->h2($options["title"]);
			$this->paragraph(stripslashes($options["text"]));
		}
	}
}
else {
	//Custom content loader
	if ($this->session->isEmpty("index_custom")) {
		if (is_file(GIBBON_ROOT."/index_custom.php")) {
			$this->session->set("index_custom", file_get_contents(GIBBON_ROOT."/index_custom.php")) ;
		}
		else {
			$this->session->clear("index_custom") ;
		}
	}
	if ($this->session->notEmpty("index_custom")) {
		echo $this->session->get("index_custom") ;
	}
	
	//DASHBOARDS!
	//Get role category
	$category = $this->getSecurity()->getRoleCategory($this->session->get("gibbonRoleIDCurrent")) ;
	if ($category === false) {
		$this->displayMessage("Your current role type cannot be determined.") ;
	}
	else if ($category=="Parent") { //Display Parent Dashboard
		$count=0 ;
		$data=array("gibbonPersonID"=>$this->session->get("gibbonPersonID")); 
		$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
		$result=$this->pdo->executeQuery($data, $sql, "<div class='error'>|n{message}\n</div>\n");
		
		if ($result->rowCount()>0) {
			//Get child list
			$count=0 ;
			$options="" ;
			$students=array() ;
			while ($row=$result->fetch()) {
				$dataChild=array("gibbonSchoolYearID"=>$this->session->get("gibbonSchoolYearID"),"gibbonFamilyID"=>$row["gibbonFamilyID"]); 
				$sqlChild="SELECT gibbonPerson.gibbonPersonID, image_240, surname, preferredName, dateStart, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, gibbonRollGroup.website AS rollGroupWebsite, gibbonRollGroup.gibbonRollGroupID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName " ;
				$resultChild=$this->pdo->executeQuery($dataChild, $sqlChild, "<div class='error'>|n{message}\n</div>\n");

				while ($rowChild=$resultChild->fetch()) {
					$students[$count][0]=$rowChild["surname"] ;
					$students[$count][1]=$rowChild["preferredName"] ;
					$students[$count][2]=$rowChild["yearGroup"] ;
					$students[$count][3]=$rowChild["rollGroup"] ;
					$students[$count][4]=$rowChild["gibbonPersonID"] ;
					$students[$count][5]=$rowChild["image_240"] ;
					$students[$count][6]=$rowChild["dateStart"] ;
					$students[$count][7]=$rowChild["gibbonRollGroupID"] ;
					$students[$count][8]=$rowChild["rollGroupWebsite"] ;
					$count++ ;
				}
			}
		}
		$guardian = new guardian($this, $this->session->get("gibbonPersonID"));
		$guardian->students = $students;
		if ($count>0) {
			$this->render('home.dashboard.guardian', $guardian);
		}
	}
	else if ($category=="Student") { //Display Student Dashboard
		$this->render('home.dashboard.student');
	}
	else if ($category=="Staff") { //Display Staff Dashboard
		$this->render('home.dashboard.staff');
	
	}	
}
