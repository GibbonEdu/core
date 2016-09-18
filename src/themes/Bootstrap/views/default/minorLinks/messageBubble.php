<?php
use Gibbon\core\trans ;
if ($el->isHouseLogo) { //Spacing with house logo
	$el->bubbleLeft = $el->bubbleLeft - 70 ; ?>
    <div id='messageBubbleArrow' style="left: 1019px; top: 58px; z-index: 9999" class='arrow top'></div>
	<div id='messageBubble' style="left: <?php echo $el->bubbleLeft; ?>px; top: 74px; width: <?php echo $el->bubbleWidth; ?>px; min-width: <?php echo $el->bubbleWidth; ?>px; max-width: <?php echo $el->bubbleWidth; ?>px; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip"><?php
}
else { //Spacing without house logo ?>
	<div id='messageBubbleArrow' style="left: 1089px; top: 38px; z-index: 9999" class='arrow top'></div>
	<div id='messageBubble' style="left: <?php echo $el->bubbleLeft; ?>px; top: 54px; width: <?php echo $el->bubbleWidth; ?>px; min-width: <?php echo $el->bubbleWidth; ?>px; max-width: <?php echo $el->bubbleWidth; ?>px; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip"><?php
} ?>
	<div class="ui-tooltip-content">
		<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'><?php echo trans::__('New Messages'); ?></div><?php
		$test = count($el->output) ;
		if ($test > 3) {
			$test = 3 ;
		}
		for ($i=0; $i<$test; $i++) { ?>
			<span style='font-size: 120%; font-weight: bold'> <?php
			if (strlen($el->output[$i]["subject"]) <= 30) {
				echo $el->output[$i]["subject"] ;
			}
			else {
				echo substr($el->output[$i]["subject"],0,30) . "..." ;
			} ?>
			</span><br/>
			<em><?php echo $el->output[$i]["author"]; ?></em><br/><br/><?php
		}
		if (count($el->output)>3) { ?>
			<em><?php echo trans::__( 'Plus more'); ?>...</em><?php
		} ?>
	</div>
	<div style='text-align: right; margin-top: 20px; color: #666'>
		<a onclick='$("#messageBubble").hide("fade", {}, 1); $("#messageBubbleArrow").hide("fade", {}, 1)' style='text-decoration: none; color: #666' href='<?php echo $el->URL; ?>'><?php echo trans::__('Read All'); ?></a>
		<a style='text-decoration: none; color: #666' onclick='$("#messageBubble").hide("fade", {}, 1000); $("#messageBubbleArrow").hide("fade", {}, 1000)' href='#'><?php echo trans::__('Dismiss'); ?></a>
	</div>
</div>
