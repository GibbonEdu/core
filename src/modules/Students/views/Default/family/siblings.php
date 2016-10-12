<?php if (count($el) > 0) {
	$this->startWell();
	$this->h4('Siblings'); ?>
    <table class='smallIntBorder' cellspacing='0' style='width:100%'><?php
        $count = 0;
        $columns = 3;
        
        foreach($el as $sibling) {
            if ($count % $columns == 0) { ?>
                <tr> <?php
            } ?>
            <td style='width:30%; text-align: left; vertical-align: top'> <?php
            //User photo
                echo Gibbon\helper::getUserPhoto($sibling->getField('image_240'), 75); ?>
                <div style='padding-top: 5px'><strong> <?php
                    if ($sibling->getField('status') == 'Full') { ?>
                        <a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=<?php echo $sibling->getField('gibbonPersonID'); ?>'><?php echo $sibling->getPerson()->formatName(); ?></a><br/><?php
                    } else {
                        echo $sibling->getPerson()->formatName(); ?><br/> <?php
                    } ?>
                    <span style='font-weight: normal; font-style: italic'><?php echo Gibbon\trans::__('Status').': '.$sibling->getField('status'); ?></span>
                </strong></div>
            </td>
        
            <?php if ($count % $columns == ($columns - 1)) { ?>
                </tr><?php
            }
        ++$count;
        }
        
        for ($i = 0; $i < $columns - ($count % $columns); ++$i) { ?>
        <td></td> <?php
        }
        
        if ($count % $columns != 0) { ?></tr> <?php
        } ?>
    </table><?php
	$this->endWell();
}
