<!-- Print family information -->
<?php $this->startWell(); 
$this->h3('Basic Family Information');
?>
<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Family Name'); ?></span><br/>
            <?php echo $el->getField('name'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Family Status'); ?></span><br/>
            <?php echo $el->getField('status'); ?>
        </td>
        <td style='width: 34%; vertical-align: top' colspan=2>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Home Languages'); ?></span><br/>
            <?php if (! empty($el->getField('languageHomePrimary'))) {
                echo $el->getField('languageHomePrimary'); ?><br/><?php
            }
            if (! empty($el->getField('languageHomeSecondary'))) {
                echo $el->getField('languageHomeSecondary'); ?><br/><?php
            } ?>
        </td>
    </tr>

    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Address Name'); ?></span><br/>
            <?php echo $el->getField('nameAddress'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        </td>
    </tr>
    
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Home Address'); ?></span><br/>
            <?php echo $el->getField('homeAddress'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Home Address (District)'); ?></span><br/>
            <?php echo $el->getField('homeAddressDistrict'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Home Address (Country)'); ?></span><br/>
            <?php echo $el->getField('homeAddressCountry'); ?>
        </td>
    </tr>
</table>
<?php $this->endWell(); ?>
