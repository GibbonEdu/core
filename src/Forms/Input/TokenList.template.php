<div class="<?= $outerClass ?? 'flex-grow relative flex items-center' ?>" 

    x-data="{
        list: [],
        listValue: '',
        active: false,
        loadList() {
            this.list = $refs.textSource.value
                .split(',')
                .map(item => item.trim());
            this.updateValue();
        },
        addItem(value) {
            var item = value.trim();
            if (item == '') return;

            var items = item.split(',').map(item => item.trim());
            items.forEach((item) => this.list.push(item));
            
            this.$refs.textInput.value = '';
            this.active = false;
            this.updateValue();
        },
        removeItem(index) {
            if (index === undefined) return;
            this.list.splice(index, 1);
            this.updateValue();
        },
        updateList(source) { 
            this.updateValue();
        },
        updateValue() {
            $nextTick(() => { 
                var newList = Array.from($refs.listList.children)
                        .map((element) => { return element.dataset.item })
                        .filter((item) => item !== undefined);

                this.listValue = newList.join(',');
                console.log(this.listValue);
            });

            
        }
    }"
    x-init="loadList()"
    @click="active = true; console.log($refs.textSource.value)"
    @click.away="active = false"
>
    
    
    <div id="<?= $id ?>TokenList" name="<?= $name ?>TokenList" 
        class="<?= $class; ?> <?= $groupClass; ?> w-full flex flex-wrap items-center justify-start gap-1 p-1 placeholder:text-gray-500  sm:text-sm sm:leading-5 border bg-white min-h-[2.375rem]
        
        <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus-within:ring-inset focus-within:ring-blue-500 focus-within:border-blue-500 focus-within:ring-1 '; ?>"

        @keydown.backspace="removeItem($focus.focused().dataset.index)" @keydown.right="$focus.wrap().next()" @keydown.left="$focus.wrap().previous()" 
        @keydown.meta.a.prevent="console.log('Select All')"
        />

        <div x-sort.ghost="updateList($el)"  class="flex flex-wrap items-center justify-start gap-1" x-ref="listList">

            <template x-for="(item, index) in list">
                <div x-sort.item="item" x-bind:data-index="index" x-bind:data-item="item" tabindex="1" 
                    class="block tag bg-gray-100 hover:bg-gray-200 focus:border-blue-500 focus:bg-blue-500 focus:text-white ">
                    <span class="whitespace-nowrap pointer-events-none" x-text="item"></span>
                </div>
            </template>

        </div>

        <input x-sort.ignore type="text" class="w-24 overflow-x-visible border-0 bg-transparent focus:ring-0 text-gray-900 sm:text-sm sm:leading-5 px-1 py-0" 
            x-ref="textInput"  @click.away="addItem($el.value)" @keydown.enter.prevent="addItem($el.value)"
        />

    </div>

    <input type="hidden" x-ref="textSource" <?= $attributes; ?> x-bind:value="listValue" >

</div>
