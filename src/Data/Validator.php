<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
    protected $allowableHTML;
    protected $allowableHTMLString;
    protected $allowableIframeSources;

    public function __construct(string $allowableHTMLString, string $allowableIframeSources = '')
    {
        $this->allowableHTMLString = $allowableHTMLString;
        $this->allowableHTML = $this->parseTagsFromString($this->allowableHTMLString);
        $this->allowableIframeSources = explode(',', mb_strtolower($allowableIframeSources));
    }

    public function getAllowableHTML()
    {
        return $this->allowableHTML;
    }

    public function getAllowableIframeSources()
    {
        return $this->allowableIframeSources;
    }

    /**
     * Sanitize the input data.
     *
     * @param  array  $input            An array of all input data
     * @param  array  $allowableTags    An array of field => tags for input fields that accept HTML
     * @param  bool   $utf8_encode
     * @return array
     */
    public function sanitize($input, $allowableTags = [], $utf8_encode = true)
    {
        $output = [];
        $urls = [];

        // Default allowable tags
        $allowableTags['*CustomEditor'] = 'HTML';

        // Match wildcard * in allowable tags and add these fields to the list
        foreach ($allowableTags as $field => $value) {
            if (mb_stripos($field, '*') === false) continue;

            if ($keys = $this->getWildcardArrayKeyMatches($input, $field)) {
                foreach ($keys as $key) {
                    $allowableTags[$key] = $value;
                }
            }
        }

        // Check allowable fields for URLs
        foreach ($allowableTags as $field => $value) {
            if (is_string($value) && strtoupper($value) == 'URL') {
                $urls[$field] = $field;
            }
        }

        // Process the input
        foreach (array_keys($input) as $field) {
            $value = $input[$field];

            if (is_array($value)) {
                $value = $this->sanitize($value, $allowableTags, $utf8_encode);
            }

            if (is_string($value)) {
                // Strip invalid control characters (borrowed from wp_kses)
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
                $value = preg_replace('/\\\\+0+/', '', $value);

                if (!empty($urls[$field])) {
                    // Sanitize URL
                    $value = $this->sanitizeUrl($value);
                } elseif (!empty($allowableTags[$field])) {
                    // Sanitize HTML
                    if (strtoupper($allowableTags[$field]) == 'RAW') {
                        $output[$field] = $value;
                        continue;
                    }

                    if (strtoupper($allowableTags[$field]) == 'HTML') {
                        $allowableTags[$field] = $this->allowableHTML;
                    }

                    $value = $this->sanitizeHTML($value, $allowableTags[$field]);

                    // Handle encoding if enabled
                    if ($utf8_encode && function_exists('iconv') && function_exists('mb_detect_encoding')) {
                        $current_encoding = mb_detect_encoding($value);
                        if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                            $value = iconv($current_encoding, 'UTF-8', $value);
                        }
                    }
                } else {
                    // Sanitize all
                    $value = strip_tags($value);
                }

                // Trim unnecessary line breaks
                if (strpos($value, "\r") !== false || strpos($value, "\n") !== false) {
                    $value = trim($value);
                }


            }

            $output[$field] = $value;
        }

        return $output;
    }

    /**
     * Sanitize an HTML string by stripping tags and handling the attributes within allowable tags.
     *
     * @param    string  &$value
     * @param    array   $allowableTags
     * @return   string
     */
    public function sanitizeHTML(&$value, $allowableTags = [])
    {
        if (is_string($allowableTags)) {
            $allowableTags = $this->parseTagsFromString($allowableTags);
        }

        if (empty($allowableTags)) {
            return strip_tags($value);
        }

        // Do a generic strip tags first
        $value = $this->stripTags($value, $allowableTags);

        // Do an extended strip tags to remove disallowed attributes
        $value = $this->stripAttributes($value, $allowableTags);

        return $value;
    }

    /**
     * Sanitize plain text where there is no expected HTML.
     *
     * @param string $value
     * @return string
     */
    public function sanitizePlainText($value)
    {
        return strip_tags($value);
    }

    /**
     * Sanitize rich text with expected HTML tags, using the TinyMCE list of allowable tags.
     *
     * @param string $value
     * @return string
     */
    public function sanitizeRichText($value)
    {
        return $this->sanitizeHTML($value, $this->allowableHTML);
    }

    /**
     * Sanitize invalid characters in a URL.
     *
     * @param string $url
     * @return string
     */
    public function sanitizeUrl($url)
    {
        if ($url === '') return $url;

        // Replace and remove disallowed characters
        $url = str_replace(' ', '%20', ltrim($url));
        $url = str_replace('"', '%22', $url);
	    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);
        $url = str_replace("'", '&#039;', $url);

        // If there is no protocol, add a default one
        if (mb_stripos($url, '://') === false) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    /**
     * Sanitize values used in URL parameters.
     *
     * @param string $value
     * @return string
     */
    public function sanitizeUrlParams($values)
    {
        $values = $this->sanitize($values);

        if (is_array($values)) {
            array_walk($values, function (&$value, $key) { 
                $value = mb_substr($key, -2) == 'ID' 
                    ? preg_replace('/[^a-zA-Z0-9-_]/', '', $value) 
                    : preg_replace('/[\<\>\'\"\;]/', '', $value);
            });
        }

        return $values;
    }

    /**
     * Wrapper for strip_tags, accepts an array of tags rather than a string.
     *
     * @param    string  &$value
     * @param    array   &$allowableTags
     * @return   string
     */
    protected function stripTags(&$value, &$allowableTags = [])
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
     * @param    string  &$value
     * @param    array   &$allowableTags
     * @return   string
     */
    protected function stripAttributes(&$value, &$allowableTags = [])
    {
        if (!defined('LIBXML_VERSION')) return $value;

        $dom = new \DOMDocument();
        $dom->formatOutput=true;
        $dom->preserveWhiteSpace=true;
        $dom->validateOnParse=false;
        libxml_use_internal_errors(true);

        $value = '<?xml encoding="utf-8" ?>' . mb_encode_numericentity($value, [0x80, 0xfffffff, 0, 0xfffffff], 'UTF-8');

        if ($dom->loadHTML('<body>'.$value.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            // Iterate over the DOM and remove attributes not in the whitelist
            $nodeList = $dom->getElementsByTagName('*');
            $length = $nodeList->length;

            for ($n = $length-1; $n >= 0; $n--) {
                $node = $nodeList->item($n);
                if (isset($allowableTags[$node->nodeName])) {
                    for ($i = $node->attributes->length-1; $i >= 0; $i--){
                        $attribute = $node->attributes->item($i);
                        if (!in_array($attribute->name, $allowableTags[$node->nodeName])) {
                            $node->removeAttributeNode($attribute);
                        }
                        if (mb_stripos($attribute->value, 'javascript:') !== false) {
                            $node->removeAttributeNode($attribute);
                        }

                        // Handle iframes with an allowlist of src domains
                        if ($node->nodeName == 'iframe' && $attribute->name == 'src' && !empty($attribute->value)) {
                            $host = parse_url(mb_strtolower($attribute->value), \PHP_URL_HOST);
                            $host = str_replace('www.', '', $host);

                            if (empty($host) || !in_array($host, $this->getAllowableIframeSources())) {
                                $node->parentNode->appendChild(new \DOMComment(__('iFrame removed due to security policy')));
                                $node->parentNode->removeChild($node);
                            }
                        }
                    }
                }
                
            }

            // Unwrap the body element, required because libxml needs an outer element (otherwise it adds one)
            $value = str_replace(['<body>', '</body>', '<!--?xml encoding="utf-8" ?-->', '<?xml encoding="utf-8" ?>'], '', $dom->saveHTML());
        }
        
        $value = mb_decode_numericentity($value, [0x80, 0xfffffff, 0, 0xfffffff], 'UTF-8');

        libxml_clear_errors();

        return $value;
    }

    /**
     * Parse a tag string into an array of tag => array(attrs). Handles strip_tags and tinymce-style strings.
     *
     * @param    string  $allowableTagString
     * @return   array
     */
    protected function parseTagsFromString($tagString = '')
    {
        if (empty($tagString)) return [];

        // Handle strip_tags style string: convert <tag><tag><tag> to tag,tag,tag
        $tagString = str_replace(array('<','>'), array('',','), $tagString);

        // Handle tinymce style string and build a tag array
        $tags = array_reduce(explode(',', $tagString), function ($group, $item) {
            $parts = preg_split("/[\[\]|]+/", $item);
            if (!empty($parts[0])) {
                $group[$parts[0]] = array_slice($parts, 1, -1);
            }
            return $group;
        }, []);

        return $tags;
    }

    protected function getWildcardArrayKeyMatches(array $haystack, string $needle )
    {
        $needle = str_replace( '\\*', '.*?', preg_quote($needle, '/' ));
        return preg_grep( '/^' . $needle . '$/i', array_keys($haystack));
    }
}
