<?php if (count($el) > 0) {
	$this->startWell();
	$this->h4('Siblings'); ?>
	<div class="row alternate"><?php
		foreach($el as $sibling) { ?>
			<div class="col-lg-4 col-md-4" style="text-align: center"> <?php
			//User photo
				echo Gibbon\helper::getUserPhoto($sibling->getField('image_240'), 75); ?>
				<div style='padding-top: 5px'><strong> <?php
					if ($sibling->getField('status') == 'Full') { ?>
						<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=<?php echo $sibling->getField('gibbonPersonID'); ?>'><?php echo $sibling->getPerson()->formatName(); ?></a><br/><?php
					} else {
						echo $sibling->getPerson()->formatName(); ?><br/> <?php
					} ?>
					<span style='font-weight: normal; font-style: italic'><?php echo Gibbon\trans::__('Status').': '.$sibling->getField('status'); ?></span>
				</strong></div>
			</div> <?php
		} ?>
	</div><?php
	$this->endWell();
}
