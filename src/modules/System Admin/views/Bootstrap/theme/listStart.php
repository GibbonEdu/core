<?php
use Gibbon\core\trans ;
?>
<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo trans::__( "Name") ; ?>
        </th>
        <th>
            <?php echo trans::__( "Status") ; ?>
        </th>
        <th>
            <?php echo trans::__( "Description") ; ?>
        </th>
        <th>
            <?php echo trans::__( "Version") ; ?>
        </th>
        <th>
            <?php echo trans::__( "Author") ; ?>
        </th>
        <th>
            <?php echo trans::__( "Active") ; ?>
        </th>
        <?php if ($params->action) { ?>
        <th style='width: 50px'>
            <?php echo trans::__( "Action") ; ?>
        </th>
        <?php } ?>
    </tr>
