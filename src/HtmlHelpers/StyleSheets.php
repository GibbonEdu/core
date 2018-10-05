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

namespace Gibbon\HtmlHelpers;

/**
 * StyleSheets container that stores and render CSS style sheets
 * information.
 * 
 * @version	7th September 2018
 * @since	  7th September 2018
 * @author	Koala Yeung
 */
class StyleSheets {

  private $_styles;

  /**
   * Add a script of a given type to the region.
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param string $content Script content. If $type is 'file', should be a path. If
   *                        $type is 'inline', should be an.
   * @param string $type    (Optional) Either 'file' or 'inline'. Default 'file'.
   * @param string $media   (Optional) CSS media declaration. Default 'screen'.
   * @return void
   */
  public function add(string $content, string $type='file', string $media='screen'): void
  {
    $this->_styles[] = [
      'type' => $type,
      'media' => $media,
      'content' => $content,
    ];
  }

  /**
   * Add multiple definition through array
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param array $definitions of string or assoc. Assoc array should contain
   *                           'content', and optionally, type and media.
   * @return void
   */
  public function addMultiple(array $definitions): void
  {
    foreach ($definitions as $definition) {
      if (is_string($definition)) {
        $this->add($definition);
      } else if (is_array($definition)) {
        if (!isset($definition['content'])) {
          throw new \Exception('no content in definition array');
        }
        $this->add(
          $definition['content'],
          isset($definition['type']) ? $definition['type'] : 'file',
          isset($definition['media']) ? $definition['media'] : 'screen'
        );
      }
    }
  }

  /**
   * Get all the style definitions.
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param string $region (Optional) Either 'head' or 'bottom'. Default 'head'.
   * @return array An array of script declarations.
   */
  public function getAll(): array
  {
    return $this->_styles;
  }

  /**
   * Render the styles as HTML
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param string $basePath (Optional) Base path of the stylesheet rendering. Default '/'.
   * @return string HTML text rendered.
   */
  public function render(string $basePath='/'): string
  {
    $basePath = rtrim($basePath, '/') . '/'; // ensure trailing slash
    return implode("\n", array_map(function ($style) use ($basePath) {
      switch ($style['type']) {
        case 'inline':
          return '<style type="text/css">'.$style['content'].'</style>';
        case 'file':
          return '<link rel="stylesheet" href="'.htmlspecialchars($basePath . $style['content']).'" type="text/css" media="'.htmlspecialchars($style['media']).'" />';
      }
      throw new \Exception(sprintf('unknown style type: %s', $style['type']));
    }, $this->getAll())) . "\n";
  }
}
