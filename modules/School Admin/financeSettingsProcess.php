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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/financeSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/financeSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$email=$_POST["email"] ;
	$financeOnlinePaymentEnabled=$_POST["financeOnlinePaymentEnabled"] ;
	$financeOnlinePaymentThreshold=$_POST["financeOnlinePaymentThreshold"] ;
	$invoiceText=$_POST["invoiceText"] ;
	$invoiceNotes=$_POST["invoiceNotes"] ;
	$invoiceNumber=$_POST["invoiceNumber"] ;
	$receiptText=$_POST["receiptText"] ;
	$receiptNotes=$_POST["receiptNotes"] ;
	$hideItemisation=$_POST["hideItemisation"] ;
	$reminder1Text=$_POST["reminder1Text"] ;
	$reminder2Text=$_POST["reminder2Text"] ;
	$reminder3Text=$_POST["reminder3Text"] ;
	$budgetCategories=$_POST["budgetCategories"] ;
	$expenseApprovalType=$_POST["expenseApprovalType"] ;
	$budgetLevelExpenseApproval=$_POST["budgetLevelExpenseApproval"] ;
	$expenseRequestTemplate=$_POST["expenseRequestTemplate"] ;
	$allowExpenseAdd=$_POST["allowExpenseAdd"] ;
	$purchasingOfficer=$_POST["purchasingOfficer"] ;
	$reimbursementOfficer=$_POST["reimbursementOfficer"] ;
	
	if ($email=="" OR $financeOnlinePaymentEnabled=="" OR $invoiceNumber=="" OR $hideItemisation=="" OR $budgetCategories=="" OR $expenseApprovalType=="" OR $budgetLevelExpenseApproval=="" OR $allowExpenseAdd=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Write to database
		$fail=FALSE ;
	
		try {
			$data=array("value"=>$email); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='email'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$financeOnlinePaymentEnabled); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='financeOnlinePaymentEnabled'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$financeOnlinePaymentThreshold); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='financeOnlinePaymentThreshold'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$invoiceText); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='invoiceText'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
	
		try {
			$data=array("value"=>$invoiceNotes); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='invoiceNotes'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$invoiceNumber); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='invoiceNumber'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
	
		try {
			$data=array("value"=>$receiptText); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='receiptText'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
	
		try {
			$data=array("value"=>$receiptNotes); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='receiptNotes'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$hideItemisation); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='hideItemisation'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$reminder1Text); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='reminder1Text'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
	
		try {
			$data=array("value"=>$reminder2Text); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='reminder2Text'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
	
		try {
			$data=array("value"=>$reminder3Text); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='reminder3Text'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$budgetCategories); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='budgetCategories'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$expenseApprovalType); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='expenseApprovalType'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$budgetLevelExpenseApproval); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='budgetLevelExpenseApproval'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$expenseRequestTemplate); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='expenseRequestTemplate'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$allowExpenseAdd); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='allowExpenseAdd'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$purchasingOfficer); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='purchasingOfficer'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$reimbursementOfficer); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Finance' AND name='reimbursementOfficer'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		if ($fail==TRUE) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			getSystemSettings($guid, $connection2) ;
			$URL.="&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>