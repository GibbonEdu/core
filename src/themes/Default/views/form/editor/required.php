<?php if ($el->required) { 
	$x = '';
	if ($el->initiallyHidden) { 
		$x =  $el->id.'.disable();';
	} 
	$this->addScript('
<script type="text/javascript">
	var '.$el->id.' = "";
	'.$el->id.'= new LiveValidation("'.$el->id.'");
	'.$el->id.'.add(Validate.Presence, { tinymce: true, tinymceField: "'.$el->id.'"});
	'.$x.'
</script>
');
}
