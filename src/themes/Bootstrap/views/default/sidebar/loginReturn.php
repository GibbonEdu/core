<?php
	$el = new \stdClass();
	$el->target = 'login.flash';
	$this->render('default.flash', $el);

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
			$loginReturnMessage=$this->__("Username or password not set.") ;
		}
		else if ($loginReturn=="fail1") {
			$loginReturnMessage=$this->__("Incorrect username and password.") ;
		}
		else if ($loginReturn=="fail2") {
			$loginReturnMessage=$this->__("You do not have sufficient privileges to login.") ;
		}
		else if ($loginReturn=="fail5") {
			$loginReturnMessage=$this->__("Your request failed due to a database error.") ;
		}
		else if ($loginReturn=="fail6") {
			$loginReturnMessage=sprintf($this->__('Too many failed logins: please %1$sreset password%2$s.'), "<a href='" . $this->session->get("absoluteURL") . "/index.php?q=/passwordReset.php'>", "</a>") ;
		}
		else if ($loginReturn=="fail7") {
			$loginReturnMessage=sprintf($this->__('Error with Google Authentication. Please contact %1$s if you have any questions.'), "<a href='mailto:" . $this->session->get("organisationDBAEmail") . "'>" . $this->session->get("organisationDBAName") . "</a>") ;
		}
		else if ($loginReturn=="fail8") {
			$loginReturnMessage=sprintf($this->__('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.'), $this->session->get("systemName"), "<a href='mailto:" . $this->session->get("organisationDBAEmail") . "'>" . $this->session->get("organisationDBAName") . "</a>") ;
		}
		else if ($loginReturn=="fail9") {
			$loginReturnMessage=$this->__('Your primary role does not support the ability to log into the specified year.') ;
		}

		print "<div class='error'>" ;
			print $loginReturnMessage;
		print "</div>" ;
	}
