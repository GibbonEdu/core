<div x-data="{ 'toggle': '<?= $element['value']; ?>', toggleSwitch() { this.toggle == 'Y' ? this.toggle = 'N' :  this.toggle = 'Y' } }" class="flex items-center justify-end mx-2" x-id="['toggle-label']">

    <input id="<?= $element['id']; ?>" type="hidden" name="<?= $element['name']; ?>" :value="toggle" value="<?= $element['value']; ?>" x-model="toggle; $dispatch('change')">
    
    <label id="<?= $element['id']; ?>Label" for="<?= $element['id']; ?>" @click="$refs.toggle.click(); $refs.toggle.focus()" :id="$id('toggle-label')" class="text-gray-900 font-medium" >
        <span x-text="toggle == 'Y' ? '<?= __('Yes'); ?>' : '<?= __('No'); ?>'">
            <?= $element['value'] == 'Y' ? __('Yes') : __('No'); ?>
        </span>
    </label>
    
    <button type="button" role="switch" x-ref="toggle" x-model="toggle" @click="toggleSwitch()"
        class="relative ml-4 inline-flex w-16 rounded-full border py-1 transition duration-300 ease-in-out" 
        :class="toggle == 'Y' ? 'border-blue-500 bg-blue-400' : 'bg-gray-300'" >
        
        <span aria-hidden="true" :class="toggle == 'Y' ? 'border-blue-500' : ''" 
            style="<?= $element['value'] == 'Y' ? 'transform: translate(2.15rem)' : 'transform: translate(0.25rem)'; ?>;"
            :style="toggle == 'Y' ? 'transform: translate(2.15rem);' : 'transform: translate(0.25rem);'" 
            class="border bg-white h-6 w-6 rounded-full transition duration-300 ease-in-out" >
        </span>
    </button>
</div>
