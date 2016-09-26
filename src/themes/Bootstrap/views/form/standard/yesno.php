<?php
use Gibbon\core\trans ;
if ($el->value == 'Y') $el->checked = true;
$this->render('form.standard.rowStart', $el); 
$el->value = 'Y';
$this->render('form.yesno', $el);
$this->render('form.standard.rowEnd', $params); 
$disabled = isset($el->disabled) && $el->disabled ? 'disabled: true,' : '';
$this->addScript('
<script type="text/jscript" language="javascript">
	$("[name=\''.$el->name.'\']").bootstrapSwitch({
		onText: "'.mb_strtoupper($this->__('Yes')).'",
		offText: "'.mb_strtoupper($this->__('No')).'",
		onColor: "success",
		offColor: "warning",
		'.$disabled.'
		size: "small"
	});
	$("input[name=\''.$el->name.'\']").on("switchChange.bootstrapSwitch", function(event, state) {
		if (state) {
			$(\'input[name="'.$el->name.'"]\').val("Y");
			$(\'input[name="'.$el->name.'"]\').prop("checked", true);
		} else {
			$(\'input[name="'.$el->name.'"]\').prop("checked", false);
		}
	});
</script>
');?>
<input type="hidden" name="boolean[<?php echo $el->name; ?>]" value="N" /><!-- bootstrap.form.standard.yesno -->
