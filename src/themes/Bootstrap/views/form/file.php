<?php
$input = '<input type="file" name="'.$params->name.'" id="'. ($id = isset($params->id) ? $params->id : $params->name) . '"';
$input .= $id = isset($params->maxLength) ? ' maxlength="'.$params->maxLength . '"' : '';
$input .= isset($params->readOnly) && $params->readOnly ? ' readonly' : '' ;
$input .= ' value="'. $this->htmlPrep($params->value). '"';
$input .= $id = isset($params->element->class) ? ' class="'.$params->element->class.'"' : '' ;
$input .= $id = isset($params->element->style) ? ' style="' . $params->element->style.'"' : '' ;
$input .= $id = (isset($params->required) AND $params->required) ? ' required' : "" ; 
$input .= $id = (isset($params->placeholder) AND $params->placeholder) ? ' placeholder="'.$params->placeholder.'"' : "" ;
$input .= $el->insertValidation($el);
$input .= ' />';
echo $input;?><!-- form.file -->