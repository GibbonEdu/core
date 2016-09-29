<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th style='min-width: 50px'>
            <?php echo $this->__("Number") ; ?>
        </th>
        <th style='min-width: 220px'>
            <?php echo $this->__("Name") ; ?><br />
            <span style='font-size: 75%; font-style: italic'><?php echo $this->__('Short Name'); ?></span>
        </th>
        <th>
            <?php echo $this->__("Description") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style='width: 100px'>
            <?php echo $this->__("Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
