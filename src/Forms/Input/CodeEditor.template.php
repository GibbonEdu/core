<textarea <?= $attributes; ?> class="hidden"><?= htmlentities($text, ENT_QUOTES, 'UTF-8'); ?></textarea>

<div id="editor" class="w-full" style="height: <?= $height; ?>px;"><?= htmlentities($text, ENT_QUOTES, 'UTF-8'); ?></div>

<script src="./lib/ace/ace.js" type="text/javascript" charset="utf-8"></script>

<?php if (!empty($autocomplete)) { ?>
    <script src="./lib/ace/ext-language_tools.js" type="text/javascript" charset="utf-8"></script>
<?php } ?>
        
<script>
    var useAutocomplete = <?= !empty($autocomplete) ? 'true' : 'false'; ?>;
    if (useAutocomplete) {
        var languageTools = ace.require("ace/ext/language_tools");
    }
    
    var editor = ace.edit("editor");
    editor.getSession().setUseWrapMode(true);
    editor.getSession().on("change", function(e) {
        $("#<?= $id; ?>").val(editor.getSession().getValue());
    });

    editor.getSession().setMode("ace/mode/<?= !empty($mode)? $mode : 'html'; ?>");

    if (useAutocomplete) {
    editor.setOptions({
        enableBasicAutocompletion: false,
        enableSnippets: true,
        enableLiveAutocompletion: true
    });

    var staticWordCompleter = {
        getCompletions: function(editor, session, pos, prefix, callback) {
            var wordList = '<?= json_encode($autocomplete ?? []); ?>';
            callback(null, wordList.map(function(word) {
                return {
                    caption: word,
                    value: word,
                    meta: "static"
                };
            }));
        }
    }
    
    languageTools.addCompleter(staticWordCompleter);
}
</script>
