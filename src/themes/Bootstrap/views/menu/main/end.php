<?php
$finder =  new Gibbon\core\finder($this);
$params = $finder->getFastFinder();

if ($this->getSecurity()->getRoleCategory($this->session->get("gibbonRoleIDCurrent")) == "Staff") 
	$search = $this->__('%d Student Count', array($params->studentCount));
else
	$search = 'Search';
if ($el->count > 0) { ?>
                        </ul>
                    </li>
            
            <?php
        } ?>
				</ul>
<?php
if ($params->output) 
{ ?>
                <div class="col-sm-3 col-md-3 pull-right">
                    <form class="navbar-form" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control topFinder" placeholder="<?php echo $search; ?>" name="finderID" id="finderID">
                            <div class="input-group-btn">
                                <button class="btn btn-default btn-sm" type="submit"><span class="glyphicon glyphicon-search"></span></button>
                            </div>
                        </div>
                    </form> <?php
					$this->render('default.finder.list', $params); ?>
                </div>
<?php } ?>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</div><!-- menu.main.end -->
