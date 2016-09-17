<div<?php echo isset($el->row->class) ? ' class="'.$el->row->class.'"' : ''; ?>>
    <div class="row form-group form-element">
    	<div class="col-md-12 col-lg-12">
			<?php 
			if (isset($el->note) && is_array($el->note))
			{
				$el->noteDetails = $el->note[1];
				$el->note = $el->note[0];
			}
			$el->titleDetails = isset($el->titleDetails) ? $el->titleDetails : array() ; ?>
            <?php $this->render('default.h4', $el); ?>
            <?php echo isset($el->note) ? '<p>'.Gibbon\core\trans::__($el->note, isset($el->noteDetails) ? $el->noteDetails : array()).'</p>' : null ; ?>  
        </div>
	</div>
</div><!-- bootstrap.form.wrapper.h4 -->