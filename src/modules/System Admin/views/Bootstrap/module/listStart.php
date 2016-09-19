<table cellspacing='0' style='width: 100%'>
	<thead>
        <tr class='head'>
            <th>
                <?php echo Gibbon\core\trans::__( "Name") ; ?>
            </th>
            <th>
                <?php echo Gibbon\core\trans::__( "Status") ; ?>
            </th>
            <th style='width: 200px;'>
                <?php echo Gibbon\core\trans::__( "Description") ; ?>
            </th>
            <th>
                <?php echo Gibbon\core\trans::__( "Type") ; ?>
            </th>
            <th>
                <?php echo Gibbon\core\trans::__( "Active") ; ?>
            </th>
            <th>
                <?php echo Gibbon\core\trans::__( "Version") ; ?>
            </th>
            <th>
                <?php echo Gibbon\core\trans::__( "Author") ; ?>
            </th>
            <?php if ((bool) $el->action) { ?>
            <th style='width: 140px!important'>
                <?php echo Gibbon\core\trans::__( "Action") ; ?>
            <?php } ?>
            </th>
        </tr>
    </thead>
    <tbody>