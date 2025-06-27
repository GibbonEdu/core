<div class="flex-grow relative flex" >
    
    <input type="text" <?= $attributes; ?>  x-init=""
        class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0 pl-6 pr-12 font-sans placeholder:text-gray-500  sm:text-sm sm:leading-6 
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
        "/>

    <span class="pointer-events-none absolute top-2 left-2 font-sans text-base font-normal text-gray-500">
        <?= $currencySymbol; ?>
    </span>

    <span class="pointer-events-none absolute top-2.5 right-2 font-sans text-sm font-normal text-gray-500">
        <?= $currencyName; ?>
    </span>

</div>
