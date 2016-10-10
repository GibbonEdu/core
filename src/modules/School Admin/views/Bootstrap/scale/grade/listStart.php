<table cellspacing='0' style='width: 100%' id='malcolm'>
    <thead>
        <tr class='head'>
            <th>
                <?php echo $this->__("Value") ; ?>
            </th>
            <th>
                <?php echo $this->__("Descriptor") ; ?>
            </th>
            <th>
                <?php echo $this->__("Sequence Number") ; ?>
            </th>
            <th>
                <?php echo $this->__("Is Default?") ; ?>
            </th>
            <?php if (! isset($el->action) || $el->action) { ?>
                <th>
                    <?php echo $this->__("Actions") ; ?>
                </th>
            <?php } ?>
        </tr>
    </thead>
    <tbody id='sortable'>
