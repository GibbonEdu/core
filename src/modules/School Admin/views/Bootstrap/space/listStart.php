<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__("Name") ; ?>
        </th>
        <th>
            <?php echo $this->__("Type") ; ?>
        </th>
        <th>
            <?php echo $this->__("Staff") ; ?>
        </th>
        <th>
            <?php echo $this->__("Capacity") ; ?>
        </th>
        <th>
            <?php echo $this->__("Facilities") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style='max-width: 75px'>
            <?php echo $this->__("Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
    
    