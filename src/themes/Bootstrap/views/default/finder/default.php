<?php
use Gibbon\core\trans ;
?>
<div id="header-finder">
	<?php if ($params->output) { ?>
	<div id='fastFinder'>
		<style>
            ul.token-input-list-facebook { width: 275px; float: left; height: 25px!important; }
            div.token-input-dropdown-facebook { width: 275px; z-index: 99999999 }
        </style>
        <div style="padding-bottom: 7px; height: 40px; margin-top: 0px">
            <form method='post' action='<?php echo $this->session->get("absoluteURL") ?>/index.php'>
                <table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px; opacity: 0.8'>
                    <tr>
                        <td style='vertical-align: top; padding: 0px' colspan=2>
                            <h2 style='padding-bottom: 0px'>
								<?php 
								echo trans::__("Fast Finder: Actions");
								if ($params->classIsAccessible)
									echo ", " . trans::__("Classes") ;
								if ($params->studentIsAccessible)
									", " . trans::__("Students") ;
								if ($params->staffIsAccessible)
									", " . trans::__("Staff") ; 
								?>
								<br/>
                    <?php
					new \Gibbon\Form\action(GIBBON_ROOT . 'plugins/findRedirect.php', null,  $this);
					new \Gibbon\Form\hidden('divert', true, $this);
					?>
							</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style='vertical-align: top; border: none'>
                            <input class='topFinder' style='width: 275px' type='text' id='id' name='id' />
                    		<?php $this->render('default.finder.list', $params); ?>
                            <script type='text/javascript'>
                                var id=new LiveValidation('id');
                                id.add(Validate.Presence);
                             </script>
                        </td>
                        <td class='right' style='vertical-align: top; border: none'>
                            <input style='height: 27px; width: 60px!important; margin-top: 0px;' type='submit' value='<?php echo trans::__( 'Go') ?>'>
                        </td>
                    </tr>
                    <?php if ($this->getSecurity()->getRoleCategory($this->session->get("gibbonRoleIDCurrent"))=="Staff") { ?>
                        <tr>
                            <td style='vertical-align: top' colspan='2'>
                                <div style="padding-bottom: 0px; font-size: 80%; font-weight: normal; font-style: italic; line-height: 80%; padding: 1em,1em,1em,1em; width: 99%; text-align: left; color: #888;">
                                    <?php echo trans::__('Total Student Enrolment: %d', array($params->studentCount)) ; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </form>
        </div>
    </div>
    <?php } ?>
</div>
