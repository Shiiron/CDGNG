<?php
require("php/ical.class.php");
require("php/event.class.php");

/**
 * Class Model
 *
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 * 
 * @version GIT: $Id$
 */
class Model {

	private $config;
	public $actions;
	public $modalites;
	public $tabError;
	public $codes;

	/**
	 * Constructeur
	 * 
	 * @param string $config_path Path to config file
	 */
	function __construct($config_path) {
		include_once($config_path);

		$this->actions = array();
		$this->modalites = array();
		$this->tabError = array();
		$this->codes = array();
		include ('data/actions.php');
		include ('data/modalites.php');
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
	 * Return code from event summarize
	 * 
	 * @param array $event Event 
	 * 
	 * @return array {"modalité", "action"}
	 */
	private function getEventCode($event){
		
		$output = array();

		if(!preg_match(	
			'#\[(?<code>[0-9]+[A-z])\]#', $event["SUMMARY"], $tab_matches)) {
			return false;
		}

		$code = strtoupper($tab_matches['code']);

		//Unification de la syntaxe ( 4 charactères )
		if (strlen($code) == 2) {
			$code = "00" . $code;
		} else if (strlen($code) == 3) {
			$code = "0" . $code;
		}

		$output["mod"] = substr($code, -1);
		$output["act"] = substr($code,  0, -1);

		return $output;
	}

	/**
	 * Parse calendar and calculate code duration per day.
	 * 
	 * @param string $cal_path Path to calendar file
	 * @param int    $ts_start 
	 */

	private function parseCal($cal_path, $ts_start, $ts_end) {
		$ical = new ical();
		$ical->parse($cal_path);

		$tab_events = array();

		// Parcours l'ensemble des événements
		foreach ($ical->get_sort_event_list() as $event_desc) {
			
			$event = new Event($event_desc);

			// L'évènement est dans le créneau choisi
			if (($event->getStart() >= $ts_start)
				&& ($event->getEnd() <= $ts_end)) {
				
				if($event->isValid($tab_events, $this->codes, $error)){

					array_push($tab_events, $event);
					
					$code = $event->getCode();
					
					// Permet l'ajout du code et de la modalites 
					// dans un tableau pour le traitement dans la vue
					$this->addCode(
						$code["act"], 
						$code["mod"], 
						$event->getLength(), 
						$this->actions, 
						$this->codes['actions'], 
						$event
					);

					$this->addCode(
						$code["mod"], 
						$code["act"], 
						$event->getLength(), 
						$this->modalites, 
						$this->codes['modalites'], 
						$event
					);

					$this->total += $event->getLength();
				} else {
					$this->addToError($error[0], $event, $error[1]);
				}
			}
		}
	}

	private function addCode($code, $subcode, $event_duration, &$tab, $list_codes, $event){
		// Ajoute un code dans le tableau qui va bien
		if (!array_key_exists($code, $list_codes)) {
			$this->addToError(2,
							$event["SUMMARY"],
							"Code mal écrit : ".$code,
							$event["DTSTART"]["unixtime"], 
							$event["DTEND"]["unixtime"]);
		} else {
			// L'action a déjà été trouvée
			if (array_key_exists($code, $tab)) {

				$tab[$code]["total"] += $event_duration;

				//La modalité a déjà été rencontrée pour cette action
				if (array_key_exists($subcode, $tab[$code]['subcode'])) {
					$tab[$code]['subcode'][$subcode] += $event_duration;
				} else { //La modalité n'a pas encore été rencontrée.
					$tab[$code]['subcode'][$subcode] = $event_duration;
				}
			}
			// L'action n'a pas encore été rencontrée
			else {
				$tab[$code] = array(
					'total' => $event_duration,
					'subcode' => array($subcode => $event_duration )
					);
			}
		}

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
		foreach ($cal_path as $value) {
			$this->parseCal($value, $ts_start, $ts_end);
		}
	}

	/**
	 * read calendar name from calendar file
	 * 
	 * @param string $cal_path Path to calendar file
	 */
	function get_cal_name($cal_path) {
		$ical = new ical();
		$ical->parse($cal_path);
		$donnees = $ical->get_calender_data();
		if (isset($donnees["X-WR-CALNAME"])) {
			$nom = $donnees["X-WR-CALNAME"];
			return($nom);
		}
	}

	function addToError($level, $event, $description) {
		$this->tabError[] = array(
			"LEVEL" 	  => $level,
			"SUMMARY"	  => $event->getSummary(),
			"DESCRIPTION" => $description,
			"DTSTART" 	  => date("d-m-Y H:i", $event->getStart()),
			"DTEND"		  => date("H:i", 	   $event->getEnd())
		);
	}
	
	function getTabError(){
		return $this->tabError;
	}
	
	function getActions(){
		return $this->actions;
	}
	
	function getModalites(){
		return $this->modalites;
	}
	
	function getTotal(){
		return $this->total;
	}
	
	function getTabAction(){
		return $this->codes['actions'];
	}
	
	function getTabModalite(){
		return $this->codes['modalites'];
	}
}

?>
