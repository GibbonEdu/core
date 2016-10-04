<?php
use Gibbon\core\trans ;
?>
<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__( "Name") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Status") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Description") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Version") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Author") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Active") ; ?>
        </th>
        <?php if ($params->action) { ?>
        <th style='width: 50px'>
            <?php echo $this->__( "Action") ; ?>
        </th>
        <?php } ?>
    </tr>
