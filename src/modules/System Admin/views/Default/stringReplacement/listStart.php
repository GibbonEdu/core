<?php
use Gibbon\core\trans ;
?>
<table cellspacing='0' style='width: 100%'>
    <thead>
        <tr class='head'>
            <th>
                <?php echo trans::__( "Original String"); ?>
            </th>
            <th>
                <?php echo trans::__( "Replacement String") ; ?>
            </th>
            <th>
                <?php echo trans::__( "Mode") ; ?>
            </th>
            <th>
                <?php echo trans::__( "Case Sensitive") ; ?>
            </th>
            <th>
                <?php echo trans::__( "Priority") ; ?>
            </th>
            <?php if (isset($params->action) && (bool) $params->action) { ?>
            <th>
                <?php echo trans::__( "Actions") ; ?>
            </th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>