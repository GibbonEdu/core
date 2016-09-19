<?php
	if (isset($params->divert))
		new Gibbon\Form\hidden('divert', true, $this);
	new Gibbon\Form\action($params->action, $this);
	new Gibbon\Form\address($this->session->get("address"), $this);
	$el = new Gibbon\Form\submitBtn(Gibbon\core\trans::__(isset($params->submit) ? $params->submit : 'Submit' ));
	$this->render('form.submit', $el);
?><!-- form.protection -->