<table cellspacing='0' style='width: 100%'>
    <tr class='head'>
        <th>
        <?php echo $this->__('School Year'); ?>
        </th>
        <th>
        <?php echo $this->__('Date'); ?>
        </th>
        <th>
        <?php echo $this->__('Name'); ?>
        </th>
        <th>
        <?php echo $this->__('Description'); ?>
        </th>
    </tr>
    
    <tr>
        <td>
            <?php echo $el->schoolYear ; ?>
        </td>
        <td>
            <?php echo $el->getField('date') ; ?>
        </td>
        <td>
            <?php echo $el->getField("name") ; ?>
        </td>
        <td>
            <?php print $el->getField("description") ; ?>
        </td>
    </tr>
</table><!-- specialDays.displayMember  -->