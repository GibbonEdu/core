<table cellspacing='0' style='width: 100%'>
    <thead>
        <tr class='head'>
            <th>
                <?php echo $this->__("Name") ; ?>
            </th>
            <th>
                <?php echo $this->__("Description") ; ?>
            </th>
            <th>
                <?php echo $this->__("Active") ; ?>
            </th>
            <?php if (! isset($el->action) || $el->action) { ?>
            <th>
                <?php echo $this->__("Actions") ; ?>
            </th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
