<?php if (empty($el->message) && ! empty($el->title)) {
	$el->message = $el->title ;
	$el->messageDetails = isset($el->titleDetails) ? $el->titleDetails : array() ;
} ?>
<p><?php echo $this->__($el->message, isset($el->messageDetails) ? $el->messageDetails : array()); ?></p>
<!-- default.paragraph -->
