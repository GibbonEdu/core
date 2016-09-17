<?php
$input = '<input type="text" name="'.$el->name.'" id="'. $el->setID() . '"';
$input .= $id = isset($el->maxLength) ? ' maxlength="'.$el->maxLength . '"' : '';
$input .= isset($el->readOnly) && $el->readOnly ? ' readonly' : '' ;
$input .= ' value="'. $this->htmlPrep($el->value). '"';
$input .= $id = isset($el->element->class) ? ' class="'.$el->element->class.'"' : '' ;
$input .= $id = isset($el->element->style) ? ' style="' . $el->element->style.'"' : '' ;
$input .= $id = (isset($el->required) AND $el->required) ? ' required' : "" ; 
$input .= $id = (isset($el->placeholder) AND $el->placeholder) ? ' placeholder="'.$el->placeholder.'"' : "" ;
$input .= $el->insertValidation($el);
$input .= ' />';
echo $input;?><!-- form.text -->