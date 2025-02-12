<textarea <?= $attributes; ?> class="hidden"><?= htmlentities($text, ENT_QUOTES, 'UTF-8'); ?></textarea>

<div id="editor<?= $id; ?>" class="w-full" style="height: <?= $height; ?>px;"><?= htmlentities($text, ENT_QUOTES, 'UTF-8'); ?></div>

<script type="text/javascript">
    function setupEditor () {
        var useAutocomplete = <?= !empty($autocomplete) ? 'true' : 'false'; ?>;
        if (useAutocomplete) {
            var languageTools = ace.require("ace/ext/language_tools");
        }

        ace.config.set('basePath', './lib/ace/');
        
        var editor = ace.edit("editor<?= $id; ?>");
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
                    var wordList = <?= json_encode($autocomplete ?? []); ?>;
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
    }

    // Ensure the scripts load in the correct order
    (async () => {
        await import("./lib/ace/ace.js")
        await import("./lib/ace/ext-language_tools.js")
        await setupEditor();
    })();

    // Remove the existing editor before htmx swaps to a new page
    document.addEventListener('htmx:beforeRequest', function (event) {
        ace.edit("editor<?= $id; ?>").destroy();
        $editor = $("#editor<?= $id; ?>")
        $editor.remove();
    }, { once: true });
</script>
