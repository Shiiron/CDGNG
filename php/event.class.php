<?php

/**
 * Class Event
 * 
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 * 
 * @version GIT: $Id$
 * 
 */
class Event {

	/*************************************************************************
	 * STATES
	 ************************************************************************/

	/**
	 * Event's description from ical Class
	 */
	private $e;

	/*************************************************************************
	 * METHODS
	 ************************************************************************/

	/**
	 * Constructor
	 * 
	 * @param array $event Event's description from ical Class
	 * 
	 */
	function __construct($event){
		$this->load($event);
	}

	/**
	 * Load event from ical class description array
	 * 
	 * @param array $event Event's description from ical Class
	 */
	function load($event){
		$this->e = $event;
		$this->standardize();
	}

	/**
	 * Standardize description
	 */
	private function standardize(){
		// Gestion des DTXXX qui ne sont pas des tableaux
		// Dans les calendriers édités avec google ce ne sont pas des tableaux
		// Alors que dans ceux avec thunderbird c'est le cas.
		// --> On met tout le monde d'accord avec des tableaux...

		if (!is_array($this->e["DTSTART"])) {
			$this->e["DTSTART"] = array(
				"unixtime" 	=> $this->e["DTSTART"],
				"TZID" 		=> "Europe/Paris",);
		}

		// Certains évènement n'ont pas de fin.
		if (isset($this->e["DTEND"])) {
			if (!is_array($this->e["DTEND"])) {
				//$ts = $events["DTEND"];
				$this->e["DTEND"] = array(
					"unixtime" 	=> $this->e["DTEND"],
					"TZID" 		=> "Europe/Paris",);
			}
		}
	}


	/**
	 * check if event action code is in selection
	 * 
	 * @return true or false.
	 */
	function isSelected() {
		if(in_array('Tous', $_POST['codes']))
			return True;

		$code = $this->getCode()['act'];

		if(in_array($code, $_POST['codes']))
			return True;

		return false;
	}

	/**
	 * check if this is a full day event
	 * 
	 * @return true or false.
	 */
	function isFullDay() {
		if ($this->getLength() == 86400)
			return true;
		return false;
	}

	/**
	 * check if event is valid
	 * 
	 * @param array $events Array of events object to check superposed
	 * @param array $error  writable to return error on unvalid event
	 * 
	 * @return true or false.
	 */
	function isValid($events, &$error) {

		// Event without end
		if (!isset($this->e["DTEND"])) {
			$error = array(99, "Sans fin.");
			return false; //Ignored
		}

		// Event without summary
		if(!isset($this->e["SUMMARY"])) {
			$error = array(99, "Sans titre.");
			return false; //Ignored
		}

		// Recurcive event
		if (array_key_exists("RRULE", $this->e)) {
			$error = array(2, "Récursif.");
			return false;
		}

		$code = $this->getCode();

		// Uncoded event
		if ($code == false) {
			$error = array(0, "Sans code.");
			return false;
		}

		// bad code
		if (!array_key_exists($code["mod"], $GLOBALS['modalites'])) {
			$error = array(2, "Mauvais code (modalité).");
			return false;
		}

		// bad code
		if (!array_key_exists($code["act"], $GLOBALS['actions'])) {
			$error = array(2, "Mauvais code (action).");
			return false;
		}

		// Event longer then 12 hours
		if ($this->getLength() >= 43200) {
			$error = array(1, "Trop long (+ de 12h)");
			return false;
		}

		// Superposed event.
		foreach ($events as $event) {
			if($this->isOverlap($event)) {
				$error = array(2, "se superpose à ".$event->getSummary().".");
				return false;
			}
		}

		// Everything go right.
		return true;
	}

	/**
	 * Check if two events are overlapping
	 * 
	 * @param Event $event Event object like this
	 */
	private function isOverlap($event){
		if( !( ($event->getEnd() <= $this->getStart())
			|| ($this->getEnd() <= $event->getStart()) )){
			return true;
		}

		return false;
	}

	/**
	 * Return Code of event
	 * 
	 * @return array {"modalite" => "X", "action" => "ZZZ"}
	 */
	function getCode(){

		if(!preg_match(	
			'#\[(?<code>[0-9]+[A-z])\]#', $this->getSummary(), $tab_matches)) {
			return false;
		}

		$code = strtoupper($tab_matches['code']);

		//Unification de la syntaxe ( 4 charactères )
		if (strlen($code) == 2) {
			$code = "00" . $code;
		} else if (strlen($code) == 3) {
			$code = "0" . $code;
		}

		return array(
			"mod" => substr($code, -1),
			"act" => substr($code,  0, -1),
		);
	}

	function getSummary(){
		if(isset($this->e["SUMMARY"]))
			return $this->e["SUMMARY"];
		return "No Summary";
	}

	function getStart(){
		return $this->e["DTSTART"]["unixtime"];
	}

	function getEnd(){
		if (isset($this->e["DTEND"]["unixtime"]))
			return $this->e["DTEND"]["unixtime"];
		//If no end defined
		return $this->e["DTSTART"]["unixtime"];
	}

	function getLength(){
		return $this->getEnd() - $this->getStart();
	}

	/**
	 * 
	 * @todo
	 *
	 */
	function cutByDay(){

		$output = array();

		//definir le TS du jour 1
		$curDate = $this->getStart();
		while($curDate < $this->getEnd()){
			$t = getdate($curDate);
			$dayStart = mktime(0, 0, 0, $t["mon"], $t["mday"], $t["year"]);
			$dayEnd = mktime(23, 59, 59, $t["mon"], $t["mday"], $t["year"]);

			// Event end after end of day.
			if($dayEnd < $this->getEnd()){
				$output[$dayStart] = $dayEnd - $curDate;
			} else {
				$output[$dayStart] = $this->getEnd() - $curDate;
			}
			$curDate = mktime(0, 0, 0, $t["mon"], $t["mday"] + 1, $t["year"]);; // one more day;
		}

		$this->getStart();

		return $output;

	}

	

}
