<?php 
$form = $this->getForm(null, array('q'=>'installer/install.php', 'step' => 2, 'guid' => $params->guid), true);;

$this->session->set('install', true);

$el = $form->addElement('h3', '', "Database Information");

$type = $form->addElement('text', 'type', 'MySQL');
$type->readOnly = true;
$type->nameDisplay = 'Database Type';
$type->setRequired();
$type->col1->style = 'width: 275px;';
$type->description = 'This value cannot be changed.';
        
        
$type = $form->addElement('text', 'databaseServer', $this->config->get('dbHost'));
$type->placeHolder = 'gibbon';
$type->nameDisplay = 'Database Server';
$type->setLength(null, null, 255);
$type->description = 'Localhost, IP address or domain.';
$type->setRequired();
    
        
$type = $form->addElement('text', 'databaseName', $this->config->get('dbName'));
$type->placeHolder = 'gibbon';
$type->nameDisplay = Gibbon\core\trans::__('Database Name');
$type->maxLength = 50 ;
$type->setRequired();
$type->description = Gibbon\core\trans::__('This database will be created if it does not already exist. Collation should be utf8_unicode_ci.');
        
        
$type = $form->addElement('text', 'databaseUsername', $this->config->get('dbUser'));
$type->nameDisplay = Gibbon\core\trans::__('Database Username');
$type->maxLength = 50 ;
$type->setRequired();
        
        
$type = $form->addElement('password', 'databasePassword', $this->config->get('dbPWord'));
$type->nameDisplay = Gibbon\core\trans::__('Database Password');
$type->maxLength = 64 ;
$type->setRequired();
        
        
$type = $form->addElement('yesno', 'demoData', 'N');
$type->nameDisplay = Gibbon\core\trans::__('Install Demo Data?');
$type->validate = false ;
$type->setRequired();
        
$type = $form->addElement('hidden', 'code', $params->code);

$el = $form->addElement('submitBtn', 'submitBtn', 'Submit');
      
$form->renderForm();
?>
<!-- install.step1 -->