<?php 
    if ($group == 'left') $groupClass = 'rounded-l-md -mr-px';
    elseif ($group == 'right') $groupClass = 'rounded-r-md -ml-px';
    elseif ($group == 'middle') $groupClass = 'rounded-none';
    else $groupClass = 'rounded-md';
?>
<div class="flex-grow relative flex">
    <input type="<?= $type ?? 'text'; ?>" <?= $attributes; ?> 
        class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0 py-2  placeholder:text-gray-400  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
        "/>

    <?php if ($type == 'url') { ?>
        <span class="pointer-events-none absolute top-0.5 right-0.5">
            <svg class="w-9 h-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
            <path fill-rule="evenodd" d="M8.914 6.025a.75.75 0 0 1 1.06 0 3.5 3.5 0 0 1 0 4.95l-2 2a3.5 3.5 0 0 1-5.396-4.402.75.75 0 0 1 1.251.827 2 2 0 0 0 3.085 2.514l2-2a2 2 0 0 0 0-2.828.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M7.086 9.975a.75.75 0 0 1-1.06 0 3.5 3.5 0 0 1 0-4.95l2-2a3.5 3.5 0 0 1 5.396 4.402.75.75 0 0 1-1.251-.827 2 2 0 0 0-3.085-2.514l-2 2a2 2 0 0 0 0 2.828.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
            </svg>
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
