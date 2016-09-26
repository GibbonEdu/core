<?php
use Gibbon\core\trans ;
use Gibbon\Record\schoolYear ;

// Add Google Login Button
$googleOAuth = $this->config->getSettingByScope("System", "googleOAuth") ;

if ($this->session->isEmpty("username") && $this->session->isEmpty("email")) {
	if ($googleOAuth == "Y") {
		$this->h2($this->__("Login with Google")) ; ?>

		<div id="siteloader"><?php include GIBBON_ROOT . '/lib/google/index.php'; ?></div>
		<?php
	} //End Check for Google Auth
	
	if ($this->session->isEmpty("username")){ // If Google Auth set to No make sure login screen not visible when logged in
		$link = array();
		if (isset($_GET["q"])) $link['q'] = $_GET['q'];
		$this->h2($this->__("Login"));

		$el =  new stdClass();
		$el->target = 'loginFlash';
		$this->render('default.flash', $el);
		
		$form = $this->getForm(null, array('q'=>'/modules/Security/login.php'), true);
		$form->setName('login');
		$form->setStyle('login');
			
		$el = $form->addElement('text', 'username', '');
		$el->nameDisplay = "Username" ;
		$el->setLength(null, null, 20);
		$el->setRequired();
		$el->element->style = 'width: 120px;';
		
		$el = $form->addElement('password', 'password', null);
		$el->nameDisplay = "Password" ;
		$el->setLength(null, null, 30);
		$el->element->style = 'width: 120px; ';
		$el->setRequired();
		$el->validate->onlyOnSubmit = true;

		$el = $form->addElement('select', 'gibbonSchoolYearID', null);
		$el->nameDisplay = "School Year" ;
		$el->row->id = 'schoolYear';
		$el->element->style  = 'width: 120px; ';
		$el->validateOff() ;

		$syObj = new schoolYear($this);
		$years = $syObj->findAll('SELECT * 
			FROM `gibbonSchoolYear` 
			ORDER BY `sequenceNumber`');
		$el->value = '';
		foreach($years as $year)
		{
			$el->addOption($this->htmlPrep($year->getField('name')), $year->getField('gibbonSchoolYearID'));
			if ( $year->getField('status') === "Current") 
				$el->value = $year->getField('gibbonSchoolYearID');
		}

		$el = $form->addElement('select', 'gibboni18nCode', '');
		$el->nameDisplay = "Language" ;
		$el->row->id = 'language';
		$el->element->style  = 'width: 120px;';
		$el->validateOff();
		$el->addOption('Select Language', '');
		foreach ($this->config->getLanguages() as $code=>$name )
		{
			$el->addOption($this->htmlPrep($name), $code);
		}

		$this->addScript('
					<script type="text/javascript">
						$(document).ready(function(){
							$("#schoolYear").hide();
							$("#language").hide();
							$(".show_hide").fadeIn(1000);
							$(".show_hide").click(function(){
							$("#schoolYear").fadeToggle(1000);
							$("#language").fadeToggle(1000);
						});
					});
					</script>');

		$el = $form->addElement('note', null, null);
		$el->description = array('%1$s', array('<a class="show_hide" onclick="false" href="#">'.$this->__("Options").'</a> . <a href="'.GIBBON_URL.'/index.php?q=/modules/Security/passwordReset.php">'.$this->__("Forgot Password?").'</a><br/>'.$this->__('* Denotes a required field.')));

		$el = $form->addElement('submitBtn', null, 'Login');
		$el->description = '';

		$form->render('login');
	}
}
