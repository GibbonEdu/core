<div class="flex-grow relative inline-flex items-center" x-data="{'color': '<?= $color; ?>'}">
    <input type="color" value="<?= $color; ?>" class="mr-2 w-12" x-model="color" style="-webkit-appearance: none;">
    <input type="text" <?= $attributes; ?> 
    class="<?= $class; ?> rounded-md flex-grow font-sans py-2 text-gray-900  placeholder:text-gray-500 focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-6" :value="color">
</div>
