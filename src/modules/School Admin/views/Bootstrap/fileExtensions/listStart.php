<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__("Extension") ; ?>
        </th>
        <th>
            <?php echo $this->__("Name") ; ?>
        </th>
        <th>
            <?php echo $this->__("Type") ; ?>
        </th>
        <?php if ((bool) $el->action) { ?>
        <th>
            <?php echo $this->__("Actions") ; ?>
        </th>
        <?php } ?>
    </tr>