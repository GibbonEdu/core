<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__( "Name") ; ?>
        </th>
        <th style="width: 100px">
            <?php echo $this->__( "Active") ; ?>
        </th>
        <th>
            <?php echo $this->__( "Template") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style="width: 100px">
            <?php echo $this->__( "Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
