<div class="flex-grow relative flex items-center">
    <input type="<?= $type ?? 'text'; ?>" <?= $attributes; ?> 
        class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0 py-2  placeholder:text-gray-400  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
        "/>

    <?php if ($type == 'url') { ?>
        <span class="pointer-events-none absolute top-0.5 right-0.5">
        <?= icon('basic', 'link', 'size-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700'); ?>
        </span>
    <?php } ?>

    <?php if (!empty($autocompleteList)) { ?>
        <datalist id="<?= $id; ?>DataList">
            <?php foreach ($autocompleteList as $listItem) { ?>
                <option value="<?= $listItem; ?>"></option>
            <?php } ?>
        </datalist>
    <?php } ?>

    <?php if (!empty($unique)) { ?>
        <script type="text/javascript">
            $("#<?= $id; ?>").gibbonUniquenessCheck(<?= $unique; ?>);
        </script>
    <?php } ?>
</div>
