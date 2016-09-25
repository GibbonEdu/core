<form<?php isset($el->method) ? ' method="'.$el->method.'"' : ' method="post"'; ?> action="<?php echo $el->getTarget(); ?>" role="<?php echo isset($el->role) ? $el->role : 'form' ; ?>"<?php echo ! empty($el->get('id')) ? ' id="'.$el->get('id').'"' : ' id="TheForm"' ;?><?php echo ! empty($el->get('enctype')) ? ' enctype="'.$el->get('enctype').'"' : '' ;?>>
    <table class='noIntBorder fullWidth' cellspacing='0'<?php echo isset($el->table->style) ? ' style="'.$el->table->style.'"' : '' ;?>>
    	<?php $this->render('form.style.elements', $el); ?>	
    </table>
</form>
