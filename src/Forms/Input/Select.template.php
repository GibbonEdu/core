<?php 
    if ($group == 'left') $groupClass = 'rounded-l-md -mr-px';
    elseif ($group == 'right') $groupClass = 'rounded-r-md -ml-px';
    elseif ($group == 'middle') $groupClass = '';
    else $groupClass = 'rounded-md';
?>

<select <?= $attributes; ?> 
    class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0 border py-2 text-gray-900  placeholder:text-gray-400 
    focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-6" >

    <?php if (isset($placeholder) && empty($multiple)) { ?>
        <option value="<?= $placeholder; ?>"><?= __($placeholder); ?></option>
    <?php } ?>

    <?php foreach ($options as $optLabel => $optGroup) { ?>

        <?php if (!empty($optLabel)) { ?>
           <optgroup label="— <?= $optLabel; ?> —">
        <?php } ?>

        <?php foreach ($optGroup as $value => $option) { ?>
            <option value="<?= $value; ?>" class="<?= $option['class']; ?>" <?= $option['selected']; ?>><?= $option['label']; ?></option>
        <?php } ?>

        <?php if (!empty($optLabel)) { ?>
           </optgroup>
        <?php } ?>

    <?php } ?>

</select>

<?php if (!empty($chainedToID)) { ?>
    <script type="text/javascript">
        $(function() {$("#<?= $id; ?>").chainedTo("#<?= $chainedToID; ?>");});
    </script>
<?php } ?>