<div class='<?php echo $el->class ?>'><?php
	$xx = (($el->getPage() * $el->get('pagination')) > $el->get('total')) ? $el->get('total') : ($el->getPage() * $el->get('pagination'));
	echo $this->__('Records&nbsp;%1$s-%2$s&nbsp;', array(($el->getPage()-1) * $el->get('pagination') + 1, $xx));
			
	if ($el->get('totalPages') <= 10) {
		for ($i=0; $i<=($el->get('total')/$el->get('pagination')); $i++) 
		{
			if ($i == ($el->getPage()-1)) 
				echo $el->getPage().' ';
			else
				$this->getLink('', GIBBON_URL . 'index.php?q=' . $this->session->get("address") . '&page=' . ($i+1) . $el->getString, ($i+1));
		}
	}
	else {
		if ($el->getPage() > 1) {
			$this->getLink('first', array('q'=> $this->session->get("address"), 'page' => 1 . $el->getString), 'First');
			$this->getLink('previous', array('q'=> $this->session->get("address"), 'page' => ($el->getPage()-1) . $el->getString), 'Previous');
		}

		$spread = 10 ;
		for ($i=0; $i <= ($el->get('total')/$el->get('pagination')); $i++) {
			if ($i == ($el->getPage()-1))
				echo $el->getPage(). ' ' ;
			elseif ($i > ($el->getPage()-(($spread/2)+2)) && $i < ($el->getPage()+(($spread/2)))) 
				$this->getLink('', array('q'=> $this->session->get("address"), 'page' => ($i+1) . $el->getString), ($i+1));
		}

		if ($el->getPage() < $el->get('totalPages')) {
			$this->getLink('next', array('q'=> $this->session->get("address"), 'page' => ($el->getPage()+1) . $el->getString), 'Next');
			$this->getLink('last', array('q'=> $this->session->get("address"), 'page' => $el->get('totalPages') . $el->getString), 'Last');
		}
	} ?>
</div>