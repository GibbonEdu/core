<?php
use Gibbon\core\trans ;
if ($el->value == 'Y') $el->checked = true;
$this->render('form.standard.rowStart', $el); 
$el->value = 'Y';
$this->render('form.yesno', $el);
$this->render('form.standard.rowEnd', $params); ?>
<script type="text/jscript" language="javascript">
	$("[name='<?php echo $el->name; ?>']").bootstrapSwitch({
		onText: '<?php echo mb_strtoupper(trans::__('Y')); ?>',
		offText: '<?php echo mb_strtoupper(trans::__('N')); ?>',
		onColor: 'success',
		offColor: 'warning',
		<?php if (isset($el->disabled) && $el->disabled) { ?>
		disabled: true,
		<?php } ?>
		size: 'small'
	});
	$('input[name="<?php echo $el->name; ?>"]').on('switchChange.bootstrapSwitch', function(event, state) {
		if (state) {
			$('input[name="<?php echo $el->name; ?>"]').val('Y');
			$('input[name="<?php echo $el->name; ?>"]').prop('checked', true);
		} else {
			$('input[name="<?php echo $el->name; ?>"]').prop('checked', false);
		}
	});
</script>
<input type="hidden" name="boolean[<?php echo $el->name; ?>]" value="N" /><!-- bootstrap.form.standard.yesno -->
