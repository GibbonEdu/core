<?php
use Gibbon\core\trans ;

if (file_exists(GIBBON_ROOT . 'config/local/config.yml') || file_exists(GIBBON_ROOT . 'config.php')) { //Make sure system is not already installed
	$this->displayMessage(array("%s already exists, which suggests this system is already installed. The installer cannot proceed.", array(GIBBON_ROOT . 'config/local/config.yml'))) ;
}
else { //No config, so continue installer
	if (! is_writable(GIBBON_ROOT)) { //Ensure that home directory is writable
		$this->displayMessage(array("The directory '%s' containing the Gibbon files is not currently writable, so the installer cannot proceed. ", array(GIBBON_ROOT))) ;
	}
	else {
		$this->displayMessage("The directory containing the Gibbon files is writable, so the installation may proceed.", 'success') ;

		$this->session->set('install', true);
		
		$form = $this->getForm(null, array('q'=>'/installer/install.php', 'step'=>1, 'guid'=> $el->guid), true);
		
		$el = $form->addElement('h3', null, "Language Settings");
	
		$el = $form->addElement('select', 'code', null);
		$el->nameDisplay = "System Language";
		$el->setRequired();
		foreach ($this->config->getLanguages() as $value=>$display)
			$el->addOption($display, $value);

		$el = $form->addElement('submitBtn', null, 'Install');
		
		$form->render();	
	}
}?>
<!-- install.step0 -->