<form method="<?php echo $el->get('method'); ?>" action="<?php echo $el->getTarget(); ?>"<?php echo ! $el->isEmpty('role') ? ' role="'.$el->get('role').'"' : ' role="form"' ; ?><?php echo ! $el->isEmpty('class') ? ' class="'.$el->get('class').'"' : '' ;?><?php echo ! $el->isEmpty('additional') ? $el->get('additional') : '' ; ?> id="<?php echo $el->get('id'); ?>" name="<?php echo $el->get('id'); ?>"<?php echo ! $el->isEmpty('enctype') ? ' enctype="'.$el->get('enctype').'"' : '' ;?>>
    	<?php $this->render('form.style.nothing.elements', $el); ?>	
</form><!-- bootstrap.form.style.nothing -->
