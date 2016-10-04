<table cellspacing='0' style='width: 100%'>
	<thead>
    <tr class='head'>
        <th>
            <?php echo $this->__("Name") ; ?>
        </th>
        <th>
            <?php echo $this->__("Type") ; ?>
        </th>
        <th>
            <?php echo $this->__("Short Name") ; ?>
        </th>
        <th>
            <?php echo $this->__("Staff") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style="width: 100px">
            <?php echo $this->__("Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>

