<?php
//$el->value = $el->value == 'Y' ? true : false ;
if ($el->value == 'On') $el->checked = true;
$this->render('form.standard.rowStart', $el); 
$el->value = 'On';
$this->render('form.yesno', $el);
$this->render('form.standard.rowEnd', $params);
$disabled = isset($el->disabled) && $el->disabled ? 'disabled: true,' : '';

$this->addScript("
<script type='text/jscript' language='javascript'>
	$('[name=\'".$el->name."\']').bootstrapSwitch({
		onText: '" . mb_strtoupper($this->__('On')) . "',
		offText: '" .mb_strtoupper($this->__('Off')) . "',
		onColor: 'success',
		offColor: 'warning',
		".$disabled."
		size: 'small'
	});
	$('input[name=\"".$el->name."\"]').on('switchChange.bootstrapSwitch', function(event, state) {
		if (state) {
			$('input[name=\'".$el->name."\']').val('On');
			$('input[name=\'".$el->name."\']').prop('checked', true);
		} else {
			$('input[name=\'".$el->name."\']').prop('checked', false);
		}
	});
</script>
");
?>
<input type="hidden" name="boolean[<?php echo $el->name; ?>]" value="Off" /><!-- bootstrap.form.standard.yesno -->
