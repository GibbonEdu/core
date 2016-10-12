<?php	
use Gibbon\trans ;
$this->startWell();
$this->h3('Brief Details');

?>			
<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Year Group'); ?></span><br/>
            <?php echo trans::__($el->student->getYearGroup('name')); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Roll Group'); ?></span><br/>
            <?php echo trans::__($el->student->getRollGroup('name')); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('House'); ?></span><br/>
            <?php echo trans::__($el->student->getDetailsOfPerson('House', 'name')); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Email'); ?></span><br/>
            <?php if (! empty($el->student->getField('email'))) { ?>
                <em><a href='mailto:<?php echo $el->student->getField('email'); ?>'><?php echo $el->student->getField('email'); ?></a></em>
            <?php } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Website'); ?></span><br/>
            <?php if (! empty($el->student->getField('website'))) { ?>
                <em><a href='<?php echo $el->student->getField('website'); ?>'><?php echo $el->student->getField('website'); ?></a></em>
            <?php } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Student ID'); ?></span><br/>
            <?php if (! empty($el->student->getField('studentID'))) { ?>
                <em><?php echo $el->student->getField('studentID'); ?></a></em>
            <?php } ?>
        </td>
    </tr>
</table><?php
$this->endWell();
