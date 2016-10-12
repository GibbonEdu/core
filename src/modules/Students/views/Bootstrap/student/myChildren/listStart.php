<table cellspacing='0' style='width: 100%'><thead>
    <tr class='head'>
        <th>
        <?php echo Gibbon\trans::__('Name'); ?>
        </th>
        <th>
        <?php echo Gibbon\trans::__('Year Group'); ?>
        </th>
        <th>
        <?php echo Gibbon\trans::__('Roll Group'); ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style="width: 100px">
        <?php echo Gibbon\trans::__('Actions'); ?>
        </th>
        <?php } ?>
    </tr></thead>
    <tbody>
