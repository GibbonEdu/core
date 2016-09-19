<input name="<?php print $params->name ?>" id="<?php echo isset($params->id) ? $params->id : $params->name ; ?>"
<?php echo isset($params->maxlength) ? ' maxlength='.$params->maxlength : '' ; ?>
<?php echo $params->readOnly ? ' readonly' : '' ; ?> 
 value="<?php print $this->htmlPrep($params->value) ?>" 
 type="password"
<?php echo isset($params->element->class) ? ' class="' . $params->element->class . '"' : '' ; ?>
<?php echo isset($params->placeholder) ? ' placeholder="' . $params->placeholder . '"' : '' ; ?>
<?php echo isset($params->element->style) ? ' style="' . $params->element->style . '"' : '' ; ?>
<?php echo $params->required ? ' required' : "" ; ?> 
<?php echo $el->insertValidation($el); ?>
/><!-- bootstrap.form.password -->
