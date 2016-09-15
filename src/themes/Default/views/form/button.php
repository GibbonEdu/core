<?php
$input = '<input name="'.$params->name.'" id="'. ($id = isset($params->id) ? $params->id : $params->name) . '"';
$input .= isset($el->element->type) ? ' type="'.$el->element->type.'"' : ' type="button"' ;
$input .= $id = isset($params->maxLength) ? ' maxlength='.$params->maxLength : '';
$input .= ' value="'. Gibbon\core\helper::htmlPrep($params->value). '"';
$input .= $id = isset($params->element->class) ? ' class="'.$params->element->class.'"' : '' ;
$input .= $id = isset($params->element->style) ? ' ' . $params->element->style : '' ;
$input .= ' />';
echo $input;?><!-- form.button -->