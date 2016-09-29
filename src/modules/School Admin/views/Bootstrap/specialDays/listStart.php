<table cellspacing='0' style='width: 100%'>
    <thead>
        <tr class='head'>
        	<?php 
			$day = 0;
			for($i=$el; $i < $el + 7*86400; $i=$i+86400)
			{ ?>
            <th style='width: <?php echo in_array($day, array(0, 6)) ? '15%' : '14%' ;?>; text-align:center'>
            	<?php echo $this->__(date('l', $i)); ?>
            </th><?php
				$day++;
			}
			?>
         </tr><!-- specialDays.listStart  -->
    </thead>
    <tbody>
