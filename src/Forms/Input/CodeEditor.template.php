<textarea <?= $attributes; ?> class="hidden"><?= htmlentities($text ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

<div id="editor<?= $id; ?>" class="w-full" style="height: <?= $height; ?>px;"><?= htmlentities($text ?? '', ENT_QUOTES, 'UTF-8'); ?></div>

<script type="text/javascript">
    (async () => {
        const editorId = "editor<?= $id; ?>";
        const textareaId = "<?= $id; ?>";
        const useAutocomplete = <?= !empty($autocomplete) ? 'true' : 'false'; ?>;

        await import("./lib/ace/ace.js");

        if (useAutocomplete) {
            await import("./lib/ace/ext-language_tools.js");
        }

        ace.config.set('basePath', './lib/ace/');

        const editorElement = document.getElementById(editorId);
        const textareaElement = document.getElementById(textareaId);

        if (!editorElement || !textareaElement) return;

        // Avoid duplicate initialization on the same DOM node
        if (editorElement.env && editorElement.env.editor) return;

        const editor = ace.edit(editorId);

        editor.getSession().setUseWrapMode(true);
        editor.getSession().setMode("ace/mode/<?= !empty($mode) ? $mode : 'html'; ?>");

        editor.getSession().on("change", function () {
            textareaElement.value = editor.getSession().getValue();
        });

        if (useAutocomplete) {
            const languageTools = ace.require("ace/ext/language_tools");

            editor.setOptions({
                enableBasicAutocompletion: false,
                enableSnippets: true,
                enableLiveAutocompletion: true
            });

            const staticWordCompleter = {
                getCompletions: function(editor, session, pos, prefix, callback) {
                    const wordList = <?= json_encode($autocomplete ?? []); ?>;
                    callback(null, wordList.map(function(word) {
                        return {
                            caption: word,
                            value: word,
                            meta: "static"
                        };
                    }));
                }
            };

            languageTools.addCompleter(staticWordCompleter);
        }

        // Sync initial editor content back to the hidden textarea
        textareaElement.value = editor.getSession().getValue();

        // Clean up before HTMX swaps the page
        document.addEventListener('htmx:beforeRequest', function () {
            if (editorElement.env && editorElement.env.editor) {
                editorElement.env.editor.destroy();
            }
        }, { once: true });
    })();
</script>