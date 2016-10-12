<table cellspacing='0' style='width: 100%'><thead>
    <tr class='head'>
        <th>
        <?php echo Gibbon\trans::__('Name'); ?>
        </th>
        <th>
        <?php echo Gibbon\trans::__('Risk'); ?>
        </th>
        <th>
        <?php echo Gibbon\trans::__('Details'); ?>
        </th>
        <th>
        <?php echo Gibbon\trans::__('Medication'); ?>
        </th>
        <th>
        <?php echo Gibbon\trans::__('Comment'); ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style="width: 100px">
        <?php echo Gibbon\trans::__('Actions'); ?>
        </th>
        <?php } ?>
    </tr></thead>
    <tbody>
