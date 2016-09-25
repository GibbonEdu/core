<?php $val = $el->insertValidation($el, true);
if (! empty($val)) { 
	$this->addScript('
<script type="text/javascript">
    var '.$params->id.' = new LiveValidation("'.$params->id.'");
	'.$val.'
	// form.standard.validate
</script>
');
}
