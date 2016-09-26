<?php $this->render('form.text', $el); $this->addScript('
<script type="text/javascript">
	$( "#'.isset($el->id) ? $el->id : $el->name.'" ).datepicker({
		onClose: function () { this.focus(); }
	});
</script>
'); ?><!-- form.nothing.date -->
