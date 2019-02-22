<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Date: 22/02/2019
 * Time: 17:50
 */

namespace Gibbon;

/**
 * Acknowledgement of Source
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2010, Union of RAD http://union-of-rad.org (http://lithify.me/)
 */
class MoFileLoader
{
    /**
     * Magic used for validating the format of a MO file as well as
     * detecting if the machine used to create that file was little endian.
     */
    const MO_LITTLE_ENDIAN_MAGIC = 0x950412de;
    /**
     * Magic used for validating the format of a MO file as well as
     * detecting if the machine used to create that file was big endian.
     */
    const MO_BIG_ENDIAN_MAGIC = 0xde120495;
    /**
     * The size of the header of a MO file in bytes.
     */
    const MO_HEADER_SIZE = 28;
    /**
     * Parses machine object (MO) format, independent of the machine's endian it
     * was created on. Both 32bit and 64bit systems are supported.
     *
     * {@inheritdoc}
     */
    public function loadResource($resource)
    {
        $stream = fopen($resource, 'r');
        $stat = fstat($stream);
        if ($stat['size'] < self::MO_HEADER_SIZE) {
            throw new InvalidResourceException('MO stream content has an invalid format.');
        }
        $magic = unpack('V1', fread($stream, 4));
        $magic = hexdec(substr(dechex(current($magic)), -8));
        if (self::MO_LITTLE_ENDIAN_MAGIC == $magic) {
            $isBigEndian = false;
        } elseif (self::MO_BIG_ENDIAN_MAGIC == $magic) {
            $isBigEndian = true;
        } else {
            throw new InvalidResourceException('MO stream content has an invalid format.');
        }
        // formatRevision
        $this->readLong($stream, $isBigEndian);
        $count = $this->readLong($stream, $isBigEndian);
        $offsetId = $this->readLong($stream, $isBigEndian);
        $offsetTranslated = $this->readLong($stream, $isBigEndian);
        // sizeHashes
        $this->readLong($stream, $isBigEndian);
        // offsetHashes
        $this->readLong($stream, $isBigEndian);
        $messages = [];
        for ($i = 0; $i < $count; ++$i) {
            $pluralId = null;
            $translated = null;
            fseek($stream, $offsetId + $i * 8);
            $length = $this->readLong($stream, $isBigEndian);
            $offset = $this->readLong($stream, $isBigEndian);
            if ($length < 1) {
                continue;
            }
            fseek($stream, $offset);
            $singularId = fread($stream, $length);
            if (false !== strpos($singularId, "\000")) {
                list($singularId, $pluralId) = explode("\000", $singularId);
            }
            fseek($stream, $offsetTranslated + $i * 8);
            $length = $this->readLong($stream, $isBigEndian);
            $offset = $this->readLong($stream, $isBigEndian);
            if ($length < 1) {
                continue;
            }
            fseek($stream, $offset);
            $translated = fread($stream, $length);
            if (false !== strpos($translated, "\000")) {
                $translated = explode("\000", $translated);
            }
            $ids = ['singular' => $singularId, 'plural' => $pluralId];
            $item = compact('ids', 'translated');
            if (\is_array($item['translated'])) {
                $messages[$item['ids']['singular']] = stripcslashes($item['translated'][0]);
                if (isset($item['ids']['plural'])) {
                    $plurals = [];
                    foreach ($item['translated'] as $plural => $translated) {
                        $plurals[] = sprintf('{%d} %s', $plural, $translated);
                    }
                    $messages[$item['ids']['plural']] = stripcslashes(implode('|', $plurals));
                }
            } elseif (!empty($item['ids']['singular'])) {
                $messages[$item['ids']['singular']] = stripcslashes($item['translated']);
            }
        }
        fclose($stream);
        return array_filter($messages);
    }
    /**
     * Reads an unsigned long from stream respecting endianness.
     *
     * @param resource $stream
     */
    private function readLong($stream, bool $isBigEndian): int
    {
        $result = unpack($isBigEndian ? 'N1' : 'V1', fread($stream, 4));
        $result = current($result);
        return (int) substr($result, -8);
    }
}