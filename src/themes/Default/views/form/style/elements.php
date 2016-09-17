<?php 
foreach ($el->get('elements') as $element)
{
	$name = $element->element->name;
	$element->setThemeStandards($el->get('theme'));
	$element->set('validation', $el->get('validation'));
	$element->theme = $el->get('theme');
	$element->style = $el->get('style');
	$this->render('form.' . $el->get('style') . '.' . $name, $element);
}
