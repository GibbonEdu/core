<tr style="height: 60px;">
<?php 
foreach($el->days as $day)
{
	if ($day['status'] == 'empty')
	{?>
	<td style='background-color: #bbbbbb'>
    </td>
    <?php }
	elseif ($day['status'] == 'special')
	{ ?>
    <td style='text-align: center; background-color: #eeeeee; font-size: 10px'>
    	<span style='color: #ff0000'><?php echo $el->tObj->dateConvertBack(date('Y-m-d', $day['timestamp'])); ?><br/><?php echo $day['name']; ?></span><br />
		<?php
        $this->getLink('edit', array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage_edit.php', 'gibbonSchoolYearSpecialDayID'=>$day['specialDayID'], 'gibbonSchoolYearID'=>$day['schoolYearID']));
		$this->getLink('delete', array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage_delete.php', 'gibbonSchoolYearSpecialDayID'=>$day['specialDayID'], 'gibbonSchoolYearID'=>$day['schoolYearID']));
		?>
    </td>
	<?php }
	elseif ($day['status'] == 'normal')
	{ ?>
    <td style='text-align: center; background-color: #eeeeee; font-size: 10px'>
    	<span style='color: #000000'><?php echo $el->tObj->dateConvertBack(date('Y-m-d', $day['timestamp'])); ?><br/><?php echo $this->__('School Day'); ?></span><br />
		<?php
		$this->getLink('add', array('q'=>'/modules/School Admin/schoolYearSpecialDay_manage_edit.php', 'gibbonSchoolYearID'=>$day['schoolYearID'], 'dateStamp'=> $day['timestamp'], 'gibbonSchoolYearTermID'=>$day['termID'], 'firstDay'=> $day['firstDay'], 'lastDay'=> $day['lastDay'], 'gibbonSchoolYearSpecialDayID'=>'Add'));
		?>
    </td>
	<?php } 
} ?>
</tr>
