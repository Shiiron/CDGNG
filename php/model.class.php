<?php
require("php/ical.class.php");

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
	public $tabAction;
	public $tabModalite;


	public $perDay;

	public $tab_days;
	public $tab_bad_events;

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
		include ('php/data/actions.php');
		include ('php/data/modalites.php');
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
		foreach ($ical->get_sort_event_list() as $event) {

			$event = $this->cleanEvent($event);

			// L'évènement est dans le créneau choisi
			if (($event["DTSTART"]["unixtime"] >= $ts_start)
				&& ($event["DTEND"]["unixtime"] <= $ts_end)) {
				
				if($this->isEventValid($event, $tab_events)){

					array_push($tab_events, $event);
					
					$code = $this->getEventCode($event);
					$event_duration = $event["DTEND"]["unixtime"]
									- $event["DTSTART"]["unixtime"];
					
					// Permet l'ajout du code et de la modalites 
					// dans un tableau pour le traitement dans la vue
					$this->addCode(
						$code["act"], 
						$code["mod"], 
						$event_duration, 
						$this->actions, 
						$this->tabAction, 
						$event
					);

					$this->addCode(
						$code["mod"], 
						$code["act"], 
						$event_duration, 
						$this->modalites, 
						$this->tabModalite, 
						$event
					);

					$this->total += $event_duration;
				}
			}
		}
	}


	/**
	 * Uniformise la forme des évènements.
	 */
	private function cleanEvent($event){

		// Gestion des DTXXX qui ne sont pas des tableaux
		// Dans les calendriers édités avec google ce ne sont pas des tableaux
		// Alors que dans ceux avec thunderbird c'est le cas.
		// --> On met tout le monde d'accord avec des tableaux...

		if (!is_array($event["DTSTART"])) {
			$event["DTSTART"] = array(
				"unixtime" 	=> $event["DTSTART"],
				"TZID" 		=> "Europe/Paris",);
		}

		// Certains évènement n'ont pas de fin.
		if (isset($event["DTEND"])) {
			if (!is_array($event["DTEND"])) {
				//$ts = $events["DTEND"];
				$event["DTEND"] = array(
					"unixtime" 	=> $event["DTEND"],
					"TZID" 		=> "Europe/Paris",);
			}
		}

		return $event;
	}

	/**
	 * check if an event is valid
	 * 
	 * @param array $event event to check
	 * @param array $tab_events array of events already tested.
	 * 
	 * @todo ignore "on day" events
	 * 
	 */
	private function isEventValid($event, $tab_events){
		
		// Event without end
		if (!isset($event["DTEND"]))
			return false; //Ignored

		// Event without summary
		if(!isset($event["SUMMARY"]))
			return false; //Ignored

		// Recurcive event
		if (array_key_exists("RRULE", $event)) {
			$this->addToError(2, $event, "Récursif");
			return false;
		}

		$code = $this->getEventCode($event);

		// Uncoded event
		if ($code == false) {
			$this->addToError(0, $event, "Pas de code");
			return false;
		}

		// bad code
		if (!array_key_exists($code["mod"], $this->tabModalite)) {
			$this->addToError(2, $event, "Mauvais code (modalité)");
			return false;
		}

		// bad code
		if (!array_key_exists($code["act"], $this->tabAction)) {
			$this->addToError(2, $event, "Mauvais code (action)");
			return false;
		}

		// Event longer then 12 hours
		if ($event["DTEND"]["unixtime"] - $event["DTSTART"]["unixtime"] >= 43200) {
			$this->addToError(1, $event, "Trop long (+ de 12h)");
			return false;
		}

		// Superposed event.
		foreach ($tab_events as $key => $event2) {
			//Evenement se chevauchent
			if( !( ($event2["DTEND"]["unixtime"] 
					<= $event["DTSTART"]["unixtime"])
				|| ($event["DTEND"]["unixtime"]  
					<= $event2["DTSTART"]["unixtime"])
				 )){
				
				$this->addToError(2, $event, 
								  "se superpose à ".$event2["SUMMARY"]);
				return false;
			}
		}

		// Everything go right.
		return true;
	}


	/**
	 * 
	 * 
	 * 
	 */
	/*private function addCode($code, $ts_start, $ts_end) {


	}/**/

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
			"SUMMARY"	  => $event["SUMMARY"],
			"DESCRIPTION" => $description,
			"DTSTART" 	  => date("d-m-Y H:i", $event["DTSTART"]["unixtime"]),
			"DTEND"		  => date("H:i", 	   $event["DTEND"]["unixtime"])
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
		return $this->tabAction;
	}
	
	function getTabModalite(){
		return $this->tabModalite;
	}
}

?>
