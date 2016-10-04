<?php
$colour = isset($el->colour) ? $el->colour : 'grey';
$el->element->class = $colour != 'default' ? $el->element->colour->$colour : $el->element->class;

$input = '<input name="'.$el->name.'" id="'. ($id = isset($el->id) ? $el->id : $el->name) . '"';
$input .= isset($el->element->type) ? ' type="'.$el->element->type.'"' : ' type="button"' ;
$input .= $id = isset($el->maxLength) ? ' maxlength='.$el->maxLength : '';
$input .= ' value="'. $this->htmlPrep($el->value). '"';
$input .= $id = isset($el->element->class) ? ' class="'.$el->element->class.'"' : '' ;
$input .= $id = isset($el->element->style) ? ' style="' . $el->element->style.'"' : '' ;
$input .= $id = isset($el->additional) ? $el->additional : '' ;
$input .= $id = isset($el->element->id) ? ' id="'.$el->element->id.'"' : '';
$input .= ' />';
echo $input;?><!-- form.button -->