<?php $this->render('form.standard.rowStart', $el);
$el->setButtonsClass(); 
foreach($el->buttons as $q=>$button)
{
	$this->render('form.button', $el->buttons[$q]);
}
$this->render('form.standard.rowEnd', $el); 
