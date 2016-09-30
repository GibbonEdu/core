<td>
    <?php echo $el->name.'.yml' ; ?>
</td>
<td>
    <?php echo $el->status ; ?>
</td>
<td class="centre">
	<?php $this->render('fileManage.update', $el); ?>
</td>
<script type="text/javascript">
	$("#file_<?php echo $el->name; ?>").removeClass().addClass("<?php echo ! empty($el->rowNum) ? $el->rowNum : '' ; ?>");
</script>