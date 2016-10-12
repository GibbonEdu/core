<input name="<?php echo $el->name; ?>" id="<?php echo isset($el->id) ? $el->id : $el->name ; ?>"<?php echo isset($el->value) ? 'value="'.$this->htmlPrep($el->value).'"' : '' ; ?> type="checkbox"
<?php echo isset($el->element->class) ? ' class="'.$el->element->class . '"' : '' ; ?>
<?php echo isset($el->element->style) ? ' style="' . $el->element->style . '"' : '' ; ?>
<?php echo isset($el->checked) && $el->checked ? ' checked' : '' ; ?><?php echo isset($el->additional)? $el->additional : '' ; ?>
/>
<!-- bootstrap.form.nothing.checkbox -->
