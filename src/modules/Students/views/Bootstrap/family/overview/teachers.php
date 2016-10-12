<?php $this->startWell(); 
$this->h3("Student's Teachers");
if (count($el) == 0) {
	$this->displayMessage('There are no records to display.');
} else { ?>
	<ul> <?php
	foreach($el as $teacher) { ?>
		<li><?php echo $this->htmlPrep($teacher->formatName(false));
		if (! empty($teacher->getField('email'))) { ?>
&nbsp;<a href="mailto:<?php echo $teacher->getField('email') ?>" title="<?php echo $this->__("Teacher's Email"); ?>">&lt;<?php echo $teacher->getField('email') ?>&gt;</a><?php
		} ?> </li><?php
	} ?>
	</ul><?php
}
$this->endWell();
