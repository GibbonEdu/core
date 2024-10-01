<div x-data="{ 'toggle': '<?= $value; ?>', 'onValue': '<?= $onValue; ?>', toggleSwitch() { this.toggle == this.onValue ? this.toggle = '<?= $offValue; ?>' :  this.toggle = '<?= $onValue; ?>' } }" class="<?= $class; ?> flex items-center justify-end mx-1" x-id="['toggle-label']">

    <input id="<?= $id; ?>" type="hidden" name="<?= $name; ?>" :value="toggle" value="<?= $value ?? $offValue; ?>" x-model="toggle; $dispatch('change')">
    
    <label id="<?= $id; ?>Label" for="<?= $id; ?>" @click="$refs.toggle.click(); $refs.toggle.focus()" :id="$id('toggle-label')" class="text-gray-900 font-medium" >
        <span x-text="toggle == onValue ? '<?= $onLabel; ?>' : '<?= $offLabel; ?>'">
            <?= $value == $onValue ? $onLabel : $offLabel; ?>
        </span>
    </label>
    
    <button type="button" role="switch" x-ref="toggle" x-model="toggle" @click="toggleSwitch()"
        class="relative ml-4 inline-flex w-16 rounded-full border py-1 transition duration-300 ease-in-out" 
        :class="toggle == onValue ? 'border-blue-500 bg-blue-400' : 'bg-gray-300'" >
        
        <span aria-hidden="true" :class="toggle == onValue ? 'border-blue-500' : ''" 
            style="<?= $value == $onValue ? 'transform: translate(2.15rem)' : 'transform: translate(0.25rem)'; ?>;"
            :style="toggle == onValue ? 'transform: translate(2.15rem);' : 'transform: translate(0.25rem);'" 
            class="border bg-white h-6 w-6 rounded-full transition duration-300 ease-in-out" >
        </span>
    </button>
</div>
