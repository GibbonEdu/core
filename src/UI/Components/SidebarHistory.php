<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
    10|the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
    20|*/

namespace Gibbon\UI\Components;

use Gibbon\Contracts\Services\Session;

/**
 * Tracks recently visited static (non-form) pages for the sidebar back link.
 *
 * @version  v31
 * @since    v31
 */
class SidebarHistory
{
    public const SESSION_KEY = 'sidebarStaticHistory';
    public const MAX_ITEMS = 8;

    /**
     * Filename suffixes treated as form pages unless they are the action entry URL.
     *
     * @var string
     */
    private const FORM_SUFFIX_PATTERN = '/_(add|edit|delete|duplicate|copy|copyForward|copyBack|import)([A-Z][A-Za-z0-9]*)?\.php$/i';

    /**
     * Determine whether the current script is the home page.
     */
    public static function isHomePage(string $address): bool
    {
        $address = trim($address, ' /');

        return $address === '' || strcasecmp($address, 'index.php') === 0;
    }

    /**
     * Determine whether a module action should be skipped when recording history.
     *
     * Add/edit/delete-style pages are treated as forms. If that filename is also
     * the action entry URL (e.g. markbook_edit.php), it is treated as static.
     */
    public static function isFormPage(string $action, ?string $entryURL = null): bool
    {
        $actionFile = basename($action);
        if ($actionFile === '' || preg_match('/Process\.php$/i', $actionFile)) {
            return true;
        }

        if (!preg_match(self::FORM_SUFFIX_PATTERN, $actionFile)) {
            return false;
        }

        if (!empty($entryURL) && strcasecmp($actionFile, basename($entryURL)) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Update stored static-page history and return the previous page to link to.
     *
     * @param array $current {address: string, url: string, title: string, isHome: bool, isForm: bool}
     * @return array{url: string, title: string}|null
     */
    public function updateAndGetPrevious(Session $session, array $current): ?array
    {
        if ((int) $session->get('pageLoads', -1) === 0) {
            $session->forget(self::SESSION_KEY);
        }

        $history = $session->get(self::SESSION_KEY, []);
        if (!is_array($history)) {
            $history = [];
        }

        $previous = null;
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['address'] ?? '') !== $current['address']) {
                $previous = $history[$i];
                break;
            }
        }

        if (empty($current['isForm'])) {
            $page = [
                'address' => $current['address'],
                'url'     => $current['url'],
                'title'   => $current['title'],
            ];

            $history = array_values(array_filter($history, function ($item) use ($current) {
                return ($item['address'] ?? '') !== $current['address'];
            }));
            $history[] = $page;

            if (count($history) > self::MAX_ITEMS) {
                $history = array_slice($history, -self::MAX_ITEMS);
            }

            $session->set(self::SESSION_KEY, $history);
        }

        if (!empty($current['isHome']) || empty($previous['url']) || empty($previous['title'])) {
            return null;
        }

        if (($previous['url'] ?? '') === ($current['url'] ?? '')) {
            return null;
        }

        return [
            'url'   => $previous['url'],
            'title' => $previous['title'],
        ];
    }
}
