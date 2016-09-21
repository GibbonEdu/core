<?php
use Gibbon\core\trans ;
?>
<table cellspacing='0' style='width: 100%'>
    <thead>
        <tr class='head'>
            <th>
                <?php echo $this->__( "Original String"); ?>
            </th>
            <th>
                <?php echo $this->__( "Replacement String") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Mode") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Case Sensitive") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Priority") ; ?>
            </th>
            <?php if (isset($params->action) && (bool) $params->action) { ?>
            <th>
                <?php echo $this->__( "Actions") ; ?>
            </th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>