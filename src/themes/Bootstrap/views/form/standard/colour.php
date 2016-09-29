<?php $this->render('form.standard.rowStart', $el); ?>
<div class="container-fluid row" id="div_<?php echo $el->id; ?>" style="background-color: #<?php echo $el->value; ?>; border: 1px SOLID #<?php echo $el->value; ?>; border-radius: 5px ">
	<div class="col-md-offset-6 col-lg-offset-6 col-md-6 col-lg-6">
    	<?php $this->render('form.text', $el); ?>
    </div>
</div>
<?php $this->render('form.standard.rowEnd', $el); ?>
<script>
$(document).ready(function(){
	$("#<?php echo $el->id; ?>").on('change', function(){
		$('#div_<?php echo $el->id; ?>').css("background-color", "#" + $("#<?php echo $el->id; ?>").val());
		$('#div_<?php echo $el->id; ?>').css("border-color", "#" + $("#<?php echo $el->id; ?>").val());
	});
});
</script><!-- bootstrap.form.colour -->