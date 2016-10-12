<div<?php echo isset($el->row->class) ? ' class="'.$el->row->class.'"' : ''; ?>>
    <div class="row form-group form-element paragraph">
    	<div class="col-md-12 col-lg-12">
			<?php
			$el->titleDetails = isset($el->titleDetails) ? $el->titleDetails : array() ; ?>
            <?php $this->render('default.paragraph', $el); ?>
        </div>
	</div>
</div>
<!-- bootstrap.form.standard.paragraph -->
