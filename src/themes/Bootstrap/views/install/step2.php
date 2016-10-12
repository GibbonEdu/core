<?php
use Gibbon\core\trans ;
use Gibbon\core\helper ;
use Symfony\Component\Yaml\Yaml ;

$this->session->set('install', true);

//Check for db values
if ($params->dbHost=="" OR $params->dbName=="" OR $params->dbUser=="" OR $params->dbPWord=="" OR $params->demoData=="") {
    $this->displayMessage(trans::__('A database connection could not be established. Please %1$stry again%2$s.', array("<a href='index.php?q=/installer/install.php'>", "</a>"))) ;
}

//Estabish db connection without database name
$this->pdo = new Gibbon\core\sqlConnection(false);
$this->pdo->installBypass($params->dbHost, $params->dbName, $params->dbUser, $params->dbPWord);
if (! $this->pdo->getSuccess()) {
	$this->displayMessage(trans::__('A database connection could not be established. Please %1$stry again%2$s.', array("<a href='index.php?q=/installer/install.php'>", "</a>"))) ;
}
else {
	if (file_exists(GIBBON_ROOT . 'config/local/config.yml'))
		$config = Yaml::parse( file_get_contents( GIBBON_ROOT . 'config/local/config.yml' ));
	else
		$config = array();
		$config['database']['dbHost'] = $params->dbHost ;
		$config['database']['dbUser'] = $params->dbUser ;
		$config['database']['dbPWord'] = $params->dbPWord ;
		$config['database']['dbName'] = $params->dbName ;
		$config['guid'] = $params->guid ;
		$config['caching'] = 10;
		file_put_contents(GIBBON_ROOT . 'config/local/config.yml', Yaml::dump($config));
 
		$config = '';
		$config .= "<?php\n";
		$config .= "/**\n";
		$config .= "Gibbon, Flexible & Open School System\n";
		$config .= "Copyright (C) 2010, Ross Parker\n";
		$config .= "\n";
		$config .= "This program is free software: you can redistribute it and/or modify\n";
		$config .= "it under the terms of the GNU General Public License as published by\n";
		$config .= "the Free Software Foundation, either version 3 of the License, or\n";
		$config .= "(at your option) any later version.\n";
		$config .= "\n";
		$config .= "This program is distributed in the hope that it will be useful,\n";
		$config .= "but WITHOUT ANY WARRANTY; without even the implied warranty of\n";
		$config .= "MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n";
		$config .= "GNU General Public License for more details.\n";
		$config .= "\n";
		$config .= "You should have received a copy of the GNU General Public License\n";
		$config .= "along with this program.  If not, see <http://www.gnu.org/licenses/>.\n";
		$config .= "*/\n";
		$config .= "\n";
		$config .= "//Sets database connection information\n";
		$config .= '$databaseServer="'.$params->dbHost."\" ;\n";
		$config .= '$databaseUsername="'.$params->dbUser."\" ;\n";
		$config .= "\$databasePassword='".$params->dbPWord."' ;\n";
		$config .= '$databaseName="'.$params->dbName."\" ;\n";
		$config .= "\n";
		$config .= "//Sets globally unique id, to allow multiple installs on the server server.\n";
		$config .= '$guid="'.$params->guid."\" ;\n";
		$config .= "\n";
		$config .= "//Sets system-wide caching factor, used to baalance performance and freshness. Value represents number of page loads between cache refresh. Must be posititve integer. 1 means no caching.\n";
		$config .= "\$caching = 10 ;\n";
		$config .= '$newSystem = true;'."\n";

		file_put_contents(GIBBON_ROOT . 'config.php', $config);
	
		if (! file_exists(GIBBON_ROOT . 'config/local/config.yml')) { //Something went wrong, config.yml could not be created.
			$this->displayMesage("Configuration could not be created, and so the installer cannot proceed.");
		}
		else { //Config, exists, let's press on
//			$result = $this->pdo->executeQuery(array(), 'SHOW TABLES');
//			while ($col = $result->fetchColumn()) 
//				$this->pdo->executeQuery(array(), 'DROP TABLE `'.$col.'`'); 
			//Let's populate the database
			if (! file_exists(GIBBON_ROOT . "src/installer/gibbon.sql")) {
				$this->displayMessage(GIBBON_ROOT . 'src/installer/gibbon.sql does not exist, and so the installer cannot proceed.') ;
			}
			else {
				ini_set('memory_limit','512M');	
				include GIBBON_ROOT . 'src/installer/installerFunctions.php' ;

				$query=@fread(@fopen(GIBBON_ROOT . "src/installer/gibbon.sql", 'r'), @filesize(GIBBON_ROOT . "src/installer/gibbon.sql")) or die('Encountered a problem.');
				$query=remove_remarks($query);
				$query=split_sql_file($query, ';');
		
				$i=1;
				$partialFail = false ;
				foreach($query as $sql){
					$i++;
					$sql = str_replace(array('MyISAM', 'utf8_general_ci'), array('Innodb', 'utf8_unicode_ci'), $sql);
					$this->pdo->executequery(array(), $sql) ;
					if (! $this->pdo->getQuerySuccess()) {
						$partialFail = true  ;
					}
				}
		
				if ($partialFail ) {
					$this->displayMessage("Errors occurred in populating the database; empty your database, remove config.yml and try again.") ;
				}
				else {
					//Try to install the demo data, report error but don't stop if any issues
					if ($params->demoData == "Y") {
						if (! file_exists(GIBBON_ROOT . "src/installer/gibbon_demo.sql")) {
							$this->displayMessage(GIBBON_ROOT . "src/installer/gibbon_demo.sql does not exist, so we will continue without demo data.") ;
						}
						else {
							$query=@fread(@fopen(GIBBON_ROOT . "src/installer/gibbon_demo.sql", 'r'), @filesize(GIBBON_ROOT . "src/installer/gibbon_demo.sql")) or die('Encountered a problem.');
							$query=remove_remarks($query);
							$query=split_sql_file($query, ';');
		
							$i=1;
							$demoFail = false ;
							foreach($query as $sql){
								$i++;
								$sql = str_replace(array('MyISAM', 'utf8_general_ci'), array('Innodb', 'utf8_unicode_ci'), $sql);
								$this->pdo->executequery(array(), $sql) ;
								if (! $this->pdo->getQuerySuccess()) {
									print $sql . "<br/>" ;
									print $this->pdo->getError() . "<br/><br/>" ;
									$demoFail = true  ;
								}
							}

							if ($demoFail) {
								$this->displayMessage( "There were some issues installing the demo data, but we will continue anyway.", 'warning') ;
							}
						}
					}
				
					$this->getConfig();
					
					//Set default language
					$this->config->setSettingByScope('defaultLanguage', $params->code, 'System');
		
				   //Let's gather some more information
					
					$form = $this->getForm(null, array('q'=>'/installer/install.php', 'step' => 3, 'guid' => $params->guid), true);
					
					$x = $form->addElement('h3', '', "User Account");
	
					$x = $form->addElement('select', 'title', null);
					$x->addOption('');
					$titles = $this->config->getTitles();
					foreach ($titles as $q=>$w)
						$x->addOption(helper::htmlPrep($w), $q);
					$x->nameDisplay = trans::__('Title'); 
					$x->col1->style = "width: 275px; ";
	
					  
					$x = $form->addElement('text', 'surname');
					$x->nameDisplay = trans::__('Surname'); 
					$x->setRequired();
					$x->maxlength = 30 ;
					$x->description = trans::__('Family name as shown in ID documents.');
	
	
					$x = $form->addElement('text', 'firstName');
					$x->nameDisplay = trans::__('First Name'); 
					$x->setRequired();
					$x->maxlength = 30 ;
					$x->description = trans::__('First name as shown in ID documents.');
	
	
					$x = $form->addElement('text', 'email');
					$x->nameDisplay = trans::__('Email'); 
					$x->setRequired();
					$x->validate->Email = true;
					$x->maxlength = 50 ;
	
	
					$x = $form->addElement('checkbox', 'support', 'true');
					$x->nameDisplay = trans::__('Receive Support?'); 
					$x->description = trans::__('Join our mailing list and receive a welcome email from the team.') ;


					$x = $form->addElement('text', 'username');
					$x->nameDisplay = trans::__( 'Username'); 
					$x->setRequired();
					$idList = "'admin','user','username'," ;
					$sqlSelect = "SELECT username FROM gibbonPerson ORDER BY username" ;
					$resultSelect = $this->pdo->executeQuery(array(), $sqlSelect);
					while ($username = $resultSelect->fetchColumn()) 
						$idList .= "'" . $username  . "'," ;
					$x->setExclusion(rtrim($idList, ','), 'Value already in use!', ", partialMatch: false, caseSensitive: false") ;
					$x->setMaxLength(20);
					$x->description = trans::__('Must be unique. System login name. Cannot be changed.');

	
					$policy = $this->getSecurity($this)->getPasswordPolicy() ;
					if ($policy) {
						$x = $form->addElement('notice', '', $this->returnMessage($policy, 'info'));
					}

					$x = $form->addElement('password', 'passwordNew');
					$x->nameDisplay = trans::__('Password'); 
					$x->setRequired();
					$x->setMaxLength(20);
					$format = "^(.*";
					if ( $this->config->getSettingByScope( "System", "passwordPolicyAlpha" ) == 'Y')
						$format .= "(?=.*[a-z])(?=.*[A-Z])";
					if ($this->config->getSettingByScope( "System", "passwordPolicyNumeric" ) == 'Y')
						$format .= "(?=.*[0-9])";
					if ($this->config->getSettingByScope( "System", "passwordPolicyNonAlphaNumeric" ) == 'Y')
						$format .= "(?=.*?[#?!@$%^&*-])";
					$format .= ".*)$";
					$x->setFormat($format, 'Does not meet password policy.');
					$el2 = new \Gibbon\Form\button('generate', trans::__("Generate Password"));
					$el2->element->style = "style='align: left'";
					$el2->element->class = "generatePassword small";
					$x->description = $this->renderReturn('form.button', $el2);
					
					$x = $form->addElement('script', '', '
						<script type="text/javascript">
                                    $(".generatePassword").click(function(){
                                        var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^<>~@|";
                                        var text = "";
                                        for(var i = 0; i < ' . intval($this->config->getSettingByScope('System', 'passwordPolicyMinLength') + 4) . '; i++) {
                                            if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
                                            else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
                                            else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
                                            else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
                                            else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
                                        }
                                        $(\'input[name="passwordNew"]\').val(text);
 										$(\'input[name="passwordNew"]\').get(0).type = "text";
										$(\'input[name="passwordNew"]\').blur(function() {
											$(\'input[name="passwordNew"]\').get(0).type = "password";
										});
                                       	$(\'input[name="passwordConfirm"]\').val(text);
                                        alert("'.trans::__("Copy this password if required:").'" + "\n\n" + text) ;
                                    });
                                </script>');

	
					$x = $form->addElement('password', 'passwordConfirm');
					$x->nameDisplay = trans::__('Confirm Password'); 
					$x->setRequired();
					$x->setConfirmation(null, 'passwordNew');
					$x->setMaxLength(20);

					$form->endWell();
					$form->startWell();

					$x = $form->addElement('h3', '', "System Settings");
			
					$x = $form->addElement('text', 'absoluteURL');
					$x->injectRecord($this->config->getSetting('absoluteURL', 'System'));
					$x->setMaxLength(50);
					$x->value = empty($x->value) ? GIBBON_URL : $x->value;
					$x->setRequired();
					$x->setFormat("/(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/", "Must start with http:// or https://");


					$el = $form->addElement('text', 'absolutePath');
					$el->injectRecord($this->config->getSetting('absolutePath', 'System'));
					$el->value = empty($el->value) ? GIBBON_ROOT : $EL->value;
					$el->setRequired();
					$x->setMaxLength(50);


					$el = $form->addElement('text', 'systemName');
					$el->injectRecord($this->config->getSetting('systemName', 'System'));
					$el->value = empty($el->value) ? 'Gibbon' : $el->value;
					$el->setRequired();
					$x->setMaxLength(50);

			 
					$el = $form->addElement('select', 'installType');
					$el->injectRecord($this->config->getSetting('installType', 'System'));
					$el->value = empty($el->value)? 'Testing' : $el->value;
					$el->setRequired() ;
					$el->addOption(trans::__('Production'), 'Production');
					$el->addOption(trans::__('Testing'), 'Testing');
					$el->addOption(trans::__('Development'), 'Development');


					$el = $form->addElement('notice', null, "
                                <div id='status' class='warning'>
                                    <div style='width: 100%; text-align: center'>
                                        <img style='margin: 10px 0 5px 0' src='src/themes/Bootstrap/img/loading.gif' alt='Loading'/><br/>
                                        ".trans::__("Checking for Cutting Edge Code.")."
                                    </div>
                                </div>
");


					$yn = $form->addElement('yesno', 'cuttingEdgeCode');
					$yn->injectRecord($this->config->getSetting('cuttingEdgeCode', 'System'));
					$yn->validateOff();
					$yn->id = $yn->name;
					$yn->name = $yn->id.'Disabled';
					$yn->setRequired();
					$yn->disabled = true ;


					$row = $form->addElement('hidden', $yn->id, 'N');
					$row->id = $yn->id.'Hidden';

                        //Check and set cutting edge code based on gibbonedu.org services value
 
					$yn = $form->addElement('script' , null, "
                        <script type=\"text/javascript\">
                            $(document).ready(function(){
                                $.ajax({
                                    crossDomain: true, type:\"GET\", contentType: \"application/json; charset=utf-8\", async: false,
                                    url: \"https://gibbonedu.org/services/version/devCheck.php?version=" . $this->config->get('version') . "&callback=?\",
                                    data: \"\", dataType: \"jsonp\", jsonpCallback: 'fnsuccesscallback', jsonpResult: 'jsonpResult',
                                    success: function(data) {
                                        $(\"#status\").attr(\"class\",\"success\");
                                        if (data['status']==='false') {
                                            $(\"#status\").html('" . trans::__('Cutting Edge Code check successful.') . "') ;
                                        }
                                        else {
											$(\"#status\").attr(\"class\",\"info\");
                                            $(\"#status\").html('" . trans::__('Cutting Edge Code check successful.') . "') ;
                                            $(\"#cuttingEdgeCode\").val('Y');
                                            $(\"#cuttingEdgeCodeHidden\").val('Y');
                                        }
                                    },
                                    error: function (data, textStatus, errorThrown) {
                                        $(\"#status\").attr(\"class\",\"error\");
                                            $(\"#status\").html('" . trans::__('Cutting Edge Code check failed') . ".') ;

                                    }
                                });
                            });
                        </script>");

					$yn = $form->addElement('yesno', 'statsCollection');
					$yn->injectRecord($this->config->getSetting('statsCollection', 'System'));
					$yn->setRequired() ;


					$form->endWell();
					$form->startWell();


					$x = $form->addElement('h3', '', "Organisation Settings");


					$el = $form->addElement('text', '');
					$el->injectRecord($this->config->getSetting('organisationName', 'System'));
					$el->setRequired() ;
					$x->setMaxLength(50);


					$el = $form->addElement('text', '');
					$el->injectRecord($this->config->getSetting('organisationNameShort', 'System'));
					$el->setRequired() ;
					$x->setMaxLength(50);

					
					$el = $form->addElement('select', '');
					$el->injectRecord($this->config->getSetting('currency', 'System'));
					$el->setRequired() ;
					$currencies = $this->config->getCurrencyList();
					foreach($currencies as $value=>$display)
					{
						if (is_array($display))
						{
							$el->addOption('optgroup', '--'.trans::__($value).'--');
							foreach($display as $q=>$w)
								$el->addOption($w, $q);
						}
					}


					$form->endWell();
					$form->startWell();
					$x = $form->addElement('h3', '', "gibbonedu.com Value-Added Services");

					$el = $form->addElement('text', '');
					$el->injectRecord($this->config->getSetting('gibboneduComOrganisationName', 'System'));
					$x->setMaxLength(80);
					$el->validateOff() ;


					$el = $form->addElement('text', '');
					$el->injectRecord($this->config->getSetting('gibboneduComOrganisationKey', 'System'));
					$x->setMaxLength(36);
					$el->validateOff() ;

					$form->endWell();
					$form->startWell();

					$x = $form->addElement('h3', '', "Miscellaneous");

					$el = $form->addElement('select', '');
					$el->injectRecord($this->config->getSetting('country', 'System'));
					$el->addOption( trans::__('Select a Country!'), '');
					$countries = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(GIBBON_ROOT . 'config/local/country.yml'));
					foreach($countries['countries'] as $name=>$value)
						$el->addOption(trans::__( $name ), $name );
					$el->setExclusion('Select a Country!', 'Select something!');

					$el = $form->addElement('text', '');
					$el->injectRecord($this->config->getSetting('timezone', 'System'));
					$el->setRequired() ;
					$el->value = date_default_timezone_get();


					$el = $form->addElement('select', '');
					$el->injectRecord($this->config->getSetting('primaryAssessmentScale', 'System'));
					$el->value = '';
					$el->setExclusion('Please select...', 'Select something!');
					$el->addOption( trans::__( 'Please select...' ), 'Please select...' );
					$scale = new \Gibbon\Record\scale($this);
					$row = $scale->findBy(array('active' => 'Y'), array('name' => 'ASC'));
					do 
						$el->addOption( trans::__( helper::htmlPrep(trans::__( $row->name ) ) ), $row->gibbonScaleID );
					while ($row = $scale->next()) ;
					
					$form->addElement('hidden', 'code', $params->code);

					$form->addElement('hidden', 'databaseServer', $params->dbHost);
					
					$form->addElement('hidden', 'databaseName', $params->dbName);
					
					$form->addElement('hidden', 'databaseUsername', $params->dbUser);
					
					$form->addElement('hidden', 'databasePassword', $params->dbPWord);
					
					$el = $form->addElement('submitBtn', 'submitBtn', 'Submit');

					$form->render();
            }
        }
    }
}
