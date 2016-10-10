 <tr style='height: <?php echo ((31 * $el->maxAllDays) + 5)?>px'>
     <td class='allDayEvent'>
     	<span style='font-size: 80%'><strong><?php echo $this->__('All Day%1$s Events', array('<br/>'))?></strong></span>
     </td>
     <td colspan='<?php echo $el->daysInWeek; ?>' class='allDayEvent'>
     </td>
 </tr><!-- 'allDayEventSpace' -->
