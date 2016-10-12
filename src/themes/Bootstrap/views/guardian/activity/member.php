<?php
$row = (array)$el->returnRecord();
$options = $this->config->getSettingByScope('Activities', 'activityTypes');
//COLOR ROW BY STATUS! ?>
<tr>
    <td>
    	<?php echo $row['name']; ?>
    </td>
    <?php if (! empty($options)) { ?>
        <td>
        	<?php echo trim($row['type']); ?>
        </td><?php
    } ?>
    <td>
    <?php 			$dateType = $this->config->getSettingByScope('Activities', 'dateType');
		if ($dateType != 'Date') {
        	$terms = $this->getRecord('schoolYear')->getTerms($this->session->get('gibbonSchoolYearID'), true);
        	$termList = '';
        	for ($i = 0; $i < count($terms); $i = $i + 2) {
            	if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
               		$termList .= $terms[($i + 1)].'<br/>';
            	}
        	}
        	echo $termList;
    	} else {
        	if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
            	if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                	echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4);
            	} else {
                	echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).'<br/>'.substr($row['programStart'], 0, 4);
            	}
        	} else {
            	echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' -<br/>'.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4);
			}
		} ?>
    </td>
    <td>
    <?php if (! empty($row['status'])) {
        echo $row['status'];
    } else {
        echo '<em>'  .$this->view->__('NA') . '</em>';
    } ?>
    </td>
</tr>
