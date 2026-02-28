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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Module\Students\View\LibraryBorrowingView;
use Gibbon\Services\Format;

/**
 * LibraryBorrowingPage
 * 
 * Displays library borrowing history for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class LibraryBorrowingPage extends ProfilePage
{
    private LibraryBorrowingView $libraryBorrowing;
    private \Gibbon\View\View $view;

    public function __construct(
        Session $session,
        LibraryBorrowingView $libraryBorrowing,
        \Gibbon\View\View $view
    ) {
        parent::__construct($session);
        $this->libraryBorrowing = $libraryBorrowing;
        $this->view = $view;
    }

    /**
     * Check if the current user has permission to view library borrowing
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_full')) {
            return false;
        }

        return Access::allows('Library', 'library_browse');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Library Borrowing');
    }


    /**
     * Generate HTML output for the library borrowing page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        ob_start();
        
        $this->libraryBorrowing->setStudent($this->gibbonPersonID)->compose($this->view);
        
        return ob_get_clean();
    }
}
