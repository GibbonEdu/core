<?php $groupClass .= !empty($groupAlign) ? ' text-sm/6' : ' text-sm'; ?>

<?php if ($type == 'blank') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> "><?= $value; ?></button>

<?php } elseif ($type == 'submit') { ?>
    <button type="submit" <?= $attributes; ?> x-data="{ submitDisabled: false }" x-bind:disabled="submitDisabled" x-on:submit="submitDisabled = true" @click="submitting = true" :class="{'submitted bg-gray-100': submitting, 'border-gray-800 bg-gray-800 hover:bg-gray-900' : !submitting}" class="<?= $class; ?> <?= $groupClass; ?> items-center px-8 py-2 font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 border border-gray-800 bg-gray-800 hover:bg-gray-900" />
    <span :class="{'opacity-0': submitting}"><?= $value; ?></span>
    </button>
<?php } elseif ($type == 'quickSubmit') { ?>

    <button type="submit" <?= $attributes; ?>  @click="submitting = true" :class="{'submitted': submitting}" class="<?= $class; ?> <?= $groupClass; ?> items-center px-4 py-2 font-semibold text-gray-800 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 border border-gray-400 bg-gray-100 hover:bg-gray-200" >
    <span :class="{'opacity-0': submitting}"><?= $value; ?></span>
    </button>
<?php } elseif ($type == 'input') { ?>

    <input type="<?= $type; ?>" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> items-center border border-gray-400 px-8 py-2 font-semibold shadow-sm text-gray-800 hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-100"/>

<?php } elseif ($type == 'button') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> flex items-center border border-gray-400 px-4 py-2 font-semibold shadow-sm text-gray-800 hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-100">

    <?php $svgClass = 'text-gray-600 block m-0.5 h-5 w-5'.(!empty($value) ? 'lg:-ml-0.5 lg:mr-1.5' : ''); ?>

    <?= !empty($icon) ? icon('solid', $icon, $svgClass) : ''; ?>

    <?= $value; ?>

    </button>
<?php } ?>
