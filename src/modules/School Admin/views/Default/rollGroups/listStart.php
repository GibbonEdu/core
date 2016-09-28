<table cellspacing='0' style='width: 100%'>
	<thead>
    <tr class='head'>
        <th>
            <?php echo $this->__("Name") ; ?><br/>
            <span style='font-size: 85%; font-style: italic'><?php echo $this->__('Short Name'); ?></span>
        </th>
        <th>
            <?php echo $this->__("Form Tutors") ; ?>
        </th>
        <th>
            <?php echo $this->__("Space") ; ?>
        </th>
        <th>
            <?php echo $this->__("Website") ; ?>
        </th>
        <?php if (! isset($el->action) || $el->action) { ?>
        <th style='width: 75px'>
            <?php echo $this->__("Actions") ; ?>
        </th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
