<div class="col-lg-3 col-md-3 col-sm-10 col-xs-10">
     <?php echo $el->name.'.yml' ; ?>
</div>      
<div class="col-lg-7 col-md-7 hidden-sm hidden-xs">
     <?php echo $el->status  ; ?>
</div>      
<div class="col-lg-2 col-md-2 centre border">
	<?php $this->render('fileManage.update', $el); ?>
</div>
<script type="text/javascript">
	$("#file_<?php echo $el->name; ?>").removeClass().addClass("row<?php echo ! empty($el->rowNum) ? ' '.$el->rowNum : '' ; ?>");
</script>