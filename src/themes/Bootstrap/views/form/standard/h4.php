<div>
    <div class="row form-group form-element">
    	<div class="col-md-12 col-lg-12">
			<?php $params->titleDetails = isset($params->titleDetails) ? $params->titleDetails : array() ; ?>
            <?php $this->render('default.h4', $params); ?>
            <?php echo isset($params->note) ? '<p>'.Gibbon\trans::__($params->note, isset($params->noteDetails) ? $params->noteDetails : array()).'</p>' : NULL ; ?>  
        </div>
	</div>
</div><!-- bootstrap.form.wrapper.h4 -->