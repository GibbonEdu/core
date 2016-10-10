<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__( "Name") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Usage") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Active") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Numeric") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th>
            <?php echo $this->__( "Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
