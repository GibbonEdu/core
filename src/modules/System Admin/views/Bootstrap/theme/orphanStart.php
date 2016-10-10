<?php
use Gibbon\core\trans ;
?>
<h2 style='margin-top: 40px'>
    <?php echo $this->__( "Orphaned Themes") ; ?>
</h2>
<p>
    <?php echo $this->__( "These themes are installed in the database, but are missing from within the file system.") ; ?>
</p>


<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__( "Name") ; ?>
        </th>
        <th style='width: 50px'>
            <?php echo $this->__( "Action") ; ?>
        </th>
    </tr>
    
