            <div id="content-wrap">
                <?php
                //Allow for wide pages (no sidebar)
                if ( $this->session->get('sidebar') == "false" ) {
					$this->render('home.contentWide');
                }
				$this->render('home.content'); ?>
                </div>
                <?php $this->render('home.sideBar'); ?>
            </div>
