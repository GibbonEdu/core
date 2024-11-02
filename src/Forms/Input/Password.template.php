
<?php 
    if ($group == 'left') $groupClass = 'rounded-l-md -mr-px';
    elseif ($group == 'right') $groupClass = 'rounded-r-md -ml-px';
    elseif ($group == 'middle') $groupClass = 'rounded-none';
    else $groupClass = 'rounded-md';
?>

<div x-data="{ show: true }" class="flex-grow relative flex">
<input :type="show ? 'password' : 'text'" <?= $attributes; ?> autocomplete="off" 
    class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0  py-2 placeholder:text-gray-500  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
    
    <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed :ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>"
    />

    <span class="absolute top-0.5 right-0.5">

        <button type="button" @click="show = !show" :class="{'hidden': !show, 'block':show }">
        <?= icon('basic', 'eye', 'pointer-events-none size-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700'); ?>
        </button>

        <button type="button" @click="show = !show" :class="{'block': !show, 'hidden':show }">
        <?= icon('basic', 'eye-slash', 'pointer-events-none size-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700'); ?>
        </button>

    </span>

</div>
