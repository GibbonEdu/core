<?php
$output = false ;

$category = $this->getSecurity()->getRoleCategory($this->session->get("gibbonRoleIDCurrent")) ;
if ($category == "Parent") {
	$x = new stdClass();
	$x->target = 'flash-photo';
	$output .= $this->renderReturn('default.flash', $x);

	if ($this->session->isEmpty("image_240")) { //No photo, so show uploader
		$form = $this->getForm(null, array('q'=> '/modules/User Admin/index_parentPhotoUploadProcess.php', 'gibbonPersonID' => $this->session->get("gibbonPersonID")), true, 'photoLoader', true);
		
		$form->setStyle('smallIntBorder');
		
		$form->addElement('h3', null, 'Profile Photo');
		
		$form->addELement('paragraph', null, array('Please upload a passport type photo to use as a profile picture. %1$spx by %2$spx.', array('240', '320')));
	
		$el = $form->addElement('photo', 'file1');
		$el->addButton('Go!', 'submit', 'right');
		$el->button->class = 'btn btn-success';
		
		$output .= $form->renderReturn('smallIntBorder');
	}
	else
	{ 
		//Photo, so show image and removal link
		$g = new \Gibbon\People\guardian($this, $this->session->get("gibbonPersonID"));
		$output .= $this->renderReturn('guardian.photo', $g);
	}
}

if ($output !== false) echo $output; 

