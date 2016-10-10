<?php
$finder =  new Gibbon\core\finder($this);
$el = $finder->getFastFinder();
if ($el->output)
{

	$head = $this->__("Fast Finder: Actions");
	if ($el->classIsAccessible)
		$head .= ", " . $this->__("Classes") ;
	if ($el->studentIsAccessible)
		$head .= ", " . $this->__("Students") ;
	if ($el->staffIsAccessible)
		$head .= ", " . $this->__("Staff") ;


	$action = '/core/scripts/findRedirect.php';
	?>
    <div class="container-fluid hidden-md hidden-sm hidden-xs">
        <form role="search" method="post" id="finderForm" action="<?php echo $this->convertGetArraytoURL(array('q' => '/core/scripts/findRedirect.php')); ?>">
            <div class="right">
            	<h2><?php echo $head; ?></h2>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="" name="id" id="finderID">
                    <span class="input-group-btn">
                    	<button class="btn btn-default btn-sm" type="sunmit"><span class="glyphicon glyphicon-search"></span></button>
                    </span>
                </div>
            	<?php $this->paragraph('Total Student Enrolment: %d', array($el->studentCount));
				$x = new Gibbon\Form\action($action, null, $this);
				echo $x->renderReturn();
				$x = new Gibbon\Form\token($action, null, $this);
				echo $x->renderReturn();
				$x = new Gibbon\Form\hidden('divert', true, $this);
				echo $x->renderReturn();
				?>
            </div>
        </form> <?php
        $this->render('default.finder.list', $el); ?>
    </div>
<?php }
