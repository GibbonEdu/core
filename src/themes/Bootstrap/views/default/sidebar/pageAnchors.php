<?php if ($this->session->notEmpty('pageAnchors')) { 
	
	$list = $this->startList('ul', 'moduleMenu')
		->addHeader($this->h4('Page Anchors', array(), true));
	foreach($this->session->get('pageAnchors') as $link=>$name)
	{
		$x = array('<a href="#'.$link.'">'.Gibbon\core\trans::__($name).'</a>');
		$list->addListElement('%1$s', $x);
	}
	$list->renderList($this);
	$this->session->clear('pageAnchors');
}
