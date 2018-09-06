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
 * Scripts container that stores and render scripts
 * of a certain region.
 *
 * @version	7th September 2018
 * @since	  7th September 2018
 * @author	Koala Yeung
 */
class Scripts {

  private $_scripts;

  /**
   * Add a script of a given type to the region.
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param string $content Script content. If $type is 'file', should be a path. If
   *                        $type is 'inline', should be an.
   * @param string $region  (Optional) Either 'head' or 'bottom'. Default 'head'.
   * @param string $type    (Optional) Either 'file' or 'inline'. Default 'file'.
   * @return void
   */
  public function add(string $content, string $region='head', string $type='file'): void
  {
    $this->_scripts[$region][] = [
      'type' => $type,
      'content' => $content,
    ];
  }

  /**
   * Add multiple definition through array
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param array $definitions of string or assoc. Assoc array should contain
   *                           'content', and optionally, region and type.
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
          isset($definition['region']) ? $definition['region'] : 'head',
          isset($definition['type']) ? $definition['type'] : 'file'
        );
      }
    }
  }

  /**
   * Get all the script definition of the region.
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param string $region (Optional) Either 'head' or 'bottom'. Default 'head'.
   * @return array An array of script declarations.
   */
  public function getAll(string $region='head'): array
  {
    if (!isset($this->_scripts[$region])) return [];
    return $this->_scripts[$region];
  }

  /**
   * Render the script region as HTML
   *
   * @version	7th September 2018
   * @since	  7th September 2018
   * @param string $region   Either 'head' or 'bottom'
   * @param string $basePath (Optional) Base path of the script rendering. Default '/'.
   * @return string HTML text rendered.
   */
  public function render(string $region='head', string $basePath='/'): string
  {
    $basePath = rtrim($basePath, '/') . '/'; // ensure trailing slash
    return implode("\n", array_map(function ($script) use ($basePath) {
      switch ($script['type']) {
        case 'inline':
          return '<script type="text/javascript">'.$script['content'].'</script>';
        case 'file':
          return '<script type="text/javascript" src="'.htmlspecialchars($basePath . $script['content']).'"></script>';
      }
      throw new \Exception(sprintf('unknown script type: %s', $script['type']));
    }, $this->getAll($region))) . "\n";
  }
}
