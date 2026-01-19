<script type="text/javascript">
    var blockData<?= $id ?> = <?= json_encode($currentBlocks) ?>;
    var predefinedData<?= $id ?> = <?= json_encode($predefinedBlocks) ?>;
    var indexNext<?= $sortGroup ?> = <?= $indexNext ?>;
</script>

<div <?= $attributes ?>
    class="customBlocks"
    x-data="{
        blocks: [],
        predefined: [],
        blockCount: 0,
        showAll: false,
        sorting: false,
        dragging: false,
        handleButtonClick(element, index) {

            if (element.dataset.event == 'delete') {
                if (confirm('<?= $deleteMessage ?>')) {
                    $nextTick(() => {
                        this.blocks.splice(index, 1);
                        this.blockCount = this.blocks.length;
                    });
                }
            }

            if (element.dataset.event == 'copy') {
                this.editorSave(this.blocks[index]);
                var block = {...this.blocks[index] };
                block.<?= $primaryInput ?> = (block.<?= $primaryInput ?> ? block.<?= $primaryInput ?> : '<?= __('Untitled') ?>') + ' (<?= __('Copy') ?>)';
                block.<?= $uniqueID ?> = null;
                this.createBlock(block, false);
            }

            if (element.dataset.event == 'showHide') {
                this.showHideBlock(this.blocks[index], !this.blocks[index].show);
            }
        },
        createBlock(block, showBlock = true) {
            var index = indexNext<?= $sortGroup ?>;
            block.id = '<?= $id ?>' + index;
            block.index = index;
            block.show = showBlock;
            this.blocks.push(block);

            //$nextTick(() => { this.showHideBlock(block, showBlock);  });

            this.blockCount = this.blocks.length;
            indexNext<?= $sortGroup ?>++;
        },
        handleBlockAdd(data) {
            data.forEach((block) => this.createBlock(block, false));
        },
        handleBlockRemove(data) {
            this.blocks = data === -1 ? [] : this.blocks.filter((block) => !data.includes(block.index));
            this.blockCount = this.blocks.length;
        },
        handleToolClick(element) {
            if (element.classList.contains('addBlock')) {
                this.createBlock({}); 
            }
        },
        handleToolChange(element) {
            if (element.value != '' && element.classList.contains('addBlock')) {
                this.createBlock(this.predefined[element.value]); 
            }
        },
        showHide(show = null) {
            this.showAll = show ?? !this.blocks.some((block) => block.show);
            this.blocks.forEach((block) => this.showHideBlock(block, this.showAll) );
        },
        showHideBlock(block, show) {
            if (show) this.editorInit(block);
            else if (!show) this.editorRemove(block);
            
            block.show = show;
            this.showAll = this.showAll || show;
        },
        editorInit(block) {
            if (this.sorting) return;

            document.getElementById(block.id)?.querySelectorAll('textarea.tinymce')?.forEach((textarea) => {
                tinymce.init( {...gibbonTinyMCEDefaults, ...gibbonTinyMCEFull, ...{
                    selector: '#'+textarea.id,
                    height: (textarea.dataset.rows * 20) + 110,
                } });
            } );
            
        },
        editorSave(block) {
            document.getElementById(block.id)?.querySelectorAll('textarea.tinymce')?.forEach((textarea) => {
                var editor = tinymce.get(textarea.id);
                block[textarea.dataset.name] = editor ? editor.save() : textarea.value;
            });
        },
        editorRemove(block) {
            document.getElementById(block.id)?.querySelectorAll('textarea.tinymce')?.forEach((textarea) => {
                var editor = tinymce.get(textarea.id);
                if (editor) {
                    editor.save();
                    editor.destroy();
                }
            });
        }
    }"
    x-init="blocks = blockData<?= $id ?>; blockCount = blocks.length; predefined = predefinedData<?= $id ?>;"
    @add-blocks="handleBlockAdd(event.detail)"
    @remove-blocks="handleBlockRemove(event.detail)"
>

    <input type="hidden" class="blockCount" name="<?= $name ?>Count" x-bind:value="blockCount" />

    <?php if ($placeholder) { ?>
    <div x-show="blockCount == 0" class="flex justify-center items-center h-24 mb-2 border border-dashed border-gray-400 rounded-md">
        <span class="text-xl text-gray-400">
            <?= $placeholder ?>
        </span>
    </div>
    <?php } ?>


    <div <?= $sortable ? 'x-sort.ghost="handleSort" x-sort:config="{onStart: beforeSort, onEnd: afterSort, }"' : '' ?> <?= $sortGroup ? 'x-sort:group="'.$sortGroup.'"' : '' ?>   class="blocks flex flex-col transition-all gap-2" x-data="{
        handleSort: (item, position) => {
            const itemPos = blocks.findIndex((r) => r.id == item);
            if (itemPos >= 0) {
                let itemToMove = blocks.splice(itemPos, 1)[0];
                blocks.splice(position, 0, itemToMove);
                $refs.blockList._x_prevKeys = blocks.map((item) => item.id);
            } else {
                var draggedOutside = document.getElementById(item);
                if (draggedOutside) {
                    draggedOutside.querySelectorAll('a[data-event=\'copy\']').forEach((item) => item.remove() );
                }
            }
            
        },
        beforeSort: function (event) {
            sorting = true;
            showHide(false);
        },
        afterSort: function (event) {
            sorting = false;
            $refs.blockList._x_prevKeys = blocks.map((item) => item.id);
        },
    }">

        <template x-for="(block, index) in blocks" x-bind:key="block.id" x-ref="blockList">
            
            <div x-from-template x-sort:item="block.id" class="relative <?= $compact ? 'compact h-min' : '' ?> border rounded-md bg-gray-50" x-bind:id="block.id">

                <div class="flex  bg-blue-50 hover:bg-blue-50/50 rounded-t-md " :class="{'border-b': block.show, 'rounded-b-md' : !block.show}">

                    <div x-sort:handle class="drag-sort-handle w-6 ltr:border-r rtl:border-l hover:bg-gray-200 rounded-tl-md" :class="{'rounded-bl-md': !block.show}"></div>

                    <div @click="showHideBlock(block, !block.show)" class="flex-1 flex items-center text-sm text-gray-800 w-full py-3 px-3 rounded-tr-md cursor-pointer">
                        <span x-text="block.primaryInput ?? (block.<?= $primaryInput ?> ? block.<?= $primaryInput ?> : '<?= __('Untitled') ?>' )" class="primaryInput" :class="!block.primaryInput && !block.<?= $primaryInput ?> ? 'text-gray-500' : ''"></span>
                    </div>

                    <?= $blockButtons ?>

                </div>

                <div x-show="block.show" x-transition.opacity class="blockInputs py-3 px-2" x-sort:ignore>
                    <?= $blockTemplate ?>
                </div>

                <input type="hidden" name="<?= $orderName ?>[]" x-bind:value="block.index">

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
