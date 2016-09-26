<?php
$this->render('form.standard.rowStart', $el);
foreach ($el->buttons as $element)
{
	$name = $element->element->name;
	$element->setThemeStandards($el->theme);
	$this->render('form.' . $name, $element);
}
$this->render('form.standard.rowEnd', $el);
