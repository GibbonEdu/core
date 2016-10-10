		<table cellspacing='0' style='width: 100%'>
			<tr class='head'>
				<th style='width: 170px'>
					<?php echo $this->__("Logo") ; ?>
				</th>
				<th>
					<?php echo $this->__("Name") ; ?>
				</th>
				<th>
					<?php echo $this->__("Short Name") ; ?>
				</th>
				<?php if (! isset($el->action) || $el->action) { ?>
				<th style="width: 125px;">
					<?php echo $this->__("Actions") ; ?>
				</th>
				<?php } ?>
			</tr>
