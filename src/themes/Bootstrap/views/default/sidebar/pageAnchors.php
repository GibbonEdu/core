<?php if ($this->session->notEmpty('pageAnchors')) { 
	
	$anchors = $this->session->get('pageAnchors');
	if (! empty($anchors)) {
		$list = $this->startList('ul', 'moduleMenu')
			->addHeader($this->h4('Page Anchors', array(), true));
		$x = array('<a href="#wrapOuter">'.$this->__('Top').'</a>');
		$list->addListElement('%1$s', $x);
		foreach($anchors as $link=>$name)
		{
			$x = array('<a href="#'.$link.'">'.$this->__($name).'</a>');
			$list->addListElement('%1$s', $x);
		}
		$x = array('<a href="#footer">'.$this->__('Bottom').'</a>');
		$list->addListElement('%1$s', $x);
		$list->renderList($this);
	}
	$this->session->clear('pageAnchors');
}
