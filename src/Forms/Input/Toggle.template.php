<div x-data="{ 'toggle': '<?= $value; ?>', 'disabled': '<?= $disabled || $readonly; ?>', 'onValue': '<?= $onValue; ?>', toggleSwitch() { if (this.disabled == true) return; this.toggle == this.onValue ? this.toggle = '<?= $offValue; ?>' :  this.toggle = '<?= $onValue; ?>'; } }" class="<?= $class; ?> flex items-center justify-end mx-1 <?= $toggleSize == 'sm' ? 'gap-2' : 'gap-4'; ?>" x-id="['toggle-label']" @click="<?= $_click ?? ''; ?>" >

    <input id="<?= $id; ?>" type="hidden" name="<?= $name; ?>" :value="toggle" value="<?= $value ?? $offValue; ?>" x-model="toggle; $dispatch('change')">
    
    <label id="<?= $id; ?>Label" for="<?= $id; ?>" @click="$refs.toggle.click(); $refs.toggle.focus()" :id="$id('toggle-label')" class=" font-medium text-xs <?= $disabled ? 'text-gray-500' : 'text-gray-900' ?>" >
        <span x-text="toggle == onValue ? '<?= $onLabel; ?>' : '<?= $offLabel; ?>'">
            <?= $value == $onValue ? $onLabel : $offLabel; ?>
        </span>
    </label>

    <?php $disabledClass = $disabled || $readonly ? 'border-dashed cursor-not-allowed' : ''; ?>
    <?php $flip = $rtl ? '-1' : '1'; ?>
    
    <?php if ($toggleSize == 'sm') { ?>

    <button type="button" role="switch" x-ref="toggle" x-model="toggle" @click="toggleSwitch()"
        class="relative inline-flex w-8 rounded-full border py-0.5 transition duration-300 ease-in-out <?= $disabledClass ?>" 
        :class="toggle == onValue ? 'border-blue-500 bg-blue-400' : 'border-gray-400 bg-gray-300'" >
        
        <span aria-hidden="true" :class="toggle == onValue ? 'border-blue-500' : 'border-gray-400'" 
            style="<?= $value == $onValue ? 'transform: translateX('.($flip * 0.95).'rem)' : 'transform: translateX('.($flip * 0.2).'rem)'; ?>;"
            :style="toggle == onValue ? 'transform: translateX(<?= $flip * 0.95 ?>rem);' : 'transform: translateX(<?= $flip * 0.2 ?>rem);'" 
            class="border bg-white h-3 w-3 rounded-full transition duration-300 ease-in-out <?= $disabledClass ?>" >
        </span>
    </button>

    <?php } else { ?>

        
    <button type="button" role="switch" x-ref="toggle" x-model="toggle" @click="toggleSwitch()"
        class="relative inline-flex w-16 rounded-full border  py-1 transition duration-300 ease-in-out <?= $disabledClass ?>" 
        :class="toggle == onValue ? 'border-blue-500 bg-blue-400' : '<?= $toggleType == 'ActiveInactive' ? 'bg-red-300 border-red-600' : 'bg-gray-300 border-gray-400' ?>'" >
        
        <span aria-hidden="true" :class="toggle == onValue ? 'border-blue-500' : '<?= $toggleType == 'ActiveInactive' ? 'border-red-600' : 'border-gray-400' ?>'" 
            style="<?= $value == $onValue ? 'transform: translateX('.($flip * 2.15).'rem)' : 'transform: translateX('.($flip * 0.25).'rem)'; ?>;"
            :style="toggle == onValue ? 'transform: translateX(<?= $flip * 2.15  ?>rem);' : 'transform: translateX(<?= $flip * 0.25 ?>rem);'" 
            class="border bg-white h-6 w-6 rounded-full transition duration-300 ease-in-out <?= $disabledClass ?>" >
        </span>
    </button>

    <?php }  ?>
</div>
