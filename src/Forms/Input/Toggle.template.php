<div x-data="{ 'toggle': <?= $element['value'] == 'Y' ? 'true' : 'false'; ?> }" class="flex items-center justify-end mx-2" x-id="['toggle-label']">

    <input id="<?= $element['id']; ?>" type="hidden" name="<?= $element['name']; ?>" :value="toggle ? 'Y' : 'N'" value="">
    
    <label id="<?= $element['id']; ?>Label" for="<?= $element['id']; ?>" @click="$refs.toggle.click(); $refs.toggle.focus()" :id="$id('toggle-label')" class="text-gray-900 font-medium" >
        <span x-text="toggle ? '<?= __('Yes'); ?>' : '<?= __('No'); ?>'"></span>
    </label>
    
    <button type="button" role="switch" x-ref="toggle" x-bind="toggle" @click="toggle = ! toggle"
        class="relative ml-4 inline-flex w-16 rounded-full border py-1 transition duration-500 ease-in-out" 
        :class="toggle ? 'border-blue-500 bg-blue-400' : 'bg-gray-300'" >
        
        <span aria-hidden="true" :class="toggle ? 'border-blue-500' : ''" 
            :style="toggle ? 'transform: translate(2.25rem);' : 'transform: translate(0.25rem);'" 
            class="border bg-white h-6 w-6 rounded-full transition duration-500 ease-in-out" >
        </span>
    </button>
</div>
