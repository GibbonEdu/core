<?php
if ( $this->session->get('sidebar') !== "false" ) {
	$this->render('home.contentStart');
}

$addressMD5 = '';
if ($this->session->notEmpty("address") && file_exists($this->session->get("address")) )
	$addressMD5 = md5_file($this->session->get("address"));
elseif ($this->session->notEmpty("address") && file_exists($this->session->get("absolutePath").$this->session->get("address")))
	$addressMD5 = md5_file($this->session->get("absolutePath").$this->session->get("address"));
//Show index page Content
if ($this->session->isEmpty("address") || $addressMD5 === md5_file($this->session->get("absolutePath").'/index.php')) {
	$this->render('home.home');
}
else {
	if (strstr($this->session->get("address"),"..") || strstr($this->session->get("address"),"installer") || strstr($this->session->get("address"),"uploads")) {
		$this->displayMessage("Illegal address detected: access denied.") ;
	}
	else {
		$target = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $this->session->get("address")));
		if(is_file($target)) {
			//Include the page
			$guid = $this->config->get('guid');   //Deprecated
			$version = $this->config->get('version');   //Deprecated
			$connection2 = $this->pdo->getConnection();   //Deprecated
			include $target ;
		}
		else {
			$this->render('default.error');
			die(__FILE__.': '.__LINE__);
		}
	}
}
