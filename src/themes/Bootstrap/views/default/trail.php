			<div class='trail'>
				<div class='trailHead'>
            	<?php foreach ($params->trailHead as $prompt=>$link) { ?>
                	<a href="<?php echo $this->session->get("absoluteURL").$link; ?>"><?php echo Gibbon\core\trans::__($prompt); ?></a>&nbsp;>
				<?php } ?>
				</div>
				<div class='trailEnd'><?php echo Gibbon\core\trans::__($params->trailEnd); ?></div>
			</div>
