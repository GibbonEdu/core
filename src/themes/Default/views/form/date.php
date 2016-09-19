<?php $this->render('form.text', $el); ?>
<script type="text/javascript">
	$( "#<?php echo isset($el->id) ? $el->id : $el->name ;?>" ).datepicker({
		onClose: function () { this.focus(); }
	});
</script><!-- form.date -->