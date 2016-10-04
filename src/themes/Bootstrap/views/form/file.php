<?php
$input = '<input type="file" name="'.$el->name.'" id="'. ($id = isset($el->id) ? $el->id : $el->name) . '"';
$input .= $id = isset($el->maxLength) ? ' maxlength="'.$el->maxLength . '"' : '';
$input .= isset($el->readOnly) && $el->readOnly ? ' readonly' : '' ;
$input .= ' value="'. $this->htmlPrep($el->value). '"';
$input .= $id = isset($el->element->class) ? ' class="'.$el->element->class.'"' : '' ;
$input .= $id = isset($el->element->style) ? ' style="' . $el->element->style.'"' : '' ;
$input .= $id = (isset($el->required) AND $el->required) ? ' required' : "" ; 
$input .= $id = (isset($el->placeholder) AND $el->placeholder) ? ' placeholder="'.$el->placeholder.'"' : "" ;
$input .= $id = isset($el->additional) ? $el->additional : "" ;
$input .= $el->insertValidation($el);
$input .= ' />';
echo $input;?><!-- form.file -->