<form method="post" action="<?php echo $el->getTarget(); ?>" role="<?php echo isset($el->role) ? $el->role : 'form' ; ?>" id="loginForm" name="login"<?php echo ! $el->isEmpty('enctype') ? ' enctype="'.$el->get('enctype').'"' : '' ;?>>
    <table class='noIntBorder' cellspacing='0' style="width: 100%; margin: 0px 0px">
    	<?php $this->render('form.style.elements', $el); ?>	
    </table>
</form>
