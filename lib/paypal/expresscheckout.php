<?php

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

require_once ("paypalfunctions.php");
//==================================
// PayPal Express Checkout Module
//==================================

//'------------------------------------
//' The paymentAmount is the total value of
//' the shopping cart, that was set
//' earlier in a session variable
//' by the shopping cart page
//'------------------------------------
$paymentAmount=$_GET["Payment_Amount"];


//'------------------------------------
//' The currencyCodeType and paymentType
//' are set to the selections made on the Integration Assistant
//'------------------------------------

$currency=getSettingByScope($connection2, "System", "currency") ;
if ($currency!=FALSE AND $currency!="") {
	$currencyCodeType=substr($currency,0,3);
}
else {
	$currencyCodeType="USD";
}
$paymentType="Sale";

//'------------------------------------
//' The returnURL is the location where buyers return to when a
//' payment has been succesfully authorized.
//'
//' This is set to the value entered on the Integration Assistant
//'------------------------------------
$returnURL=$_SESSION[$guid]["absoluteURL"] . "/" . $_GET["return"];

//'------------------------------------
//' The cancelURL is the location buyers are sent to when they hit the
//' cancel button during authorization of payment during the PayPal flow
//'
//' This is set to the value entered on the Integration Assistant
//'------------------------------------
$cancelURL=$_SESSION[$guid]["absoluteURL"] . "/" . $_GET["fail"];

//'------------------------------------
//' Calls the SetExpressCheckout API call
//'
//' The CallShortcutExpressCheckout function is defined in the file PayPalFunctions.php,
//' it is included at the top of this file.
//'-------------------------------------------------
$resArray=CallShortcutExpressCheckout ($paymentAmount, $currencyCodeType, $paymentType, urlencode($returnURL), urlencode($cancelURL), $guid, _SESSION[$guid]['i18n']['code']);
$ack=strtoupper(@$resArray["ACK"]);


if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
{
	RedirectToPayPal ( $resArray["TOKEN"] );
}
else
{
	//Display a user friendly Error on the page using any of the following error information returned by PayPal
	$ErrorCode=urldecode($resArray["L_ERRORCODE0"]);
	$ErrorShortMsg=urldecode($resArray["L_SHORTMESSAGE0"]);
	$ErrorLongMsg=urldecode($resArray["L_LONGMESSAGE0"]);
	$ErrorSeverityCode=urldecode($resArray["L_SEVERITYCODE0"]);

	print $ErrorLongMsg ; exit() ;

	if ($ErrorLongMsg="Currency is not supported") {
		$URL=$_SESSION[$guid]["gatewayCurrencyNoSupportReturnURL"] ;
		header("Location: {$URL}");
	}
	else {
		echo "SetExpressCheckout API call failed. ";
		echo "Detailed Error Message: " . $ErrorLongMsg;
		echo "Short Error Message: " . $ErrorShortMsg;
		echo "Error Code: " . $ErrorCode;
		echo "Error Severity Code: " . $ErrorSeverityCode;
	}
}
?>
