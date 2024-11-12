<?php $groupClass .= !empty($groupAlign) ? ' text-sm/6' : ' text-sm'; ?>

<?php 
if ($disabled) {
    $bgClass = ' bg-gray-100 text-gray-600';
} else if ($color == 'gray') {
    $bgClass = ' bg-gray-100 hover:bg-gray-200 text-gray-800';
} else if ($color == 'red') {
    $bgClass = ' border-red-700 bg-red-700 hover:bg-red-900 hover:border-red-900 text-white';
} else if ($color == 'purple') {
    $bgClass = ' border-purple-600 bg-purple-600 hover:bg-purple-800 hover:border-purple-800 text-white';
} else if ($type == 'submit') {
    $bgClass = ' border-gray-800 bg-gray-800 hover:bg-gray-900 text-white';
} else {
    $bgClass = ' bg-gray-100 hover:bg-gray-200 text-gray-800';
}
?>

<?php if ($type == 'blank') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> ">
    
        <?php $svgClass = 'text-gray-600 block m-0.5 size-5 '.($iconClass ?? ''); ?>

        <?= !empty($icon) ? icon($iconLibrary ?? 'solid', $icon, $svgClass ) : ''; ?>
        
        <?= $value; ?>

    </button>

<?php } elseif ($type == 'submit') { ?>
    <button type="submit" <?= $attributes; ?> x-data="{ submitDisabled: false }" x-bind:disabled="submitDisabled" x-on:submit="submitDisabled = true" @click="submitting = true" :class="{'submitted bg-gray-100': submitting, '<?= $bgClass; ?>' : !submitting}" class="<?= $class; ?> <?= $groupClass; ?> items-center px-8 py-2 font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 border <?= $bgClass; ?>" />
    <span :class="{'opacity-0': submitting}"><?= $value; ?></span>
    </button>
<?php } elseif ($type == 'quickSubmit') { ?>

    <button type="submit" <?= $attributes; ?>  @click="submitting = true" :class="{'submitted': submitting}" class="<?= $class; ?> <?= $groupClass; ?> <?= $bgClass; ?> items-center px-4 py-2 font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 border border-gray-400" >
    <span :class="{'opacity-0': submitting}"><?= $value; ?></span>
    </button>
<?php } elseif ($type == 'input') { ?>

    <input type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> <?= $bgClass; ?>  items-center border border-gray-400 px-8 py-2 font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"/>

<?php } elseif ($type == 'button') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> <?= $bgClass; ?> flex items-center border border-gray-400 px-4 py-2 font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">

    <?php $svgClass = 'text-gray-600 block m-0.5 size-5 '.(!empty($value) ? 'lg:-ml-0.5 lg:mr-1.5 ' : '').($iconClass ?? ''); ?>

    <?= !empty($icon) ? icon($iconLibrary ?? 'solid', $icon, $svgClass ) : ''; ?>

    <?= $value; ?>

    </button>
<?php } ?>
