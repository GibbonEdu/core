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
use Gibbon\Services\Format;

/**
 * BehaviourPage
 * 
 * Displays behaviour records for the student.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class BehaviourPage extends ProfilePage
{
    private $connection2;
    private $guid;
    private $container;

    public function __construct(
        Session $session,
        $connection2,
        $guid,
        $container
    ) {
        parent::__construct($session);
        $this->connection2 = $connection2;
        $this->guid = $guid;
        $this->container = $container;
    }

    /**
     * Check if the current user has permission to view behaviour records
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'View Student Profile_full')) {
            return false;
        }

        return isActionAccessible($this->guid, $this->connection2, '/modules/Behaviour/behaviour_view.php');
    }

    /**
     * Generate HTML output for the behaviour page
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
        
        // Include module functions and render behaviour records
        $gibbonPersonID = $this->gibbonPersonID;
        $connection2 = $this->connection2;
        $guid = $this->guid;
        $container = $this->container;
        $session = $this->session;
        
        include './modules/Behaviour/moduleFunctions.php';
        
        // Get behaviour records
        $gibbonSchoolYearID = $this->gibbonSchoolYearID;
        
        // Render positive and negative behaviour
        echo '<h3>';
        echo __('Positive Behaviour');
        echo '</h3>';
        
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonBehaviour.*, gibbonPerson.preferredName, gibbonPerson.surname 
                FROM gibbonBehaviour 
                JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
                WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID 
                AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND type='Positive' 
                ORDER BY date DESC, timestamp DESC";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        if ($result->rowCount() < 1) {
            echo '<div class="message">';
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            echo '<table class="fullWidth colorOddEven" cellspacing="0">';
            echo '<tr class="head">';
            echo '<th>';
            echo __('Date');
            echo '</th>';
            echo '<th>';
            echo __('Descriptor');
            echo '</th>';
            echo '<th>';
            echo __('Level');
            echo '</th>';
            echo '<th>';
            echo __('Teacher');
            echo '</th>';
            echo '<th>';
            echo __('Comment');
            echo '</th>';
            echo '</tr>';
            
            while ($row = $result->fetch()) {
                echo '<tr>';
                echo '<td>';
                echo Format::date($row['date']);
                echo '</td>';
                echo '<td>';
                echo $row['descriptor'];
                echo '</td>';
                echo '<td>';
                echo $row['level'];
                echo '</td>';
                echo '<td>';
                echo Format::name('', $row['preferredName'], $row['surname'], 'Staff');
                echo '</td>';
                echo '<td>';
                echo $row['comment'];
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        
        echo '<h3>';
        echo __('Negative Behaviour');
        echo '</h3>';
        
        $sql = "SELECT gibbonBehaviour.*, gibbonPerson.preferredName, gibbonPerson.surname 
                FROM gibbonBehaviour 
                JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
                WHERE gibbonBehaviour.gibbonPersonID=:gibbonPersonID 
                AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID 
                AND type='Negative' 
                ORDER BY date DESC, timestamp DESC";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        if ($result->rowCount() < 1) {
            echo '<div class="message">';
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            echo '<table class="fullWidth colorOddEven" cellspacing="0">';
            echo '<tr class="head">';
            echo '<th>';
            echo __('Date');
            echo '</th>';
            echo '<th>';
            echo __('Descriptor');
            echo '</th>';
            echo '<th>';
            echo __('Level');
            echo '</th>';
            echo '<th>';
            echo __('Teacher');
            echo '</th>';
            echo '<th>';
            echo __('Comment');
            echo '</th>';
            echo '</tr>';
            
            while ($row = $result->fetch()) {
                echo '<tr>';
                echo '<td>';
                echo Format::date($row['date']);
                echo '</td>';
                echo '<td>';
                echo $row['descriptor'];
                echo '</td>';
                echo '<td>';
                echo $row['level'];
                echo '</td>';
                echo '<td>';
                echo Format::name('', $row['preferredName'], $row['surname'], 'Staff');
                echo '</td>';
                echo '<td>';
                echo $row['comment'];
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        
        return ob_get_clean();
    }
}
