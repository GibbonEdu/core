<div<?php echo isset($el->row->class) ? ' class="'.$el->row->class.'"' : ''; ?>>
	<div class="row form-group form-element">
    	<div class="col-md-12 col-lg-12">
			<?php $params->titleDetails = isset($params->titleDetails) ? $params->titleDetails : array() ; ?>
            <?php $this->render('default.h3', $params); ?>
            <?php echo isset($params->note) ? '<p>'.Gibbon\core\trans::__($params->note, isset($params->noteDetails) ? $params->noteDetails : array()).'</p>' : NULL ; ?>  
        </div>                              
    </div>
</div><!-- bootstrap.form.standard.h3 -->