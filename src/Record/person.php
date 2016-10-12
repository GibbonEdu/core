<?php
/**
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
/**
 */
namespace Gibbon\Record ;

use Gibbon\core\security ;
use Gibbon\core\module as helper ;
use Gibbon\core\trans ;
use Gibbon\Record\personField ;
use Gibbon\core\fileManager ;

/**
 * Person Record
 *
 * @version	15th September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class person extends record
{
	/**
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonPerson';

	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonPersonID';

	/**
	 * @var	object	$role	Gibbon\Record\Role
	 */
	protected $role ;

	/**
	 * @var	stdClass	Titles for Fields
	 */
	protected $title ;

	/**
	 * Unique Test
	 *
	 * @version	31st July 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		if (empty($this->record))
			return $this->uniqueFailed('The Record has not been set.', 'Debug', $this->table) ;
		$this->setInsertDefaults();
		$required = array('surname', 'firstName', 'preferredName', 'officialName', 'gender', 'username', 'status', 'gibbonRoleIDPrimary');
		foreach ($required as $name) {
			if (empty($this->record->$name))
			{
				return $this->uniqueFailed('A necessary field was empty.', 'Debug', $this->table, array($name)) ;
			}
		}
		if (empty($this->record->address1) && empty($this->record->address1District))
			$this->record->address1Country = '';
		if (empty($this->record->address2) && empty($this->record->address2District))
			$this->record->address2Country = '';
		$data = array('username' => $this->record->username, 'gibbonPersonID' => $this->record->gibbonPersonID);
		$sql = 'SELECT * FROM `gibbonPerson` WHERE `username` = :username AND NOT `gibbonPersonID` = :gibbonPersonID';
		if (! empty($this->record->studentID)) {
			$data['studentID'] = $this->record->studentID;
			$sql = 'SELECT * FROM `gibbonPerson` WHERE (`username` = :username OR `studentID` = :studentID) AND NOT `gibbonPersonID` = :gibbonPersonID';
		}
		$tester = clone $this;
		$s = $tester->findAll($sql, $data);
		if (count($s) > 0)
			return $this->uniqueFailed('A record with the username already exists.', 'Debug', $this->table, $data) ;
		return true ;
	}

	/**
	 * Find all Staff
	 *
	 * @version	13th July 2016
	 * @since	25th May 2016
	 * @param	string		$status
	 * @return	array		of Gibbon\Record\person
	 */
	public function findAllStaff($status = 'Full')
	{
		$data = array('status' => $status);
		return $this->findAll("SELECT *
			FROM `gibbonPerson`
				JOIN `gibbonStaff` on (`gibbonPerson`.`gibbonPersonID`=`gibbonStaff`.`gibbonPersonID`)
			WHERE `status` = :status
			ORDER BY `surname`, `preferredName`", $data);
	}

	/**
	 * can Delete
	 *
	 * @version	25th May 2016
	 * @since	25th May 2016
	 * @return	boolean
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * get Role (Primary)
	 *
	 * @version	19th June 2016
	 * @since	2nd June 2016
	 * @return	object
	 */
	public function getRole()
	{
		if ($this->role instanceof role && isset($this->record->gibbonRoleIDPrimary) && intval($this->record->gibbonRoleIDPrimary) === intval($this->role->getField('gibbonRoleID')))
			return $this->role;
		if (isset($this->record->gibbonRoleIDPrimary) && intval($this->record->gibbonRoleIDPrimary) > 0)
			$this->role = new role($this->view, $this->record->gibbonRoleIDPrimary);
		else
			$this->role = new role($this->view);
		return $this->role;
	}

	/**
	 * get All Student Enrolment
	 *
	 * @version	6th June 2016
	 * @since	6th June 2016
	 * @param	integer		$schoolYearID
	 * @return	array
	 */
	public function getAllStudentEnrolment($schoolYearID)
	{
		$sql = "(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname,
				preferredName, title, image_240, gibbonYearGroup.nameShort AS yearGroup,
				gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type, gibbonRoleIDPrimary
			FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup
			WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
				AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
				AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
				AND gibbonStudentEnrolment.gibbonSchoolYearID=".intval($schoolYearID)." AND gibbonPerson.status='Full'
				AND gibbonPerson.gibbonPersonID = ".intval($this->record->gibbonPersonID).")
			UNION (SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS rollGroup, 'Staff' AS type, gibbonRoleIDPrimary
				FROM gibbonPerson
					JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
				WHERE type='Teaching'
					AND gibbonPerson.status='Full'
					AND gibbonPerson.gibbonPersonID=".intval($this->record->gibbonPersonID).")
			ORDER BY surname, preferredName";
		return $this->getStudentEnrolment($schoolYearID, $sql);
	}

	/**
	 * get Student Enrolment
	 *
	 * @version	6th June 2016
	 * @since	6th June 2016
	 * @param	integer		$schoolYearID
	 * @param	string		$sql	Query
	 * @return	object
	 */
	public function getStudent_Enrolment($schoolYearID, $sql = NULL)
	{
		if (! empty($this->studentEnrolment))
			return $this->studentEnrolment;
		if (is_null($sql))
			$sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title,
					image_240, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup,
					'Student' AS type, gibbonRoleIDPrimary
				FROM gibbonPerson
					LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=".intval($schoolYearID).")
					LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
					LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
				WHERE gibbonPerson.status='Full'
					AND gibbonPerson.gibbonPersonID=".intval($this->record->gibbonPersonID)." ORDER BY surname, preferredName";
		$se = new studentEnrolment($this->view);
		$this->studentEnrolment = $se->findAll($sql, array(), '_', 'gibbonPersonID');
		return $this->studentEnrolment ;
	}
	/**
	 * clear Student Enrolment
	 *
	 * @version	6th June 2016
	 * @since	6th June 2016
	 * @return	object
	 */
	public function clearStudentEnrolment()
	{
		$this->studentEnrolment =  NULL;
	}

	/**
	 * format Name
	 *
	 * @version	19th June 2016
	 * @since	22nd April 2016
	 * @param	boolean		$reverse	Reverse
	 * @param	boolean		$informal	Informal
	 * @return	string
	 */
	public function formatName($reverse = false, $informal = false)	{

		$roleCategory = $this->getRole()->getField('category');

		$output = false ;

		if ($roleCategory == "Staff" || $roleCategory == "Other" || empty($roleCategory)) {
			if (! $informal) {
				if ($reverse) {
					$output = $this->record->title . " " . $this->record->surname . ", " . strtoupper(substr($this->record->preferredName,0,1)) . "." ;
				}
				else {
					$output = $this->record->title . " " . strtoupper(substr($this->record->preferredName,0,1)) . ". " . $this->record->surname ;
				}
			}
			else {
				if ($reverse) {
					$output = $this->record->surname . ", " . $this->record->preferredName ;
				}
				else {
					$output = $this->record->preferredName . " " . $this->record->surname ;
				}
			}
		}
		else if ($roleCategory == "Parent") {
			if (! $informal) {
				if ($reverse) {
					$output = $this->record->title . " " . $this->record->surname . ", " . $this->record->preferredName ;
				}
				else {
					$output = $this->record->title . " " . $this->record->preferredName . " " . $this->record->surname ;
				}
			}
			else {
				if ($reverse) {
					$output = $this->record->surname . ", " . $this->record->preferredName ;
				}
				else {
					$output = $this->record->preferredName . " " . $this->record->surname ;
				}
			}
		}
		else if ($roleCategory == "Student") {
			if ($reverse) {
				$output = $this->record->surname. ", " . $this->record->preferredName ;
			}
			else {
				$output = $this->record->preferredName. " " . $this->record->surname ;
			}

		}

		return trim($output) ;
	}

	/**
	 * inject Post
	 *
	 * @version	7th September 2016
	 * @since	24th May 2016
	 * @return	boolean
	 */
	public function injectPost($data = null)
	{
		//  PRE Injection
		$staff = false;
		$student = false;
		$parent = false;
		$other = false;
		if (is_array($this->record->gibbonRoleIDAll))
			$roles = $this->record->gibbonRoleIDAll;
		else
			$roles = explode(',', $this->record->gibbonRoleIDAll);
		if (empty($roles)) $roles = array();

		foreach ($roles as $role) {
			$roleCategory = $this->view->getSecurity()->getRoleCategory($role);
			if ($roleCategory == 'Staff') {
				$staff = true;
			}
			if ($roleCategory == 'Student') {
				$student = true;
			}
			if ($roleCategory == 'Parent') {
				$parent = true;
			}
			if ($roleCategory == 'Other') {
				$other = true;
			}
		}
		if (isset($_POST['gibbonRoleIDAll']) && is_array($_POST['gibbonRoleIDAll']))
		{
			$_POST['gibbonRoleIDAll'][] = $_POST['gibbonRoleIDPrimary'];
			$_POST['gibbonRoleIDAll'] = array_unique($_POST['gibbonRoleIDAll']);
			sort($_POST['gibbonRoleIDAll']);
			foreach ($_POST['gibbonRoleIDAll'] as $q=>$w)
				if (empty($w))
					unset($_POST['gibbonRoleIDAll'][$q]);
			$_POST['gibbonRoleIDAll'] = implode(',', $_POST['gibbonRoleIDAll']);
		} else {
			$_POST['gibbonRoleIDAll'] = $_POST['gibbonRoleIDPrimary'];
		}

		if (isset($_POST['privacyOptions']) && is_array($_POST['privacyOptions'])) {
			$_POST['privacy'] = implode(',', $_POST['privacyOptions']);
		} else {
			$_POST['privacy'] = empty($this->record->privacy) ? null : $this->record->privacy ;
		}

		if (isset($_POST['studentAgreements']) && is_array($_POST['studentAgreements'])) {
			$_POST['studentAgreements'] = rtrim(implode(',', $_POST['studentAgreements']), ',');
		} else {
			$_POST['studentAgreements'] = empty($this->record->studentAgreements) ? null : $this->record->studentAgreements ;
		}

		//DEAL WITH CUSTOM FIELDS
		//Prepare field values
		$customRequireFail = false;
		$resultFields = $this->getCustomFields($student, $staff, $parent, $other);
		$fields = array();
		if ($resultFields && count($resultFields) > 0)
		{
			while ($rowFields = $resultFields->fetch()) {
				if (isset($_POST['custom'.$rowFields['gibbonPersonFieldID']])) {
					if ($rowFields['type'] == 'date') {
						$fields[$rowFields['gibbonPersonFieldID']] = helper::dateConvert($_POST['custom'.$rowFields['gibbonPersonFieldID']]);
					} else {
						$fields[$rowFields['gibbonPersonFieldID']] = $_POST['custom'.$rowFields['gibbonPersonFieldID']];
					}
				}
				if ($rowFields['required'] == 'Y') {
					if (empty($_POST['custom'.$rowFields['gibbonPersonFieldID']])) {
						$customRequireFail = true;
					}
				}
			}
		}
		$_POST['fields'] = ! $customRequireFail ? serialize($fields) : null ;

		$username = isset($_POST['username']) ? filter_var($_POST['username']) : null ;
		$username = is_null($username) ? (isset($this->record->username) ? $this->record->username : null) : $username ;

		if (! empty($_FILES) && ! is_null($username)) {
			$fm1 = new fileManager($this->view);
			if (! $fm1->fileManage('file1', $username.'_240')) $imageFail = true ;
			if (! $fm1->validImage(360, 480, 1.2, 1.4)) $imageFail = true ;
			$_POST['image_240'] = empty($fm1->fileName) ? $_POST['image_240'] : $fm1->fileName ;
			$fm1 = new fileManager($this->view);
			if (! $fm1->fileManage('nationalIDCardScanNew', $username.'_idscan')) $imageFail = true ;
			if (! $fm1->validImage(1440, 900)) $imageFail = true ;
			$_POST['nationalIDCardScan'] = empty($fm1->fileName) ? $_POST['nationalIDCardScan'] : $fm1->fileName ;
			$fm1 = new fileManager($this->view);
			if (! $fm1->fileManage('citizenship1PassportScanNew', $username.'_passportscan')) $imageFail = true ;
			if (! $fm1->validImage(1400, 900)) $imageFail = true ;
			$_POST['citizenship1PassportScan'] = empty($fm1->fileName) ? $_POST['citizenship1PassportScan'] : $fm1->fileName ;
		} elseif (! empty($_FILES) && is_null($username))
			$this->view->insertMessage("Images where not saved as the username was not set.", 'warning');

		// and inject
		$ok = parent::injectPost();

		// POST Injection
		for ($x = 1; $x < 5; $x++)
		{
			$p = 'phone'.$x;
			$t = 'phone'.$x.'Type';
			$c = 'phone'.$x.'CountryCode';
            $phone1Type = $_POST['phone1Type'];
            if (! empty($this->record->$p) && empty($this->record->$t)) {
                $this->record->$t = 'Other';
            }
			if (! empty($this->record->$p))
				$this->record->$p = preg_replace('/[^0-9+]/', '', $this->record->$p);
		}

		return $ok ;
	}

	/**
	 * get Family
	 *
	 * @version	20th July 2016
	 * @since	20th July 2016
	 * @param	integer		$personID
	 * @return	family
	 */
	public function getFamily($personID = null)
	{
		$this->family = array();
		if (is_null($personID))
			$personID = $this->record->gibbonPersonID;
		if (intval($personID) < 1) return array();

		$fObj = new family($this->view);
		$xx = $fObj->findAll('SELECT `gibbonFamilyAdult`.`gibbonFamilyID`
			FROM `gibbonFamily`
				JOIN `gibbonFamilyAdult` ON `gibbonFamilyAdult`.`gibbonFamilyID` = `gibbonFamily`.`gibbonFamilyID`
			WHERE `gibbonFamilyAdult`.`gibbonPersonID` = :personID', array('personID' => $personID));
		if (count($xx) !== 1)
			$xx = $fObj->findAll('SELECT `gibbonFamilyChild`.`gibbonFamilyID`
				FROM `gibbonFamily`
					JOIN `gibbonFamilyChild` ON `gibbonFamilyChild`.`gibbonFamilyID` = `gibbonFamily`.`gibbonFamilyID`
				WHERE `gibbonFamilyChild`.`gibbonPersonID` = :personID', array('personID' => $personID));
		if (count($xx) !== 1)
			return array();
		$family = reset($xx);
		$familyID = $family->getField('gibbonFamilyID');
		$xx = $fObj->findAll('SELECT `gibbonFamilyAdult`.`gibbonFamilyID`, `gibbonFamilyAdult`.`gibbonPersonID`, "adult" AS `role`, `gibbonFamily`.`name`, `dob`
			FROM `gibbonFamily`
				JOIN `gibbonFamilyAdult` ON `gibbonFamilyAdult`.`gibbonFamilyID` = `gibbonFamily`.`gibbonFamilyID`
				JOIN `gibbonPerson` ON `gibbonFamilyAdult`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID`
			WHERE `gibbonFamilyAdult`.`gibbonFamilyID` = :familyID
			UNION (SELECT `gibbonFamilyChild`.`gibbonFamilyID`, `gibbonFamilyChild`.`gibbonPersonID`, "child" AS `role`, `gibbonFamily`.`name`, `dob`
				FROM `gibbonFamily`
					JOIN `gibbonFamilyChild` ON `gibbonFamilyChild`.`gibbonFamilyID` = `gibbonFamily`.`gibbonFamilyID`
					JOIN `gibbonPerson` ON `gibbonFamilyChild`.`gibbonPersonID` = `gibbonPerson`.`gibbonPersonID`
				WHERE `gibbonFamilyChild`.`gibbonFamilyID` = :familyID1)
			ORDER BY `gibbonFamilyID`, `role`, `dob` DESC, `gibbonPersonID`', array('familyID' => $familyID, 'familyID1' => $familyID));
		if (is_array($xx))
			foreach($xx as $person)
				$this->family[] = $person->returnRecord();
		return $this->family;
	}

	/**
	 * delete Record
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @param	integer		$personID
	 * @return	boolean
	 */
	public function deleteRecord($personID)
	{
		$this->find($personID);  // Reset the object to this user.
		$this->deleteUserPhotos();
		$ok = parent::deleteRecord($personID);
		return $ok;
	}

	/**
	 * delete User Photos
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @return	boolean
	 */
	public function deleteUserPhotos()
	{
		$photos = array('image_240', 'citizenshipPassportScan', 'nationalIDCardScan');
		foreach($photos as $photo)
		{
			if (! empty($this->record->$photo) && ! unlink(GIBBON_ROOT . ltrim($this->record->$photo, '/'))) return false ;
		}
		return true ;
	}

	/**
	 * get Details of Person
	 *
	 * @version	21st July 2016
	 * @since	21st July 2016
	 * @param	string		$recordClass
	 * @param	string		$name
	 * @return	mixed
	 */
	public function getDetailsOfPerson($recordClass, $name = null)
	{
		$IDName = 'gibbon'.$recordClass.'ID';
		if (isset($this->record->$IDName))
		{
			$className = '\\Gibbon\\Record\\'.lcfirst($recordClass);
			$obj = new $className($this->view, $this->record->$IDName);
			if (is_null($name))
				return $obj->returnRecord();
			else
				return $obj->getField($name);
		}
		return null;
	}

	/**
	 * set Insert Defaults
	 *
	 * @version	1st August 2016
	 * @since	1st August 2016
	 * @param	array		$defaults
	 * @return	void
	 */
	public function setInsertDefaults($defaults = array())
	{
		if (isset($this->record->gibbonPersonID) && intval($this->record->gibbonPersonID) > 0)
			return ;
		if (empty($this->record->gender))
			$this->record->gender = 'Unspecified';
		if (empty($this->record->status))
			$this->record->status = 'Pending Approval';
		if (empty($this->record->passwordStrong))
		{
			$this->record->passwordStrong = $this->view->getSecurity()->getPasswordHash($this->view->getSecurity()->randomPassword(12));
			$this->record->passwordStrongSalt = $this->view->getSecurity()->getSalt(false);
		}
		if (is_array($defaults))
		{
			if (! empty($defaults['role'])) {
				$this->record->gibbonRoleIDPrimary = $defaults['role'];
				$this->record->gibbonRoleIDAll = $defaults['role'];
			}
			if (! empty($defaults['password'])) {
				$this->record->passwordStrong = $this->view->getSecurity()->getPasswordHash($defaults['password']);
				$this->record->passwordStrongSalt = $this->view->getSecurity()->getSalt(false);
				$this->record->password = '';
			}
		}
	}

	/**
	 * get Title
	 *
	 * @version	11th August 2016
	 * @since	4th August 2016
	 * @param	string		$fieldName
	 * @return	string
	 */
	public function getTitle($fieldName)
	{
		if (! $this->title instanceof \stdClass)
		{
			$this->title = new \stdClass();
			$this->title->title = 'Title';
			$this->title->surname = 'Surname';
			$this->title->firstName = 'First Name';
			$this->title->preferredName = 'Preferred Name';
			$this->title->otherNames = 'Other Names';
			$this->title->officialName = 'Official Name';
			$this->title->nameInCharacters = 'Name In Characters';
			$this->title->dob = 'Date of Birth';
			$this->title->email = 'Email';
			$this->title->emailAlternate = 'Alternate Email';
			$this->title->address1 = 'Address 1';
			$this->title->address1District = 'Address 1 District';
			$this->title->address1Country = 'Address 1 Country';
			$this->title->address2 = 'Address 2';
			$this->title->address2District = 'Address 2 District';
			$this->title->address2Country = 'Address 2 Country';
			for ($i = 1; $i < 5; ++$i)
			{
				$type = 'phone'.$i.'Type';
				$this->title->$type = sprintf('Phone %1$s Type', $i);
				$cc = 'phone'.$i.'CountryCode';
				$this->title->$cc = sprintf('Phone %1$s Country Code', $i);
				$ph = 'phone'.$i;
				$this->title->$ph = sprintf('Phone %1$s', $i);
			}
			$this->title->languageFirst = 'First Language';
			$this->title->languageSecond = 'Second Language';
			$this->title->languageThird = 'Third Language';
			$this->title->countryOfBirth = 'Country of Birth';
			$this->title->ethnicity = 'Ethnicity';
			$this->title->citizenship1 = 'Citizenship 1';
			$this->title->citizenship1Passport = 'Citizenship 1 Passport';
			$this->title->citizenship2 = 'Citizenship 2';
			$this->title->citizenship2Passport = 'Citizenship 2 Passport';
			$this->title->religion = 'Religion';
			$this->title->nationalIDCardNumber = 'National ID Card Number';
			$this->title->residencyStatus = 'Residency Status';
			$this->title->visaExpiryDate = 'Visa Expiry Date';
			$this->title->profession = 'Profession';
			$this->title->employer = 'Employer';
			$this->title->jobTitle = 'Job Title';
			$this->title->emergency1Name = 'Emergency 1 Name';
			$this->title->emergency1Number1 = 'Emergency 1 Number 1';
			$this->title->emergency1Number2 = 'Emergency 1 Number 2';
			$this->title->emergency1Relationship = 'Emergency 1 Relationship';
			$this->title->emergency2Name = 'Emergency 2 Name';
			$this->title->emergency2Number1 = 'Emergency 2 Number 1';
			$this->title->emergency2Number2 = 'Emergency 2 Number 2';
			$this->title->emergency2Relationship = 'Emergency 2 Relationship';
			$this->title->vehicleRegistration = 'Vehicle Registration';
			$this->title->privacy = 'Privacy';
		}
		if (! empty($this->title->$fieldName))
			return $this->title->$fieldName;
		return $fieldName ;
	}

	/**
	 * get Custom Feilds
	 *
	 * @version	14th August 2016
	 * @since	14th August 2016
	 * @param	boolean		$student
	 * @param	boolean		$staff
	 * @param	boolean		$parent
	 * @param	boolean		$other
	 * @param	boolean		$applicationForm
	 * @param	boolean		$dataUpdater
	 * @return	array
	 */
	public function getCustomFields($student = false, $staff = false, $parent = false, $other = false, $applicationForm = false, $dataUpdater = false)
	{
		$pfObj = new personField($this->view);
		$resultFields = $pfObj->getCustomFields($student, $staff, $parent, $other, $applicationForm, $dataUpdater);
		return $resultFields;
	}

	/**
	 * get Total People
	 *
	 * @version	15th September 2016
	 * @since	15th September 2016
	 * @param	string		$status  Assumes Full Status.
	 * @return	integer
	 */
	public function getTotalPeople($status = 'Full')
	{
		$data =array('status' => $status);
		$sql = "SELECT COUNT(`gibbonPersonID`)
			FROM `gibbonPerson`
			WHERE `status` LIKE :status" ;
		$v = clone $this ;
		$result = $v->executeQuery($data, $sql);
		$total = $result->fetchColumn() ;
		if (! $v->getSuccess())
			$total = 0;
		return $total;
	}
}
