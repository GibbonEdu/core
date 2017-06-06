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

namespace Gibbon\Data;

/**
 * Validaton & Sanitization Class
 *
 * @version v14
 * @since   v14
 */
class Validator
{
    /**
     * Sanitize the input data.
     *
     * @param  array  $input            An array of all input data
     * @param  array  $allowableTags    An array of field => tags for input fields that accept HTML
     * @param  bool   $utf8_encode
     *
     * @return array
     */
    public function sanitize($input, $allowableTags = array(), $utf8_encode = true)
    {
        $output = array();

        foreach (array_keys($input) as $field) {
            $value = $input[$field];

            if (is_array($value)) {
                $value = $this->sanitize($value, $allowableTags, $utf8_encode);
            }

            if (is_string($value)) {
                // Strip invalid control characters (borrowed from wp_kses)
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
                $value = preg_replace('/\\\\+0+/', '', $value);

                // Sanitize HTML
                if (!empty($allowableTags[$field])) {
                    $value = $this->sanitizeHTML($value, $allowableTags[$field]);
                } else {
                    $value = strip_tags($value);
                }

                // Trim unnecessary line breaks
                if (strpos($value, "\r") !== false || strpos($value, "\n") !== false) {
                    $value = trim($value);
                }

                // Handle encoding if enabled
                if ($utf8_encode && function_exists('iconv') && function_exists('mb_detect_encoding')) {
                    $current_encoding = mb_detect_encoding($value);
                    if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                        $value = iconv($current_encoding, 'UTF-8', $value);
                    }
                }
            }

            $output[$field] = $value;
        }

        return $output;
    }

    /**
     * Sanitize an HTML string by stripping tags and handling the attributes within allowable tags.
     *
     * @version  v14
     * @since    v14
     * @param    string  &$value
     * @param    array   $allowableTags
     *
     * @return   string
     */
    public function sanitizeHTML(&$value, $allowableTags = array())
    {
        if (is_string($allowableTags)) {
            $allowableTags = $this->parseTagsFromString($allowableTags);
        }

        if (empty($allowableTags)) {
            return $value;
        }

        // Do a generic strip tags first
        $value = $this->stripTags($value, $allowableTags);

        // Do an extended strip tags to remove disallowed attributes
        $value = $this->stripAttributes($value, $allowableTags);

        return $value;
    }

    /**
     * Wrapper for strip_tags, accepts an array of tags rather than a string.
     *
     * @version  v14
     * @since    v14
     * @param    string  &$value
     * @param    array   &$allowableTags
     *
     * @return   string
     */
    protected function stripTags(&$value, &$allowableTags = array())
    {
        // Reduce the tag array into a string of <tag><tag><tag>
        $allowableTagString = array_reduce(array_keys($allowableTags), function ($join, $item) {
            return $join.'<'.$item.'>';
        }, '');

        return strip_tags($value, $allowableTagString);
    }

    /**
     * Strip disallowed attributes from tags using the DOM classes to walk over the HTML elements.
     *
     * @version  v14
     * @since    v14
     * @param    string  &$value
     * @param    array   &$allowableTags
     *
     * @return   string
     */
    protected function stripAttributes(&$value, &$allowableTags = array())
    {
        if (!defined('LIBXML_VERSION')) return $value;

        $dom = new \DOMDocument();
        $dom->formatOutput=true;
        $dom->preserveWhiteSpace=true;
        $dom->validateOnParse=false;
        libxml_use_internal_errors(true);

        if ($dom->loadHTML('<body>'.$value.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            // Iterate over the DOM and remove attributes not in the whitelist
            foreach ($dom->getElementsByTagName('*') as $node) {
                if (isset($allowableTags[$node->nodeName])) {
                    for ($i = $node->attributes->length-1; $i >= 0; $i--){
                        $attribute = $node->attributes->item($i);
                        if (!in_array($attribute->name, $allowableTags[$node->nodeName])) {
                            $node->removeAttributeNode($attribute);
                        }
                    }
                }
            }

            // Unwrap the body element, required because libxml needs an outer element (otherwise it adds one)
            $value = str_replace(array('<body>', '</body>'), '', $dom->saveHTML());
        }
        libxml_clear_errors();

        return $value;
    }

    /**
     * Parse a tag string into an array of tag => array(attrs). Handles strip_tags and tinymce-style strings.
     *
     * @version  v14
     * @since    v14
     * @param    string  $allowableTagString
     *
     * @return   array
     */
    protected function parseTagsFromString($tagString = '')
    {
        if (empty($tagString)) return array();

        // Handle strip_tags style string: convert <tag><tag><tag> to tag,tag,tag
        $tagString = str_replace(array('<','>'), array('',','), $tagString);

        // Handle tinymce style string and build a tag array
        $tags = array_reduce(explode(',', $tagString), function ($group, $item) {
            $parts = preg_split("/[\[\]|]+/", $item);
            if (!empty($parts[0])) {
                $group[$parts[0]] = array_slice($parts, 1, -1);
            }
            return $group;
        }, array());

        return $tags;
    }
}
