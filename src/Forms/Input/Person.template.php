<div id="<?= $id ?>PersonSelect" x-data="{
        allOptions: [],
        options: [],
        search: '',
        isOpen: false,
        openedWithKeyboard: false,
        selectedValue: '<?= $selected ?>',
        selectedOption: null,
        getOptions() {
            Array.from(this.$refs.hiddenInput.querySelectorAll('optgroup')).forEach((optGroup) => { 
                    var group = {
                        label: optGroup.label,
                        options: [],
                    };

                    Array.from(optGroup.children)
                        .filter((option) => option.value != '')
                        .forEach((option) => { 
                            option.group = optGroup.label;
                            group.options.push(option);
                        });

                    this.selectedOption = group.options.find(element => element.value == this.selectedValue) ?? this.selectedOption;
                    this.allOptions.push(group);
                }
            );
        
            this.options = this.allOptions;
        },
        setSelectedOption(option) {
            if (option == null) return;
            this.isOpen = false;
            this.selectedOption = option;
            this.search = '';
            this.openedWithKeyboard = false;

            this.$refs.hiddenInput.value = option.value;
            this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

            htmx.ajax('POST', '<?= $absoluteURL ?>/modules/User/form_person_photoAjax.php', {target:'#<?= $id ?>Photo', values:{fieldName: '<?= $id ?>', gibbonPersonID: option.value}, swap:'outerHTML'}).then(() => {});

            
            $focus.focus(this.$refs.searchSelect)
        },
        clearSelectedOption() {
            this.selectedOption = null;
            this.$refs.hiddenInput.value = null;
            this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            this.$refs.searchSelect.blur();
            this.$refs.personPhoto.src = '';
        },
        getFilteredOptions(query) {
            var search = query.toLowerCase().split(' ');

            this.options = this.allOptions
                .filter((element) => 
                    element.options.some((option) => search.every(v => option.label.toLowerCase().includes(v)) ))
                .map(element => {
                    return Object.assign({}, element, {options : element.options.filter((option) => search.every(v => option.label.toLowerCase().includes(v)))});
                }); 
            
            if (this.options.length === 0) {
                this.$refs.noResultsMessage.classList.remove('hidden')
            } else {
                this.$refs.noResultsMessage.classList.add('hidden')
            }
        },
        handleKeydownOnOptions(event) {
            // if the user presses backspace or the alpha-numeric keys, focus on the search field
            if ((event.keyCode >= 65 && event.keyCode <= 90) || (event.keyCode >= 48 && event.keyCode <= 57) || event.keyCode === 8) {
                this.$refs.searchField.focus()
            }
        },
        toggleSelect(toggle) {
            this.isOpen = toggle;
            if (toggle) {
                $focus.focus(this.$refs.searchField);
                this.$refs.searchField.focus()
                this.search = '';
                this.getFilteredOptions('');
            }
        },
    }" class="relative <?= $outerClass ?? 'flex w-full' ?>  flex-col gap-1" x-on:keydown="handleKeydownOnOptions($event)" x-on:keydown.esc.window="toggleSelect(false), openedWithKeyboard = false" x-init="getOptions()">

    <!-- Hidden Input To Grab The Selected Value  -->
    <select class="hidden invisible personSelect" <?= $attributes; ?> x-ref="hiddenInput">
        <option value=""></option>

        <?php foreach ($options as $group => $optionList)  { ?>
                <optgroup label="<?= $group ?>">

                <?php foreach ($optionList as $option)  { ?>
                <option value="<?= $option['value'] ?>" <?= $option['value'] == $selected? 'selected' : '' ?>  ><?= $option['label'] ?></option>
                <?php } ?>

                </optgroup>

        <?php } ?>
        
    </select>

    <!-- trigger button  -->
    <button type="button" class="<?= $class; ?> <?= $groupClass; ?> inner-input inline-flex w-full items-center justify-start min-w-16 bg-white border border-outline py-2 px-3 text-gray-900  placeholder:text-gray-500 focus:border-blue-500 focus-within:border-blue-500 focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-5 transition  " role="combobox" aria-controls="<?= $id ?>List" aria-haspopup="<?= $id ?>List listbox" x-on:click="toggleSelect(!isOpen)" x-on:keydown.down.prevent="openedWithKeyboard = true" x-on:keydown.enter.prevent="openedWithKeyboard = true" x-on:keydown.space.prevent="openedWithKeyboard = true" x-bind:aria-expanded="isOpen || openedWithKeyboard" x-bind:aria-label="selectedOption ? selectedOption.value : '<?= __($placeholder); ?>'" x-ref="searchSelect" >

        <div class="flex-none relative w-12 h-12 z-10 mr-4 overflow-hidden rounded-full bg-gray-200 border border-solid border-gray-400 bg-no-repeat" style="background-size: 70% 70%; background-position: 50% 50%;">
        <div class="absolute left-1/2 top-1/2 -translate-y-1/2 -translate-x-1/2"><?= icon('solid', 'user', 'size-7 mt-2 text-gray-500') ?></div>
            <img id="<?= $id ?>Photo" x-bind:id="$refs.hiddenInput.id+'Photo'" src="" x-ref="personPhoto">
        </div>

        <span class="block flex-1 text-sm font-normal text-left"  x-text="selectedOption ? selectedOption.label : '<?= __($placeholder); ?>'"><?= $selectedLabel ?? __($placeholder); ?></span>

        <!-- Chevron  -->
        <svg x-cloak x-show="selectedOption == null" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-gray-500" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
        </svg>

        <svg x-cloak x-show="selectedOption != null" x-on:click.stop="clearSelectedOption()" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-gray-500 hover:text-red-700 transition-colors" aria-hidden="true">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
        </svg>
    </button>

    <div x-cloak x-show="isOpen || openedWithKeyboard" id="<?= $id ?>List" class="absolute top-0 left-0 z-50 w-full min-w-52 rounded-md bg-white " role="listbox" aria-label="list" x-on:click.outside="toggleSelect(false); openedWithKeyboard = false" x-on:keydown.down.prevent="$focus.wrap().next()" x-on:keydown.up.prevent="$focus.wrap().previous()" x-transition:enter.opacity.duration.75ms x-transition:leave.opacity.duration.0ms x-trap="openedWithKeyboard"
        style="display:none;">

        <!-- Search  -->
        <div class="relative flex gap-2 max-w-full ">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5" class="absolute ml-3 top-1/2 size-5 -translate-y-1/2 text-on-surface/50 dark:text-on-surface-dark/50" aria-hidden="true" >
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" class="w-full border  focus:border-blue-500 focus:ring-1 focus:ring-inset focus:ring-blue-500 rounded-t-md py-2 pl-10 text-sm text-on-surface focus:outline-hidden focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-75 dark:text-on-surface-dark dark:focus-visible:border-primary-dark" name="searchField" aria-label="<?= __('Search') ?>" x-on:input="getFilteredOptions($el.value)" x-model="search" x-ref="searchField" placeholder="<?= __('Search') ?>" />
        </div>

        <!-- Options  -->
        <ul class="list-none flex w-fit min-w-full max-h-80 flex-col overflow-x-hidden overflow-y-auto m-0 p-1 border -mt-px rounded-b-md bg-white shadow-lg" style="max-width: max(100%, 24rem);">
            <li class="hidden px-4 py-2 text-sm text-on-surface dark:text-on-surface-dark" x-ref="noResultsMessage">
                <span><?= __('No results') ?></span>
            </li>

            <template x-for="(group, groupIndex) in options" x-bind:key="group.label">

                <ul x-from-template class="list-none flex flex-col m-0 p-1">
                    <template x-if="group.label != ''">
                        <li x-from-template x-text="group.label" class="px-3 py-1 text-sm  font-semibold"></li>
                    </template>

                    <template x-for="(item, index) in group.options" x-bind:key="item.value">

                        <li x-from-template class="combobox-option inline-flex justify-between rounded px-3 py-1 text-sm  hover:bg-blue-500 hover:text-white focus:bg-blue-500 focus:text-white cursor-pointer whitespace-nowrap" role="option" x-on:click="setSelectedOption(item)" x-on:keydown.enter="setSelectedOption(item)" x-bind:id="'option-' + index" tabindex="0" :class="{'bg-gray-300 text-gray-900 hover:text-white' : selectedOption == item , 'hover:text-white': selectedOption != item, 'pl-8' : group.label != '' }">

                            <!-- Label  -->
                            <span x-bind:class="selectedOption == item ? 'font-medium' : null" x-text="item.label" class="overflow-hidden text-ellipsis"></span>

                            <!-- Screen reader 'selected' indicator  -->
                            <span class="sr-only" x-text="selectedOption == item ? 'selected' : null"></span>

                            <!-- Checkmark  -->
                            <svg x-cloak x-show="selectedOption == item" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2" class="size-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5">
                            </svg>
                        </li>

                    </template>

                </ul>

            </template>

        </ul>
    </div>
</div>
