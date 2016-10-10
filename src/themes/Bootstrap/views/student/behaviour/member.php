    <tr>
        <td>
        <strong><?php echo $el->getPerson()->formatName(false)?></strong><br/><?php
		if (substr($el->getField('timestamp'), 0, 10) > $el->getField('date')) {
			echo $this->__('Date Updated').': '.$el->dateConvertBack(substr($el->getField('timestamp'), 0, 10)).'<br/>';
			echo $this->__('Incident Date').': '.$el->dateConvertBack($el->getField('date')).'<br/>';
		} else {
			echo  $el->dateConvertBack($el->getField('date')).'<br/>';
		} ?>
        </td>
        <td style='text-align: center'>
            <?php if ($el->getField('type') == 'Negative')
				$this->getIcon('cross', 'Negative');
            elseif ($el->getField('type') == 'Positive')
				$this->getIcon('tick', 'Positive'); ?>
        </td> 
        <td>
        <?php echo trim($el->getField('descriptor')); ?>
        </td>
        <td>
        <?php echo trim($el->getField('level')); ?>
        </td>
        <td>
        <?php
	    echo $el->getPerson($el->getField('gibbonPersonIDCreator'))->formatName('Staff', false).'<br/>';
		?>
        </td>
        <td>
        <?php $this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		$(".comment-'.$el->getField('gibbonBehaviourID').'").hide();
		$(".show_hide-'.$el->getField('gibbonBehaviourID').'").fadeIn(1000);
		$(".show_hide-'.$el->getField('gibbonBehaviourID').'").click(function(){
			$(".comment-'.$el->getField('gibbonBehaviourID').'").fadeToggle(1000);
		});
	});
</script> 
		');
		if (! empty($el->getField('comment'))) { 
			$this->getLink('page down', array('class'=>'show_hide-'.$el->getField('gibbonBehaviourID'), 'onclick'=>'false', 'href'=>'#', 'title'=>'View Description'));
        } ?>
        </td>
    </tr>
    <?php                             
	if (! empty($el->getField('comment'))) {
		if ($el->getField('type') == 'Positive') {
			$bg = 'background: none; background-color: #D4F6DC;' ;
		} else {
			$bg = 'background: none; background-color: #F6CECB;' ;
		} ?>
        <tr class='comment-<?php echo $el->getField('gibbonBehaviourID');?>' id='comment-<?php echo $el->getField('gibbonBehaviourID');?>'>
            <td style='<?php echo $bg; ?>' colspan='6'>
                <?php echo $el->getField('comment'); ?>
            </td>
        </tr>
    <?php } ?>
    </tr>
</tr>
