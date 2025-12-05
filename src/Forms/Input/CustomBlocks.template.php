<script type="text/javascript">
    var blockData<?= $name ?> = <?= json_encode($currentBlocks) ?>;
    var blockEditors<?= $name ?> = <?= json_encode($editors) ?>;
</script>

<div <?= $attributes ?>
    x-data="{
        blocks: [],
        blockCount: 0,
        editors: blockEditors<?= $name ?>,
        showAll: false,
        nextIndex: <?= $index ?>,
        handleButtonClick(element, index) {

            if (element.dataset.event == 'delete') {
                if (confirm('<?= $deleteMessage ?>')) {
                    this.blocks.splice(index, 1);
                    this.blockCount = this.blocks.length;
                }
            }

            if (element.dataset.event == 'copy') {
                var block = {...this.blocks[index] };
                block.<?= $primaryInput ?> += ' (<?= __('Copy') ?>)';
                this.createBlock(block);
            }

            if (element.dataset.event == 'showHide') {
                this.showHideBlock(index, !this.blocks[index].show);
            }
        },
        createBlock(block) {
            var index = this.nextIndex;
            block.id = '<?= $name ?>' + index;
            block.show = true;
            this.blocks.push(block);

            $nextTick(() => { htmx.process(htmx.find('#<?= $name ?>' + index)); this.showHideBlock(index, true); });

            this.blockCount = this.blocks.length;
            this.nextIndex++;
        },
        handleToolClick(element) {
            if (element.classList.contains('addBlock')) {
                this.createBlock({}); 
            }
        },
        showHide() {
            this.showAll = !this.blocks.some((block) => block.show);
            this.blocks.forEach((block) => this.showHideBlock(block, this.showAll) );
        },
        showHideBlock(block, show) {
            block.show = show;
            this.showAll = this.showAll || show;
        }
    }"
    x-init="blocks = blockData<?= $name ?>; blockCount = blocks.length;"
>

    <input type="hidden" class="blockCount" name="<?= $name ?>Count" x-bind:value="blockCount" />

    <div x-show="blockCount == 0" class="flex justify-center items-center h-24 mb-2 border border-dashed border-gray-400 rounded-md">
        <span class="text-xl text-gray-400">
            <?= $placeholder ?>
        </span>
        
    </div>


    <div <?= $sortable ? 'x-sort.ghost="handleSort"' : '' ?>  class="blocks flex flex-col transition-all gap-2" x-data="{
        handleSort: (item, position) => {
            const itemPos = blocks.findIndex((r) => r.id == item)
            let itemToMove = blocks.splice(itemPos, 1)[0];
            blocks.splice(position, 0, itemToMove);
            $refs.blockList._x_prevKeys = blocks.map((item) => item.id);
        }
    }">

        <template x-for="(block, index) in blocks" x-bind:key="block.id" x-ref="blockList">
            
            <div x-sort:item="block.id" class="relative <?= $compact ? 'compact h-min' : '' ?> border rounded-md bg-gray-50" x-bind:id="block.id">

                <div class="flex  bg-blue-50 hover:bg-blue-50/50 rounded-t-md " :class="{'border-b': block.show, 'rounded-b-md' : !block.show}">

                    <div x-sort:handle class="drag-sort-handle w-6 ltr:border-r rtl:border-l hover:bg-gray-200 rounded-tl-md" :class="{'rounded-bl-md': !block.show}"></div>

                    <div @click="showHideBlock(block, !block.show)" class="flex-1 flex items-center text-sm text-gray-800 w-full py-3 px-3 rounded-tr-md cursor-pointer">
                        <span x-text="block.primaryInput ?? block.<?= $primaryInput ?> ?? '<?= __('Untitled') ?>'" :class="!block.primaryInput && !block.<?= $primaryInput ?> ? 'text-gray-500' : ''"></span>
                    </div>

                    <?= $blockButtons ?>

                </div>

                <div x-show="block.show" x-transition.opacity class="blockInputs py-3 px-2" x-sort:ignore>
                    <?= $blockTemplate ?>
                </div>

                <input type="hidden" name="<?= $orderName ?>[]" x-bind:value="index" x-validate.required="">

                <?php foreach ($hiddenInputs as $inputName => $nameFormat) { ?>
                    <input type="hidden" x-bind:name="<?= $nameFormat ?>" x-bind:value="block.<?= $inputName ?>">
                <?php } ?>

            </div>
            
        </template>

        

    </div>

    <nav class="flex mt-2">
            <?= $toolsTable ?>

            <button x-show="blockCount > 0" @click="showHide()" class="inline-flex rounded-md text-sm sm:leading-5 bg-gray-100 hover:bg-gray-200 text-gray-800 align-middle items-center border border-gray-400 gap-2 px-3 py-2 font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500" type="button">
                <span x-show="!showAll" title="<?= __('Expand All') ?>" class="inline-flex"><?= icon('basic', 'expand-lines', 'size-5 text-gray-600') ?></span>
                <span x-cloak x-show="showAll" title="<?= __('Collapse All') ?>" class="inline-flex"><?= icon('basic', 'collapse-lines', 'size-5 text-gray-600') ?></span>
            </button>
        </nav>
    
    
</div>
