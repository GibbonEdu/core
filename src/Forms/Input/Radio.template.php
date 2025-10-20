<div class="flex-grow relative gap-2 <?= $inline ? 'inline-flex justify-between items-center ' : 'flex flex-col justify-center ' . ($align == 'left' ? 'items-start' : 'items-end'); ?>" >
    
<?php foreach ($options as $radioValue => $radioLabel) { ?>

    <?php $itemId = !empty($id) ?: $name.$count; ?>
    <?php $checked = !empty($value) && $radioValue === $value; ?>

    <?php if ($inline) { ?>
        <input type="radio" value="<?= $radioValue; ?>" id="<?= $itemId; ?>" <?= $attributes; ?> <?= $checked ? 'checked' : ''; ?> >
        <label for="<?= $itemId; ?>" class="flex-1 text-left font-medium"><?= $radioLabel; ?></label>
    <?php } elseif ($align == 'right') { ?>
        
        <div class="flex gap-2 items-center text-right">
        <label class="leading-compact flex-1 font-medium" for="<?= $itemId; ?>"><?= $radioLabel; ?></label>
        <input type="radio" value="<?= $radioValue; ?>" id="<?= $itemId; ?>" <?= $attributes; ?> <?= $checked ? 'checked' : ''; ?> >
        </div>
    <?php } else { ?>
        <div class="flex gap-2 items-center text-left">
        <input type="radio" value="<?= $radioValue; ?>" id="<?= $itemId; ?>" <?= $attributes; ?> <?= $checked ? 'checked' : ''; ?> >
        <label class="leading-compact flex-1 font-medium" for="<?= $itemId; ?>"><?= $radioLabel; ?></label>
        </div>
    <?php } ?>

    <?php $count++; ?>

<?php } ?>

</div>
