<?php $el->hideDisplay = empty($el->hideDisplay) ? false : $el->hideDisplay ; 
foreach ($el->options as $value=>$display) {  ?>
    <div class="row" style="border: none">
        <div class="col-lg-8 col-md-8 right">
            <?php echo ! $el->hideDisplay ? '<label class="checkBoxLabel" for="'.(isset($display->id) ? $display->id : $el->name).'">'.$this->__($display->display).'</label>&nbsp;' : '' ; ?>
        </div>
        <div class="col-lg-4 col-md-4">
            <input<?php echo $display->value == $el->value ? ' checked' : '' ; ?> type="radio" name="<?php echo $el->name; ?>" value="<?php echo $value; ?>"<?php echo isset($el->element->class) ? ' class="' . $el->element->class . '"': '' ; ?> id="<?php echo isset($display->id) ? $display->id : '_' . $el->name ; ?>"<?php echo $el->checked ? ' checked="checked"' : '' ;?> />
        </div>
    </div>
<?php } ?><!-- bootstrap.form.radio -->