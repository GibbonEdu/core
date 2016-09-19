<?php 
use Gibbon\core\trans ;
new Gibbon\Form\startForm(array('q'=>'/modules/System Admin/i18n_manageProcess.php'), $this);
?>  
<table cellspacing='0' style='width: 100%'><!-- System Admin.Default.i18n.listStart -->
    <thead>
        <tr class='head'>
            <th>
            <?php echo trans::__('Name'); ?>
            </th>
            <th>
            <?php echo trans::__('Code'); ?>
            </th>
            <th>
            <?php echo trans::__('Active'); ?>
            </th>
            <th>
            <?php echo trans::__('Version'); ?>
            </th>
            <th>
            <?php echo trans::__('Update to'); ?>
            </th>
            <th>
            <?php echo trans::__('Maintainer'); ?>
            </th>
            <th>
            <?php echo trans::__('Default'); ?>
            </th>
        </tr>
    </thead>
    <tbody>