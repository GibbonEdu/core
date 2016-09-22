<<<<<<< HEAD
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
=======
>>>>>>> 9f852d0fb1c6b3799f833bd1593409785cc98f71
				</ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</div><!-- menu.main.end -->
