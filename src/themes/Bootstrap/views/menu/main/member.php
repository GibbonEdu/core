<?php 
use Gibbon\core\trans ;
if ($el->currentCategory != $el->lastCategory) {
	if ($el->count > 0) { ?>
		</ul></li><?php
	} ?>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo $this->__($el->currentCategory) ; ?></a>
        <ul class="dropdown-menu">
<<<<<<< HEAD
            <li><a href='<?php echo $this->session->get("absoluteURL") ?>/index.php?q=/modules/<?php echo $el->name ; ?>/<?php echo $el->entryURL ; ?>'><?php echo $this->__($el->name); ?></a></li> <?php
}
else { ?>
			<li><a href='<?php echo $this->session->get("absoluteURL") ?>/index.php?q=/modules/<?php echo $el->name ; ?>/<?php echo $el->entryURL ; ?>'><?php echo $this->__($el->name); ?></a></li> <?php
=======
            <li><a href='<?php echo GIBBON_URL ?>index.php?q=/modules/<?php echo $el->name ; ?>/<?php echo $el->entryURL ; ?>'><?php echo $this->__($el->name); ?></a></li> <?php
}
else { ?>
			<li><a href='<?php echo GIBBON_URL ?>index.php?q=/modules/<?php echo $el->name ; ?>/<?php echo $el->entryURL ; ?>'><?php echo $this->__($el->name); ?></a></li> <?php
>>>>>>> 9f852d0fb1c6b3799f833bd1593409785cc98f71
} 
