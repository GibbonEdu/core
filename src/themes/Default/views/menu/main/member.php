<?php 
use Gibbon\core\trans ;
if ($el->currentCategory != $el->lastCategory) {
	if ($el->count > 0) { ?>
		</ul></li><?php
	} ?>
	<li><a href='#'><?php echo trans::__($el->currentCategory) ; ?></a>
		<ul>
			<li><a href='<?php echo $this->session->get("absoluteURL") ?>/index.php?q=/modules/<?php echo $el->name ; ?>/<?php echo $el->entryURL ; ?>'><?php echo trans::__($el->name); ?></a></li> <?php
}
else { ?>
	<li><a href='<?php echo $this->session->get("absoluteURL") ?>/index.php?q=/modules/<?php echo $el->name ; ?>/<?php echo $el->entryURL ; ?>'><?php echo trans::__($el->name); ?></a></li> <?php
}
