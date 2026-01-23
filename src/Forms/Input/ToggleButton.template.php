

<div
    x-data="{
        tabSelected: 1,
        tabId: $id('tabs'),
        options: <?= $options ?? '{}' ?>,
        optionCount: <?= $optionCount ?>,
        selected: '<?= $selected ?>',
        toggleInput: null,
        tabMarker: null,
        tabButtonClicked(tabButton, option){
            this.selected = option;
            this.tabSelected = tabButton.id.replace(this.tabId + '-', '');
            this.toggleInput.value = option;
            this.tabRepositionMarker(tabButton);

            const event = new Event('change', { bubbles: true });
            this.toggleInput.dispatchEvent(event);
        },
        tabRepositionMarker(tabButton){
            this.tabMarker.style.width=( 100/this.optionCount ) + '%';
            this.tabMarker.style.height=tabButton.offsetHeight + 'px';
            this.tabMarker.style.left=( ((this.tabSelected-1)/this.optionCount)*100 ) + '%';
        },
    }"
    
    x-init="tabSelected = Object.keys(options).indexOf(selected) + 1;" class="relative w-full" >

    <div class="relative flex items-center justify-center w-full text-gray-500  bg-gray-200  ring-1 ring-inset ring-gray-300 rounded-full select-none">

        <template x-for="(option, index) in options" >
            <button x-from-template :id="$id(tabId)" @click="tabButtonClicked($el, option);" 
            x-init="$nextTick(() => { if (option == selected) tabButtonClicked($el, option) } )"
            type="button" class="flex-1 relative z-20 inline-flex items-center justify-center w-full h-10  px-3 text-sm   transition-all rounded-full bg-transparent cursor-pointer whitespace-nowrap" x-text="option" :class="option == selected ? 'text-gray-800' : 'text-gray-600'"></button>
        </template>

        <div x-init="tabMarker = $el; $el.style.left = ( ((tabSelected-1)/optionCount)*100 ) + '%';" class="absolute left-0 p-1 z-10 w-1/<?= $optionCount ?> h-10 duration-300 ease-out" x-cloak><div class="w-full h-full bg-white border rounded-full shadow-sm "></div></div>
    </div>

    <input type="hidden" id="<?= $id ?>" name="<?= $name ?>" :value="selected" x-init="toggleInput = $el" />
    
</div>

