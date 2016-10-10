<?php
//Still required for Google Login
if (isset($_GET["loginReturn"])) {
	$loginReturn=$_GET["loginReturn"] ;
}
else {
	$loginReturn="" ;
}

$loginReturnMessage="" ;
if (!($loginReturn=="")) {
	if ($loginReturn=="fail0b") {
		$this->insertMessage("Username or password not set.", 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail1") {
		$this->insertMessage("Incorrect username and password.", 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail2") {
		$this->insertMessage("You do not have sufficient privileges to login.", 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail5") {
		$this->insertMessage("Your request failed due to a database error.", 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail6") {
		$this->insertMessage(array('Too many failed logins: please %1$sreset password%2$s.', array("<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/passwordReset.php'>", "</a>")), 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail7") {
		$this->insertMessage(array('Error with Google Authentication. Please contact %1$s if you have any questions.', array("<a href='mailto:" . $this->session->get("organisationDBAEmail") . "'>" . $this->session->get("organisationDBAName") . "</a>")), 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail8") {
		$this->insertMessage(array('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.', array($this->session->get("systemName"), "<a href='mailto:" . $this->session->get("organisationDBAEmail") . "'>" . $this->session->get("organisationDBAName") . "</a>")), 'error', false, 'login.flash') ;
	}
	else if ($loginReturn=="fail9") {
		$this->insertMessage('Your primary role does not support the ability to log into the specified year.', 'error', false, 'login.flash') ;
	}
}

$el = new \stdClass();
$el->target = 'login.flash';
$this->render('default.flash', $el);

