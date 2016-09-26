<?php
if ( empty($el)) 
{
	$el = new stdClass();
	$el->target = 'flash';
}
$this->getReturn();  // Old stuff
echo $this->session->get($el->target);
$this->session->clear($el->target);
