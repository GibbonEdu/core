<?php	
use Gibbon\trans ;

$extendedBriefProfile = $this->config->getSettingByScope('Students', 'extendedBriefProfile');
if (true || $extendedBriefProfile == 'Y') {
	$el->student->getFamily();
	$this->startWell();

	$this->h3('Family Details');

	if (! $el->student->validFamily)
		$this->displayMessage('There are no records to display.');
	else 
	{
		$count = 0;
		//Get adults
		foreach($el->student->getFamilyAdults() as $adult) { 
			$this->h4(trans::__(array('Adult %1$s', array(++$count))));
			?>
			<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 33%; vertical-align: top'>
                        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Name'); ?></span><br/>
                        <?php echo $adult->getPerson()->formatName(); ?>
					</td>
					<td style='width: 33%; vertical-align: top'>
                        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('First Language'); ?></span><br/>
                        <?php echo $adult->getField('languageFirst'); ?>
					</td>
					<td style='width: 34%; vertical-align: top'>
                        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Second Language'); ?></span><br/>
                        <?php echo $adult->getField('languageSecond'); ?>
					</td>
				</tr>
				<tr>
					<td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>
						<span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Contact By Phone'); ?></span><br/>
						<?php if ($adult->getField('contactCall') == 'N') {
							echo trans::__('Do not contact by phone.');
						} elseif ($adult->getField('contactCall') == 'Y' &&
							(! empty($adult->getField('phone1')) || ! empty($adult->getField('phone2')) || ! empty($adult->getField('phone3')) || ! empty($adult->getField('phone4')))) {
							for ($i = 1; $i < 5; ++$i) {
								if (! empty($adult->getField('phone'.$i)) && ! empty($adult->getField('phone'.$i.'Type'))) {
										echo '<em>'.$adult->getField('phone'.$i.'Type').':</em>';
								}
								if (! empty($adult->getField('phone'.$i.'CountryCode'))) {
									echo '+'.$adult->getField('phone'.$i.'CountryCode'); 
								}
								echo helper::formatPhone($adult->getField('phone'.$i)); ?><br/><?php
							}
						} ?>
					</td>
					<td style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan='2'>
						<span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Contact By Email'); ?></span><br/>
						<?php if ($adult->getField('contactEmail') == 'N') {
							echo trans::__('Do not contact by email.');
						} elseif ($adult->getField('contactEmail') == 'Y' and (! empty($adult->getField('email')) || ! empty($adult->getField('emailAlternate')))) {
							if ($adult->getField('email') != '') { ?>
								<a href='mailto:<?php echo $adult->getField('email'); ?>'><?php echo $adult->getField('email'); ?></a><br/> <?php
							}
							?><br/><?php
						} ?>
					</td>
				</tr>
			</table><?php
		}
	}
	$this->endWell();
}
