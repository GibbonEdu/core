<textarea name="<?php print $params->name ?>"
<?php echo isset($params->id) ? " id='". $params->id."'" : " id='". $params->name."'" ; ?>
<?php echo isset($params->rows) ? " rows='".$params->rows . "'" : '' ; ?>
<?php echo isset($params->cols) ? " cols='".$params->cols . "'" : '' ; ?>
<?php echo isset($params->element->class) ? ' class="'.$params->element->class . '"' : '' ; ?>
<?php echo $el->insertValidation($el); ?>><?php echo isset($params->value) ? $this->htmlPrep($params->value) : '' ; ?></textarea>
<!-- form.textArea -->