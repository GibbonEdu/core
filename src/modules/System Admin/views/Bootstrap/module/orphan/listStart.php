<?php		
		$this->h3("Orphaned Modules") ; 
		$this->paragraph("These modules are installed in the database, but are missing from within the file system.") ; ?>
		</p>
		
		<table cellspacing='0' style='width: 100%'>
        <thead>
			<tr class='head'>
				<th>
					<?php echo $this->__("Name") ; ?>
				</th>
				<th style='width: 150px'>
					<?php echo $this->__("Action") ; ?>
				</th>
			</tr>
        </thead>
        <tbody>
			
