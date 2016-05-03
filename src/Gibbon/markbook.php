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

namespace Gibbon;

/**
 * Markbook display & edit class
 *
 * @version	3rd May 2016
 * @since	3rd May 2016
 * @author	Sandra Kuipers
 */
class markbook
{

	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo ;
	
	/**
	 * Gibbon\session
	 */
	private $session ;
	
	/**
	 * Gibbon\config
	 */
	private $config ;

	/**
	 * Gibbon Settings
	 */
	//private $enableColumnWeighting;
	//private $attainmentAlternativeName;
	//private $attainmentAlternativeNameAbrev;
	//private $effortAlternativeName;
	//private $effortAlternativeNameAbrev;

	private $settings = array();

	/**
	 * Markbook Class Settings - readonly
	 */
	private $columnsPerPage = 12;
	private $columnsThisPage;

	/**
     * Constructor
     *
     * @version  3rd May 2016
     * @since    3rd May 2016
     * @param    Gibbon\session
     * @param    Gibbon\config
     * @param    Gibbon\sqlConnection
     * @return   void
     */
    public function __construct(\Gibbon\session $session = NULL, \Gibbon\config $config = NULL, \Gibbon\sqlConnection $pdo = NULL)
    {
        if ($session === NULL)
            $this->session = new \Gibbon\session();
        else
            $this->session = $session ;

        if ($config === NULL)
            $this->config = new \Gibbon\config();
        else
            $this->config = $config ;

        if ($pdo === NULL)
            $this->pdo = new \Gibbon\sqlConnection();
        else
            $this->pdo = $pdo ;

        //Get alternative header names
		$this->settings['enableColumnWeighting'] = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'enableColumnWeighting');
		
		$attainmentAltName = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'attainmentAlternativeName');
		$attainmentAltNameAbrev = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'attainmentAlternativeNameAbrev');
		$effortAltName = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'effortAlternativeName');
		$effortAltNameAbrev = getSettingByScope($this->pdo->getConnection(), 'Markbook', 'effortAlternativeNameAbrev');

		$this->settings['attainmentName'] = (!empty($attainmentAltName))? $attainmentAltName : __($this->config->get('guid'), 'Attainment');
		$this->settings['attainmentAbrev'] = (!empty($attainmentAltNameAbrev))? $attainmentAltNameAbrev : __($this->config->get('guid'), 'Att');

		$this->settings['effortName'] = (!empty($effortAltName))? $effortAltName : __($this->config->get('guid'), 'Effort');
		$this->settings['effortAbrev'] = (!empty($effortAltNameAbrev))? $effortAltNameAbrev : __($this->config->get('guid'), 'Eff');
    }

    public function getSetting( $key ) {
    	return (isset($this->setting[$key]))? $this->setting[$key] : NULL;
    }

    public function getColumnsPerPage() {
    	return $this->columnsPerPage;
    }

    public function getColumnCountThisPage() {
        return $this->columnsThisPage;
    }

    public function getColumns( $gibbonCourseClassID, $columnCount, $pageNum ) {

    	try {
    		$data = array('gibbonCourseClassID' => $gibbonCourseClassID);
    		$where = $this->getColumnFilters();

    		$sql = 'SELECT * FROM gibbonMarkbookColumn WHERE '.$where.' ORDER BY sequenceNumber, complete, completeDate DESC LIMIT '.($pageNum * $this->columnsPerPage).', '.$this->columnsPerPage;

    		//echo $sql;

	        $result=$this->pdo->executeQuery($data, $sql);
	    } catch (PDOException $e) {
	        echo "<div class='error'>".$e->getMessage().'</div>';
	    }

	    $this->columnsThisPage = $result->rowCount();

	    return $result;
    }

    public function getColumnCount( $gibbonCourseClassID ) {

    	try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $where = $this->getColumnFilters();
            $sql = 'SELECT count(*) FROM gibbonMarkbookColumn WHERE '.$where;
            $result=$this->pdo->executeQuery($data, $sql);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        return ($result->rowCount() > 0)? $result->fetchColumn() : 0;
    }

    private function getColumnFilters() {

    	$where = 'gibbonCourseClassID=:gibbonCourseClassID';

    	$gibbonSchoolYearTermID = (isset($_GET['gibbonSchoolYearTermID']))? $_GET['gibbonSchoolYearTermID'] : '';
        $columnFilter = (isset($_GET['columnFilter']))? $_GET['columnFilter'] : '';

    	if (!empty($gibbonSchoolYearTermID)) {

        	try {
		        $data=array("gibbonSchoolYearTermID"=>$gibbonSchoolYearTermID );
		        $sql="SELECT firstDay, lastDay FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID" ;
		        $resultTerms=$this->pdo->executeQuery($data, $sql);
		    }
		    catch(PDOException $e) { }

		    if ($resultTerms->rowCount() > 0) {
		    	$termRow = $resultTerms->fetch();
        		$where .= " AND (date IS NOT NULL AND date BETWEEN '".$termRow['firstDay']."' AND '".$termRow['lastDay']."' )";
        	}
        }

        if (!empty($columnFilter)) {
        	switch ($columnFilter) {
        		case 'marked':		$where .= " AND complete = 'Y'"; break;
        		case 'unmarked':	$where .= " AND complete = 'N'"; break;
        		case 'week':		$where .= " AND WEEKOFYEAR(date)=WEEKOFYEAR(NOW())"; break;
        		case 'month':		$where .= " AND MONTH(date)=MONTH(NOW())"; break;
        	}
        }

        return $where;
    }



}

?>