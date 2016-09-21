<form<?php echo isset($el->method) ? ' method="'.$el->method.'"' : ' method="post"'; ?> action="<?php echo $el->getTarget(); ?>"<?php echo ! is_null($el->get('role')) ? ' role="'.$el->get('role').'"' : ' role="form"' ; ?><?php echo ! empty($el->get('id')) ? ' id="'.$el->get('id').'"' : ' id="TheForm"' ;?><?php echo ! is_null($el->get('class')) ? ' class="'.$el->get('class').'"' : '' ;?><?php echo ! is_null($params->get('additional')) ? $params->get('additional') : '' ; ?><?php echo ! empty($el->get('enctype')) ? ' enctype="'.$el->get('enctype').'"' : '' ;?>>
    <div class='container-fluid'>
    	<div class='well noIntBorder-well'>
    	<?php $this->render('form.style.elements', $el); ?>	
        </div>
    </div>

</form><!-- bootstrap.form.style.standard --><?php
$id = ! empty($el->get('id')) ? ' id="'.$el->get('id').'"' : ' id="TheForm"' ; ;
$this->addScript("
<script>
    $(document).ready(function() {
        $('#".$id."').formValidation();
    });
</script>
");


