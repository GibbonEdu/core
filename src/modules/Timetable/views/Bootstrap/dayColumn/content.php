<div class='<?php echo $el->class?>' <?php echo $el->title?> style='z-index: <?php echo $el->zCount?>; position: absolute; top: <?php echo $el->top; ?>; width: <?php echo $el->width?>; height: <?php echo $el->height?>; margin: 0px; padding: 0px; opacity: <?php echo $el->ttAlpha; ?>;'><?php
    if ($el->height > 15 and $el->height < 30) {
		echo $el->name.'<br/>';
	} elseif ($el->height >= 30) {
		echo $el->name.'<br/>';
		echo '<em>'.substr($el->effectiveStart, 0, 5).'-'.substr($el->effectiveEnd, 0, 5).'</em><br/>';
    } ?>
</div>
<!-- dayColumn.content -->
