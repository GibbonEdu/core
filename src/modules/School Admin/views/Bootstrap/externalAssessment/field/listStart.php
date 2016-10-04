<table cellspacing='0' style='width: 100%' id='malcolm'>
    <thead>
        <tr class='head'>
            <th>
                <?php echo $this->__("Name") ; ?>
            </th>
            <th width="55%">
                <?php echo $this->__("Category") ; ?>
            </th>
             <th>
                <?php echo $this->__('Order') ; ?>
            </th> 
            <?php if (! isset($el->action) || $el->action) { ?>
            <th width="100px">
                <?php echo $this->__("Actions") ; ?>
            </th>
            <?php } ?>
        </tr>
    </thead>
    <tbody id="sortable">
