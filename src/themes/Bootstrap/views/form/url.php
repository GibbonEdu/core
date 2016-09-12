<?php
$input = '<input type="url" name="'.$params->name.'" id="'. ($id = isset($params->id) ? $params->id : $params->name) . '"';
$input .= $id = isset($params->maxLength) ? ' maxlength='.$params->maxLength : '';
$input .= $params->readOnly ? ' readonly' : '' ;
$input .= ' value="'. Gibbon\core\helper::htmlPrep($params->value). '"';
$input .= $id = isset($params->element->class) ? ' class="'.$params->element->class.'"' : '' ;
$input .= $id = isset($params->element->style) ? ' style="' . $params->element->style.'"' : '' ;
$input .= $id = (isset($params->required) AND $params->required) ? ' required' : "" ; 
$input .= $id = (isset($params->placeholder) AND $params->placeholder) ? ' placeholder="'.$params->placeholder.'"' : "" ;
$input .= $el->insertValidation($el);
$input .= ' />';
echo $input;?><!-- form.url -->