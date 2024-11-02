<div class="flex-grow relative flex" x-data="{selectOpen: false, selectedItem: ''}"
    @keydown.escape="if(selectOpen){ selectOpen=false; }"
    >
    
    <input type="time" <?= $attributes; ?> maxlength="5" @click="selectOpen"
        class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0 py-2 font-sans placeholder:text-gray-500  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
        "/>


    <span class="pointer-events-none absolute top-0.5 right-0.5">
        <?= icon('outline', 'clock', 'pointer-events-none size-9 p-2 rounded text-gray-600 hover:text-gray-800'); ?>
    </span>

</div>
