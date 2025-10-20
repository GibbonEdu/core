<div class="flex-grow relative inline-flex items-center gap-2" x-data="{'colorSelected': '<?= $color; ?>', isOpen: false, colors: [
<?php if ($palette == 'background') { ?>
    '#ffffff','#f5f5f5','#e5e5e5','#d4d4d4','#a3a3a3','#737373','#525252',
    '#fca5a5','#fdba74','#fde047','#86efac','#5eead4','#93c5fd','#c4b5fd','#f9a8d4',
    '#fee2e2','#ffedd5','#fef9c3','#dcfce7','#ccfbf1','#dbeafe','#ede9fe','#fce7f3',
<?php } else { ?>
    '#ffffff','#f5f5f5','#d4d4d4','#737373','#404040','#171717','#000000',
    '#b91c1c','#c2410c','#a16207','#15803d','#0e7490','#1d4ed8','#6d28d9','#be185d',
    '#ef4444','#f97316','#eab308','#22c55e','#14b8a6','#3b82f6','#8b5cf6','#ec4899',
    '#fca5a5','#fdba74','#fde047','#86efac','#5eead4','#93c5fd','#c4b5fd','#f9a8d4',
    '#fee2e2','#ffedd5','#fef9c3','#dcfce7','#ccfbf1','#dbeafe','#ede9fe','#fce7f3',
<?php }  ?>
],}" x-cloak>

    <button type="button" @click="isOpen = !isOpen"
        class="w-10 h-10 rounded-full focus:outline-none inline-flex items-center justify-center shadow hover:ring" :class="{'text-gray-500': colorSelected == '#ffffff' || colorSelected == '', 'text-white': colorSelected != '#ffffff' && colorSelected != ''}"
        :style="`background: ${colorSelected};`">
        <?= icon('outline', 'swatch', 'size-6'); ?>
    </button>

    <input type="text" <?= $attributes; ?>
        class="<?= $class; ?> <?= $showField ? '' : 'hidden'; ?> rounded-md flex-grow font-sans py-2 text-gray-900  placeholder:text-gray-500 focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-6" x-model="colorSelected">

    <div x-show="isOpen" @click.away="isOpen = false" x-transition:enter="transition ease-out duration-100 transform"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75 transform"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 top-0 mt-12 rounded-md shadow-lg <?= $showField ? 'left-0' : 'right-0'; ?>">
        <div class="rounded-md bg-white shadow-xs w-80 px-4 pt-4 pb-3">
            <div class="grid grid-cols-8 justify-center items-center gap-1">

            <div class="w-8 h-8 overflow-hidden inline-flex justify-center items-center rounded-full cursor-pointer border-4 border-white focus:outline-none focus:ring"  title="<?= __('Custom Colour'); ?>">
                <input type="color" value="<?= $color; ?>" class="-mx-px -my-px p-0" x-model="colorSelected" style="-webkit-appearance: none;" @click="isOpen = false">
                <div class="absolute pointer-events-none" :class="{'text-gray-500': colorSelected == '#ffffff', 'text-white': colorSelected != '#ffffff'}">
                <?= icon('solid', 'eye-dropper', 'size-4 mt-1'); ?>
                </div>
            </div>

                <template x-for="(color, index) in colors" :key="index">
                    <div
                        class="">
                        <template x-if="colorSelected === color">
                            <div
                                class="w-8 h-8 inline-flex rounded-full cursor-pointer border-4 border-white ring ring-gray-300"
                                :style="`background: ${color};`"></div>
                        </template>

                        <template x-if="colorSelected != color">
                            <div
                                @click="colorSelected = color"
                                @keydown.enter="colorSelected = color"
                                role="checkbox"
                                tabindex="0"
                                :aria-checked="colorSelected"
                                class="w-8 h-8 inline-flex rounded-full cursor-pointer border-4 border-white focus:outline-none focus:ring"
                                :style="`background: ${color};`"></div>
                        </template>
                    </div>
                </template>
            </div>

            
        </div>
    </div>
</div>
