<?php if ($this->session->notEmpty("sidebarExtra") AND $this->session->get("sidebarExtraPosition") == "bottom") { ?>
<div class='sidebarExtra'> <?php
			echo $this->session->get("sidebarExtra") ; ?>
</div> <?php
}
