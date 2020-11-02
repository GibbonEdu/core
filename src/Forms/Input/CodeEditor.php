<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Forms\Input;

use Gibbon\Forms\Input\Input;

/**
 * CodeEditor
 *
 * @version v20
 * @since   v20
 */
class CodeEditor extends Input
{
    protected $mode = 'mysql';
    protected $autocomplete = null;

    public function setMode($mode)
    {
        $this->mode = $mode;
        
        return $this;
    }

    public function autocomplete($wordList)
    {
        $this->autocomplete = is_array($wordList) ? $wordList : explode(',', $wordList);
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $text = $this->getAttribute('value');
        $this->setAttribute('value', '');

        $output = '<textarea '.$this->getAttributeString().' style="display: none;">';
        $output .= htmlentities($text, ENT_QUOTES, 'UTF-8');
        $output .= '</textarea>';

        $output .= '<div id="editor" class="w-full" style="height: 400px;">';
        $output .= htmlentities($text, ENT_QUOTES, 'UTF-8');
        $output .= '</div>';

        $output .= '<script src="./lib/ace/ace.js" type="text/javascript" charset="utf-8"></script>';

        if ($this->autocomplete) {
            $output .= '<script src="./lib/ace/ext-language_tools.js" type="text/javascript" charset="utf-8"></script>';
        }
        
        $output .= '<script>';
        if ($this->autocomplete) {
            $output .= 'var languageTools = ace.require("ace/ext/language_tools");';
        }

        $output .= '
            var editor = ace.edit("editor");
            editor.getSession().setUseWrapMode(true);
            editor.getSession().on("change", function(e) {
                $("#'.$this->getID().'").val(editor.getSession().getValue());
            });';

        if ($this->mode == 'twig') {
            $output .= 'editor.getSession().setMode("ace/mode/twig");';
        } elseif ($this->mode == 'mysql') {
            $output .= 'editor.getSession().setMode("ace/mode/mysql");';
        }

        if ($this->autocomplete) {
            $output .= 'editor.setOptions({
                enableBasicAutocompletion: false,
                enableSnippets: true,
                enableLiveAutocompletion: true
            });

            var staticWordCompleter = {
                getCompletions: function(editor, session, pos, prefix, callback) {
                    var wordList = '.json_encode($this->autocomplete).';
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
            ';
        }

        $output .= '</script>';

        return $output;
    }
}
