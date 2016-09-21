<table cellspacing='0' style='width: 100%'>
	<thead>
        <tr class='head'>
            <th>
                <?php echo $this->__( "Name") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Status") ; ?>
            </th>
            <th style='width: 200px;'>
                <?php echo $this->__( "Description") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Type") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Active") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Version") ; ?>
            </th>
            <th>
                <?php echo $this->__( "Author") ; ?>
            </th>
            <?php if ((bool) $el->action) { ?>
            <th style='width: 140px!important'>
                <?php echo $this->__( "Action") ; ?>
            <?php } ?>
            </th>
        </tr>
    </thead>
    <tbody>