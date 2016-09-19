<?php 
foreach ($el->get('elements') as $kill=>$element)
{
	$name = $element->element->name;
	$element = $el->grabFormDetails($element);
	$this->render('form.' . $element->style . '.' . $name, $element);
	$el->removeElement($kill);
}
