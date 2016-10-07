<tr style='height: <?php echo (ceil($el->diffTime / 60) + 14)?>px'>
    <td class='ttTime'>
        <div style='position: relative; width: 71px'><?php
			$countTime = 0;
			$time = $el->timeStart;
			?><div <?php echo $el->title ?> style='z-index: <?php echo $el->zCount ?>; position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>
			<?php echo substr($time, 0, 5).'<br/>'; ?>
			</div><?php
			$time = date('H:i:s', strtotime($time) + 3600);
			$spinControl = 0;
			while ($time <= $el->timeEnd and $spinControl < (23 - substr($el->timeStart, 0, 5))) {
				++$countTime;
				?><div <?php echo $el->title ?> style='z-index: <?php echo $el->zCount ?>; position: absolute; top:<?php echo (($countTime * 60) - 5)?>px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>
				<?php echo substr($time, 0, 5).'<br/>'; ?>
				</div><?php
				$time = date('H:i:s', strtotime($time) + 3600);
				++$spinControl;
			}
			
        ?></div>
    </td><!-- 'weekDetailsStart' -->
    