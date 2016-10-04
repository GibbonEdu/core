<?php
$el->hideDisplay = empty($el->hideDisplay) ? false : $el->hideDisplay ; 
foreach ($el->options as $value=>$el->display) {  ?>
    <div class="row" style="border: none">
        <div class="col-lg-8 col-md-8 right">
            <?php echo ! $el->hideDisplay ? '<label class="checkBoxLabel" for="'.(isset($el->display->id) ? $el->display->id : $el->name).'">'.$this->__($el->display->display).'</label>&nbsp;' : '' ; ?>
        </div>
        <div class="col-lg-4 col-md-4">
        	<?php 
			$this->render('form.radio', $el); ?>
        </div>
    </div>
<?php } ?><!-- bootstrap.form.standard.radio -->