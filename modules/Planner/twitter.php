<script charset="utf-8" src="http://widgets.twimg.com/j/2/widget.js"></script>
										<script type="text/javascript">
											new TWTR.Widget({
											 version: 2,
											 type: 'search',
											 search: '<?php print str_replace("^", "#", $_GET["twitter"]) ?>',
											 interval: 20000,
											 title: '',
											 subject: 'Tweets <?php print str_replace("^", "#", $_GET["twitter"]) ?>',
											 width: 'auto',
											 height: 300,
											 theme: {
												shell: {
												 background: '#8ec1da',
												 color: '#ffffff'
												},
												tweets: {
												 background: '#ffffff',
												 color: '#444444',
												 links: '#1985b5'
												}
											 },
											 features: {
												scrollbar: true,
												loop: true,
												live: true,
												behavior: 'default'
											 }
											}).render().start();
										</script>