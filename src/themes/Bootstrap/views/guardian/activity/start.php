<table cellspacing='0' class='table-striped' style='width: 100%;'>
    <thead>
        <tr class='head'>
            <th>
            	<?php echo $this->view->__('Activity'); ?>
            </th>
			<?php $options = $this->config->getSettingByScope('Activities', 'activityTypes');
            if (! empty($options)) { ?>
                <th>
					<?php echo $this->__('Type'); ?>
                </th><?php
            } ?>
            <th>
            	<?php $dateType = $this->config->getSettingByScope('Activities', 'dateType');
				if ($dateType != 'Date') {
					echo $this->__('Term');
				} else {
					echo  $this->__('Dates');
				} ?>
            </th>
            <th>
            <?php echo $this->__('Status');?>
            </th>
        </tr>
    </thead>
    <tbody>
