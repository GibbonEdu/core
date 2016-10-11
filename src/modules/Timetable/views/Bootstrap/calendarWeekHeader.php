        <tr class='head'>
            <th class='mini-header'><?php
            //Calculate week number
            if (false !== ($week = $this->getWeekNumber($el->startDayStamp))) echo $this->__('Week %1$s', array($week)).'<br/>'; ?>
            
            <span style='font-weight: normal; font-style: italic;'><?php echo $this->__('Time'); ?><span>
            </th>
            <?php 
            foreach ($el->days as $day) {
                if ($day['schoolDay'] == 'Y') {
                    $dateCorrection = ($day['sequenceNumber'] - $el->dateCorrectionOffSet);
                    ?>
                    <th class='calendarDay' style='width: <?php echo $el->width; ?> ;'><?php echo $this->__($day['nameShort']).'<br/>'; ?>
                        <span style='font-size: 80%; font-style: italic'><?php echo date($this->session->get('i18n.dateFormatPHP'), ($el->startDayStamp + (86400 * $dateCorrection)))?></span><br/><?php
                        $dataSpecial = array('date' => date('Y-m-d', ($el->startDayStamp + (86400 * $dateCorrection))));
                        $sqlSpecial = "SELECT `name` 
                            FROM `gibbonSchoolYearSpecialDay` 
                            WHERE `date`=:date 
                                AND `type`='Timing Change'";
                        $specialDay = $this->getRecord('schoolYearSpecialDay')->findAll($sqlSpecial, $dataSpecial);
                        if (count($specialDay) == 1) {?><span style='font-size: 80%; font-weight: bold'><u><?php $xx = reset($specialDay);  echo $xx->getField('name'); ?></u></span><?php } ?>
                    </th><?php
                }
            } ?>
        </tr>
    </thead>
	<tbody>
<!-- calendarWeekHeader -->
