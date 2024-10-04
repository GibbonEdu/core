
<div x-data="{ show: true }" class="flex-grow relative flex">
<input :type="show ? 'password' : 'text'" <?= $attributes; ?> autocomplete="off" 
    class="<?= $class; ?> w-full min-w-0 rounded-md py-2  placeholder:text-gray-400  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
    
    <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed :ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>"
    />

    <span class="absolute top-0.5 right-0.5">
        <svg @click="show = !show" :class="{'hidden': !show, 'block':show }" class="password-show w-9 h-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
        <path fill-rule="evenodd" d="M1.38 8.28a.87.87 0 0 1 0-.566 7.003 7.003 0 0 1 13.238.006.87.87 0 0 1 0 .566A7.003 7.003 0 0 1 1.379 8.28ZM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" clip-rule="evenodd" />
        </svg>

        <svg @click="show = !show" :class="{'block': !show, 'hidden':show }" class="password-hide hidden w-9 h-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
        <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l10.5 10.5a.75.75 0 1 0 1.06-1.06l-1.322-1.323a7.012 7.012 0 0 0 2.16-3.11.87.87 0 0 0 0-.567A7.003 7.003 0 0 0 4.82 3.76l-1.54-1.54Zm3.196 3.195 1.135 1.136A1.502 1.502 0 0 1 9.45 8.389l1.136 1.135a3 3 0 0 0-4.109-4.109Z" clip-rule="evenodd" />
        <path d="m7.812 10.994 1.816 1.816A7.003 7.003 0 0 1 1.38 8.28a.87.87 0 0 1 0-.566 6.985 6.985 0 0 1 1.113-2.039l2.513 2.513a3 3 0 0 0 2.806 2.806Z" />
        </svg>


    </span>


</div>
