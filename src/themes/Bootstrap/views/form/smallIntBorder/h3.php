<div<?php echo isset($el->row->class) ? ' class="'.$el->row->class.'"' : ''; ?>>
	<div class="row form-group form-element h3">
    	<div class="col-md-12 col-lg-12">
			<?php
			if (isset($el->note) && is_array($el->note))
			{
				$el->noteDetails = $el->note[1];
				$el->note = $el->note[0];
			}
			$el->titleDetails = isset($el->titleDetails) ? $el->titleDetails : array() ; ?>
            <?php $this->render('default.h3', $el); ?>
            <?php echo isset($el->note) ? '<p>'.$this->__($el->note, isset($el->noteDetails) ? $el->noteDetails : array()).'</p>' : NULL ; ?>
        </div>
    </div>
</div>
<!-- bootstrap.form.smallIntBorder.h3 -->
