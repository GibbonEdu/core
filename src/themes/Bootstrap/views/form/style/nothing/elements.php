<?php 
foreach ($el->get('elements') as $kill=>$element)
{
	$name = $element->element->name;
	$element = $el->grabFormDetails($element);
	if (count((array)$element->span) > 0) { ?>
    	<span<?php echo isset($element->span->class) ? ' class="'.$element->span->class.'"' : '' ; ?><?php echo isset($element->span->style) ? ' style="'.$element->span->style.'"' : '' ; ?>><?php
	}
	if (isset($element->nameDisplay))
		echo $this->__($element->nameDisplay);
	$this->render('form.nothing.' . $name, $element);
	if (count((array)$element->span) > 0) { ?>
    	</span>
    <?php }
	$el->removeElement($kill);
} 
$el->signOff();
