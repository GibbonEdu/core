<select name="<?php print $params->name; ?>" id="<?php echo isset($params->id) ? $params->id : '_'.$params->name ; ?>"<?php echo isset($params->element->class) ? ' class="' . $params->element->class . '"' : '' ; ?><?php echo isset($params->element->style) ? ' style="' . $params->element->style.'"' : '' ; ?><?php echo isset($params->disabled) && $params->disabled ? ' disabled' : '' ; ?><?php echo isset($params->readOnly) && $params->readOnly ? ' disabled' : '' ; ?><?php echo $el->insertValidation($el); ?><?php echo isset($el->multiple) && $el->multiple ? ' multiple' : '' ; ?><?php echo $el->additional; ?>>
<?php foreach ($el->options as $value=>$display) { 
	$class = $display->class ;
	$display = $display->display ; ?>
    <?php if ($display === 'optgroup') { ?>
        <optgroup label="<?php echo $value; ?>" />
    <?php } else { ?>
        <option value="<?php echo $value; ?>"<?php echo $value == $params->value || (is_array($params->value) && in_array($value, $params->value)) ? ' selected' : '' ; ?><?php echo ! empty($class) ? ' class="' . $class . '"' : '' ; ?>><?php echo $display ; ?></option>
    <?php } ?>
<?php } ?>
</select>
<!-- form.select 17/7/16 -->
