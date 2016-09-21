<ul id='nav'>
    <li class='active'>
 	   <a href='<?php echo GIBBON_URL; ?>index.php'><?php echo $this->__('Home')?></a>
    </li>
<?php echo isset($params->doNotClose) && $params->doNotClose ? '': '</ul>'; 
