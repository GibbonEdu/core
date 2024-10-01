
<?php if ($type == 'button') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> rounded-md border border-gray-500 px-4 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-100"><?= $value; ?></button>

<?php } elseif ($type == 'submit') { ?>

    <input type="<?= $type; ?>" <?= $attributes; ?> class="<?= $class; ?> rounded-md px-8 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-800 hover:bg-gray-900" />

<?php } else { ?>

    <input type="<?= $type; ?>" <?= $attributes; ?> class="<?= $class; ?> rounded-md border border-gray-500 px-8 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-100"/>

<?php } ?>
