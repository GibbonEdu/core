		<table cellspacing='0' style='width: 100%'>
			<tr class='head'>
				<th>
					<?php echo $this->__("School Year") ; ?>
				</th>
				<th>
					<?php echo $this->__("Sequence") ; ?>
				</th>
				<th>
					<?php echo $this->__("Name") ; ?>
				</th>
				<th>
					<?php echo $this->__("Short Name") ; ?>
				</th>
				<th>
					<?php echo $this->__("Dates") ; ?>
				</th>
				<?php if (! isset($el->action) || $el->action) { ?>
				<th>
					<?php echo $this->__("Actions") ; ?>
				</th>
				<?php } ?>
			</tr>


			
