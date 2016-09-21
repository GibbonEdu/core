<form method="post" action="<?php echo $el->getTarget(); ?>"<?php echo ! $el->isEmpty('role') ? ' role="'.$el->get('role').'"' : ' role="form"' ; ?><?php echo ! $el->isEmpty('class') ? ' class="'.$el->get('class').'"' : '' ;?><?php echo ! $el->isEmpty('additional') ? $el->get('additional') : '' ; ?> id="loginForm" name="login"<?php echo ! $el->isEmpty('enctype') ? ' enctype="'.$el->get('enctype').'"' : '' ;?>>
    <div class='gibbon-form'>
    	<?php $this->render('form.style.elements', $el); ?>	
    </div>
</form><!-- bootstrap.form.style.login --><?php
$this->addScript("
<script>
$(document).ready(function() {
    $('#loginForm').formValidation();
});
</script>
");
