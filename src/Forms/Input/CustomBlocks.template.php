<script type="text/javascript">
    var blockData<?= $name ?> = <?= json_encode($currentBlocks) ?>;
    var blockEditors<?= $name ?> = <?= json_encode($editors) ?>;
</script>

<div class="customBlocks <?= $compact ? 'compact' : '' ?>" <?= $attributes ?>
    x-data="{
        blocks: [],
        editors: blockEditors<?= $name ?>,
        handleButtonClick(element, index) {

            if (element.dataset.event == 'delete') {
                if (confirm('<?= $deleteMessage ?>')) {
                    this.blocks.splice(index, 1);
                }
            }

            if (element.dataset.event == 'showHide') {
                this.blocks[index].hide = !this.blocks[index].hide;

                this.editors.forEach((name) => {
                    var editor = tinymce.get(name+index);
                    if (editor) $nextTick(() => { 
                        if (this.blocks[index].hide) {editor.hide(); editor.show() }
                        else { editor.hide(); }
                    })
                });
            }
        },
        handleToolClick(element) {
            if (element.classList.contains('addBlock')) {
                this.blocks.push([]);
            }
        },
    }"
    x-init="blocks = blockData<?= $name ?>"
>

    <input type="hidden" class="blockCount" name="<?= $name ?>Count" value="<?= $blockCount ?>" />

    <template x-if="blocks.length == 0">
        <div class="blockPlaceholder" ><?= $placeholder ?></div>
    </template>

    <div class="blocks flex flex-col gap-2 mb-2" <?= $sortable ? 'x-sort.ghost' : '' ?> >

        <template x-for="(block, index) in blocks" x-bind:key="index">
            
            <div x-sort:item="index" class="relative <?= $compact ? 'compact h-min' : '' ?> border rounded-md bg-blue-50 p-1 px-6">

                <div x-sort:handle class="absolute top-0 left-0 mt-2 p-2 h-12">
                    <div class="sortHandle"></div>
                </div>

                <input type="hidden" name="<?= $orderName ?>[]" x-bind:value="index" x-validate.required="">

                <?php foreach ($hiddenInputs as $inputName => $nameFormat) { ?>
                    <input type="hidden" x-bind:name="<?= $nameFormat ?>" x-bind:value="block.<?= $inputName ?>">
                <?php } ?>
                
                <div class="blockInputs flex py-3 pr-4" x-sort:ignore>
                <?= $blockTemplate ?>
                </div>

                <div class="blockSidebar absolute top-0 right-0 mt-2 mr-2" x-sort:ignore>
                    <?= $blockButtons ?>
                </div>
                
            </div>
            
        </template>

    </div>
    
    <?= $toolsTable ?>
</div>
