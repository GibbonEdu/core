<?php
$colour = isset($el->colour) ? $el->colour : 'grey';
$el->element->class = $colour != 'default' ? $el->element->colour->$colour : $el->element->class;
?>
<button type="<?php echo $el->element->type; ?>" name="<?php echo $el->name; ?>"<?php echo isset($el->element->class) ? ' class="'.$el->element->class.'"' : 'class="btn btn-default form-control"'; ?><?php echo isset($el->element->style) ? ' style="'.$el->element->style.'"' : ''; ?><?php echo isset($el->additional) ? $el->additional : ''; ?><?php echo isset($el->id) ? ' id="'.$el->id.'"' : ''; ?> value="<?php echo $el->value; ?>"><?php echo $el->value; ?></button><!-- bootstrap.form.button -->
