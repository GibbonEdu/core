<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
            <?php echo $this->__("Sequence Number") ; ?>
        </th>
        <th>
            <?php echo $this->__("Name") ; ?>
        </th>
        <th>
            <?php echo $this->__("Short Name") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style="width: 125px;">
            <?php echo $this->__("Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
