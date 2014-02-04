<?php

require "php/calendar.class.php";

/**
 * Class Model
 *
 * @author Loris Puech
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 * 
 * @version GIT: $Id$
 */
class Model {

	private $config;
	private $calendars;


	/**
	 * Constructeur
	 * 
	 * @param string $config_path Path to config file
	 */
	function __construct($config_path) {
		include_once($config_path);
		$this->calendars = array();
	}

	/**
	 * Load calendars list
	 * 
	 * @return array Calendars list
	 */
	function getCalList() {

		$result = array();

		//Remplissage du tableau
		if ($handle = opendir($this->config['calendars_path'])) {
			while (false !== ($entry = readdir($handle))) {
				if (substr_compare($entry, ".ics", -4, 4, TRUE) == 0) {
					$path = $this->config['calendars_path'] . $entry;
					if (is_file($path)) {
						$result[] = $path;
					}
				}
			}
			closedir($handle);
		}

		//Tri du tableau
		sort($result, SORT_STRING);

		return $result;
	}

	/**
	 * Make timestamp from date string (start of day)
	 * 
	 * @param string $str date as string
	 * 
	 * @return int timestamp
	 */
	function strToTime($str) {
		$tab = explode("-", $str, 3);
		return strtotime($tab[2] . "-" . $tab[1] . "-" . $tab[0]);
	}

	/**
	 * Make timestamp from date string (End of day)
	 * 
	 * @param string $str date as string 
	 * 
	 * @return int timestamp
	 */
	function strToTime_EndDate($str) {
		$tab = explode("-", $str, 3);
		$time = mktime(23, 59, 59, $tab[1], $tab[0], $tab[2]);
		return $time;
	}

	/**
	 * Analyse one or more calendars
	 * 
	 * @param string $cal_path Path to calendar
	 * @param int    $ts_start Start of event timestamp
	 * @param int 	 $ts_end   End of event timestamp 
	 */
	function analyseCal($cal_path, $ts_start, $ts_end) {
		//Reset tab
		$this->actions = array();
		$this->modalites = array();
		$this->total = 0;
		$this->tabError = array();

		$ts_start = $this->strToTime($ts_start);
		$ts_end = $this->strToTime_EndDate($ts_end);

		//Parse each calendar		
		foreach ($cal_path as $path) {
			$cal = new Calendar($path);
			$name = $cal->getname();
			$this->calendars[$name] = $cal;
			$this->calendars[$name]->parse($ts_start, $ts_end);
		}
	}
	
	/**
	 * Returns errors for all calendars
	 * 
	 * @return array All calendars errors
	 */
	function getErrors(){
		$output = array();
		foreach ($this->calendars as $name => $calendar) {	
			$output[$calendar->getName()] = $calendar->getErrors();
		}
		return $output;
	}

	/**
	 * Return calendar's data
	 * 
	 * @param string $type Result by action or by modalites (byAction, byModalite)
	 * @param string $slot define time slot (day, week, month, year, all)
	 * @param bool $fusion Définis si il faut fusionner les données des différents calendriers
	 */

	function getData($slot = "All", $fusion = false){
		$output = array();
		if(!$fusion){
			foreach ($this->calendars as $name => $calendar) {	
				$output[$calendar->getName()] = $calendar->getData($slot);
				$output[$calendar->getName()]['duration'] = $calendar->getTotalLength();
			}
		} else {
			//TODO : need addCal dans Calendar
		}
		return $output;
	}
	
	/**
	 * Calculate sum of duration of all valid events
	 * 
	 * @return int Duration of all valid events for all selected calendars 
	 */
	function getTotal(){
		$total = 0;

		foreach ($this->calendars as $name => $calendar) {	
			$total += $calendar->getTotalLength();
		}
		return $total;
	}

	/**
	 * Return name of selected calendars
	 * 
	 * @return string
	 */
	function GetName(){
		$output = "";
		foreach ($this->calendars as $name => $calendar) {	
			$output .= $calendar->getName().", ";
		}
		return substr($output, 0, -2);
	}
}

?>