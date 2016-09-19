<form<?php echo isset($el->method) ? ' method="'.$el->method.'"' : ' method="post"'; ?>  action="<?php echo $el->getTarget(); ?>" role="<?php echo isset($el->role) ? $el->role : 'form' ; ?>"<?php echo isset($el->id) ? ' id="'.$el->id.'"' : ' id="TheForm"' ;?><?php echo ! $el->isEmpty('class') ? ' class="'.$el->get('class').'"' : '' ;?><?php echo ! $el->isEmpty('enctype') ? ' enctype="'.$el->get('enctype').'"' : '' ;?>>
    <table class='smallIntBorder fullWidth' cellspacing='0'>
    	<?php $this->render('form.style.elements', $el); ?>	
    </table>
</form>
