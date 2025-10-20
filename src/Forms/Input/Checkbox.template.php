<div class="<?= !empty($checkall) ? 'checkboxGroup' : ''; ?> flex-grow relative gap-2 <?= $inline ? 'flex justify-between items-center ' : 'flex flex-col justify-center ' . ($align == 'left' ? 'items-start' : 'items-end w-full'); ?>" >
   
<?php if (!empty($checkall)) { ?>
<div class="flex mt-1 <?= $align == 'right' ? 'justify-end text-right' : '' ?>">
    <?php $checked = $totalOptions == $checkedOptions; ?>
    <label for="checkall<?= $identifier; ?>" class="mr-2 text-xs text-gray-600"><?= $checkall; ?></label> 
    <input id="checkall<?= $identifier; ?>" class="checkall h-4 w-4 rounded text-gray-500 focus:ring-gray-600" type="checkbox" <?= $checked; ?> >
</div>
<?php } ?>

<?php foreach ($options as $group => $optionList) { ?>

    <?php if (!empty($group)) { ?>
        <fieldset class="w-full gap-2 <?= $inline ? 'inline-flex justify-between items-center ' : 'flex flex-col justify-center ' . ($align == 'left' ? 'items-start' : 'items-end'); ?>" >
        <legend class="w-full font-medium text-sm/6 text-gray-700 border-b mb-3"><?= $group ?></legend>
    <?php } ?>

    <?php foreach ($optionList as $checkboxValue => $checkbox) { ?>

        <?php $itemId = $hasMultiple ? $identifier.$count : $identifier; ?>
        <?php $itemClass = 'h-4 w-4 rounded text-blue-500 focus:ring-blue-600 ' . $class; ?>
        
        <?php if ($inline) { ?>
            <input type="checkbox" name="<?= $name; ?>" id="<?= $itemId; ?>" class="<?= $itemClass; ?>" value="<?= $checkboxValue; ?>" <?= $checkbox['checked'] ?> <?= $checkbox['disabled'] ?> <?= $attributes; ?> >
            <label for="<?= $itemId; ?>" class="flex-1 text-left text-sm <?= $labelClass; ?>"><?= $checkbox['label']; ?></label>

        <?php } elseif ($align == 'right') { ?>
            
            <div class="w-full inline-flex gap-2 justify-center items-center text-right">
            <label class="leading-compact flex-1 text-sm <?= $labelClass; ?>" for="<?= $itemId; ?>"><?= $checkbox['label']; ?></label>
            <input type="checkbox" name="<?= $name; ?>" id="<?= $itemId; ?>" class="<?= $itemClass; ?>" value="<?= $checkboxValue; ?>" <?= $checkbox['checked'] ?> <?= $checkbox['disabled'] ?> <?= $attributes; ?> >
            </div>
        <?php } else { ?>
            <div class="w-full inline-flex gap-2 justify-center items-center text-left">
            <input type="checkbox" name="<?= $name; ?>" id="<?= $itemId; ?>" class="<?= $itemClass; ?>" value="<?= $checkboxValue; ?>" <?= $checkbox['checked'] ?> <?= $checkbox['disabled'] ?> <?= $attributes; ?> >
            <label class="leading-compact flex-1 text-sm <?= $labelClass; ?>" for="<?= $itemId; ?>"><?= $checkbox['label']; ?></label>
            </div>
        <?php } ?>

        <?php $count++; ?>

    <?php } ?>

    <?php if (!empty($group)) { ?>
    </fieldset>
    <?php } ?>

<?php } ?>

</fieldset>

</div>
