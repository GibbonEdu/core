<?php 
$this->startWell(); 
$this->h4(array('Adult %1$s', array($el->getField('gibbonPersonID')))); ?>
<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; vertical-align: top' rowspan='2'>
        	<?php echo Gibbon\helper::getUserPhoto($el->getField('image_240'), 75); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Name'); ?></span><br/>
            <?php echo $el->getPerson()->formatName(); ?><br/>
            <div style='font-size: 85%; font-style: italic'>
            	<?php echo $el->relationship; ?>
            </div>
        </td>
        <td style='width: 34%; vertical-align: top' colspan='2'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Contact Priority'); ?></span><br/>
            <?php echo $el->getField('contactPriority'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('First Language'); ?></span><br/>
            <?php echo $el->getField('languageFirst'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Second Language'); ?></span><br/>
            <?php echo $el->getField('languageSecond'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Contact By Phone'); ?></span><br/><?php
            if ($el->getField('contactCall') == 'N') {
                echo Gibbon\trans::__('Do not contact by phone');
            } elseif ($el->getField('contactCall') == 'Y' and (! empty($el->getField('phone1')) || ! empty($el->getField('phone2')) || ! empty($el->getField('phone3')) || ! empty($el->getField('phone4')))) {
                for ($i = 1; $i < 5; ++$i) {
                    if (! empty($el->getField('phone'.$i))) {
                        if (! empty($el->getField('phone'.$i.'Type'))) {
                            echo '<em>'.$el->getField('phone'.$i.'Type').'</em>'; 
                        }
                        if (! empty($el->getField('phone'.$i.'CountryCode'))) {
                            echo '+'.$el->getField('phone'.$i.'CountryCode'); 
                        }
                        echo Gibbon\helper::formatPhone($el->getField('phone'.$i)); ?><br/><?php 
                    }
                }
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Contact By SMS'); ?></span><br/><?php 
            if ($el->getField('contactSMS') == 'N') {
                echo Gibbon\trans::__('Do not contact by SMS');
            } elseif ($el->getField('contactCall') == 'Y' and (! empty($el->getField('phone1')) || ! empty($el->getField('phone2')) || ! empty($el->getField('phone3')) || ! empty($el->getField('phone4')))) {
                for ($i = 1; $i < 5; ++$i) {
                    if (! empty($el->getField('phone'.$i))) {
                        if (! empty($el->getField('phone'.$i.'Type'))) {
                            echo '<em>'.$el->getField('phone'.$i.'Type').'</em>'; 
                        }
                        if (! empty($el->getField('phone'.$i.'CountryCode'))) {
                            echo '+'.$el->getField('phone'.$i.'CountryCode'); 
                        }
                        echo Gibbon\helper::formatPhone($el->getField('phone'.$i)); ?><br/><?php 
                    }
                }
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan='2'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Contact By Email'); ?></span><br/><?php
            if ($el->getField('contactEmail') == 'N') {
                echo Gibbon\trans::__('Do not contact by email');
            } elseif ($el->getField('contactEmail') == 'Y' && (! empty($el->getField('email')) || ! empty($el->getField('emailAlternate')))) {
            if (! empty($el->getField('email'))) {
                echo Gibbon\trans::__('Email') . ": <a href='mailto:".$el->getField('email')."'><".$el->getField('email')."</a><br/>";
            }
            if (! empty($el->getField('emailAlternate'))) {
                echo Gibbon\trans::__('Email')." 2: <a href='mailto:".$el->getField('emailAlternate')."'>".$el->getField('emailAlternate').'</a><br/>';
            } ?>
            <br/> <?php
            } ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Profession'); ?></span><br/>
            <?php echo $el->getField('profession'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Employer'); ?></span><br/>
            <?php echo $el->getField('employer'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Job Title'); ?></span><br/>
            <?php echo $el->getField('jobTitle'); ?>
        </td>
    </tr>
    
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Vehicle Registration'); ?></span><br/>
            <?php echo $el->getField('vehicleRegistration'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        </td>
    </tr>
    
    <?php if (! empty($el->getField('comment'))) { ?>
    <tr>
        <td style='width: 33%; vertical-align: top' colspan='3'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Comment'); ?></span><br/>
            <?php echo $el->getField('comment'); ?>
        </td>
    </tr> <?php
    } ?>
</table>
<?php $this->endWell(); ?>
