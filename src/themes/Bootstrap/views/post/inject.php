<?php
if (isset($_GET['q']) && $_GET['q'] === '/modules/Security/logout.php') $this->fileAnObject(array(__FILE__,__LINE__,$_GET), 'logout'.basename(__FILE__).__LINE__);
$this->render('default.header');
if (! (isset($_POST['absoluteAction']) && $_POST['absoluteAction']))
	$el->action = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $el->action));
if (isset($_GET['q']) && $_GET['q'] === '/modules/Security/logout.php') $this->fileAnObject(array(__FILE__,__LINE__,$_GET), 'logout'.basename(__FILE__).__LINE__);
if (isset($_GET['q']) && $_GET['q'] === '/modules/Security/logout.php') $this->fileAnObject(array(__FILE__,__LINE__,$_GET, $el->action), 'logout'.basename(__FILE__).__LINE__);
	
include $el->action ;
