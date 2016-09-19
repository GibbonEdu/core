<div class="row" style="border: none">
	<div class="col-lg-8 col-md-8 right">
		<?php echo isset($el->label) ? '<label class="checkBoxLabel" for="'.(isset($el->id) ? $el->id : $el->name).'">'.$el->label.'</label>&nbsp;': '' ; ?>
	</div>
    <div class="col-lg-4 col-md-4">
		<input name="<?php print $el->name ?>" id="<?php echo isset($el->id) ? $el->id : $el->name ; ?>" value="<?php print $this->htmlPrep($el->value) ?>" type="checkbox" <?php echo isset($el->element->class) ?  ' class="'.$el->element->class . '"' : '' ; ?>
<?php echo isset($el->style->input) ? ' style="' . $el->style->input . '"' : '' ; ?>
<?php echo isset($el->checked) && $el->checked ? ' checked' : '' ; ?> />
	</div>
</div><!-- bootstrap.form.checkbox -->