<div class="flex-grow relative flex" x-data="{selectOpen: false, selectedItem: ''}"
    @keydown.escape="if(selectOpen){ selectOpen=false; }"
    >
    
    <input type="time" <?= $attributes; ?> maxlength="5" @click="selectOpen"
        class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0 py-2 font-sans placeholder:text-gray-400  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
        "/>


    <span class="pointer-events-none absolute top-0.5 right-0.5">
        <svg class="w-9 h-9 p-2 rounded text-gray-700 hover:text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    </span>

</div>
