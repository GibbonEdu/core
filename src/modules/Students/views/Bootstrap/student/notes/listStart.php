<table cellspacing='0' style='width: 100%'><thead>
    <tr class='head'>
        <th>
			<?php echo Gibbon\trans::__('Date'); ?><br/>
            <span style='font-size: 75%; font-style: italic'> <?php echo Gibbon\trans::__('Time'); ?></span>
        </th>
        <th>
			<?php echo Gibbon\trans::__('Category'); ?>
        </th>
        <th>
			<?php echo Gibbon\trans::__('Title'); ?><br/>
            <span style='font-size: 75%; font-style: italic'> <?php echo Gibbon\trans::__('Overview'); ?></span>
        </th>
        <th>
			<?php echo Gibbon\trans::__('Note Taker'); ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
            <th style="width: 100px">
            	<?php echo Gibbon\trans::__('Actions'); ?>
            </th>
        <?php } ?>
    </tr></thead>
    <tbody>
