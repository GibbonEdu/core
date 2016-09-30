<?php
$this->render('default.header');
if (! (isset($_POST['absoluteAction']) && $_POST['absoluteAction']))
	$el->action = str_replace('//', '/', GIBBON_ROOT . 'src/' . str_replace(array('//', '\\', 'src/', GIBBON_ROOT), array('/', '/', '', ''), $el->action));
	
include $el->action ;
