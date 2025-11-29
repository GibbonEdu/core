<div class="editor-<?= $mode ?> relative my-2 bg-white ring-1 ring-inset ring-gray-200" style="min-height: <?= (intval($rows ?? 2) * 18) + 40 ?>px; border-radius: 10px; --tw-ring-color:rgb(208, 212, 220)">
    <textarea class="tinymce w-full focus:shadow-none focus:border-gray-500 hidden" 
        name="<?= $name ?>" 
        id="<?= $id ?>" 
        rows="<?= $rows ?>" 
        style="height: <?= $rows * 18 ?>px;" 
        <?= $required ? 'x-validate.required data-error-msg="'. __('This field is required') .'"' : '' ?>
        <?= $onKeyDownSubmitUrl ? 'data-autosave="'.$onKeyDownSubmitUrl.'"' : '' ?> 

        x-data="{
            loadEditor(element) {
                try {
                    tinymce.remove('#'+element.id);
                } catch (e) {}

                tinymce.init( {...gibbonTinyMCEDefaults, ...gibbonTinyMCE<?= ucfirst($mode) ?>, ...{
                    selector: 'textarea#'+element.id,
                    height: '<?= (intval($rows ?? 2) * 18) + 40 ?>px',
                    min_height: <?= (intval($rows ?? 2) * 18) + 40 ?>,
                } });
            }
        }"
        x-init="loadEditor($el)"><?= htmlentities($value ?? '') ?></textarea>
</div>
