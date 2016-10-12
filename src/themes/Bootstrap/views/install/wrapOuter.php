		<div id="wrapOuter" class="container">
			<div id="wrap" class="container-fluid">
            	<?php $this->render('install.header', $params); ?>
				<div id="content-wrap" class="container-fluid">
                	<?php $this->render('install.content', $params); ?>
                	<?php $this->render('install.sidebar', $params); ?>
				</div><!-- install.content-wrap -->
                <?php $this->render('install.footer', $params); ?>
			</div> 
		</div><!-- install.wrapOuter -->
