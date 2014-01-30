<?php

require("php/ical.class.php");
require("php/event.class.php");

class Calendar{

	private $path;
	private $totalLength;
	private $dailyTime;
	private $errors;

	function __construct($path){
		$this->path = $path;
		$this->totalLength = 0;
		$dailyTime = array();
		$errors = array();
	}

	function parse($ts_start, $ts_end){

		$ical = new ical();
		$ical->parse($this->path);

		$tab_events = array();

		// Parcours l'ensemble des événements
		foreach ($ical->get_sort_event_list() as $event_desc) {
			
			$event = new Event($event_desc);

			// L'évènement est dans le créneau choisi
			if (($event->getStart() >= $ts_start)
				&& ($event->getEnd() <= $ts_end)) {
				
				if($event->isValid($tab_events, $error)){

					array_push($tab_events, $event);
					$this->addEvent($event);

				} else {

					$this->addToError($error[0], $event, $error[1]);

				}
			}
		}

		print("<pre>");
		var_dump($this->dailyTime);
		print("</pre>");
	}
	
	private function addEvent($event){
		$code = $event->getCode();
		$result = $event->cutByDay();

		foreach ($result as $day => $time) {
			if (isset($this->dailyTime[$day][$code["act"]][$code["mod"]])){
				$this->dailyTime[$day][$code["act"]][$code["mod"]] += $time;
			} else {
				$this->dailyTime[$day][$code["act"]][$code["mod"]] = $time;
			}

		}
		$this->totalLength += $event->getLength();
	}


	function addToError($level, $event, $description) {
		$this->errors[] = array(
			"LEVEL" 	  => $level,
			"SUMMARY"	  => $event->getSummary(),
			"DESCRIPTION" => $description,
			"DTSTART" 	  => date("d-m-Y H:i", $event->getStart()),
			"DTEND"		  => date("H:i", 	   $event->getEnd())
		);
	}

	/**
	 * return calendar name from calendar file
	 */
	function getName(){
		$ical = new ical();
		$ical->parse($this->path);
		$donnees = $ical->get_calender_data();
		if (isset($donnees["X-WR-CALNAME"])) {
			$nom = $donnees["X-WR-CALNAME"];
			return($nom);
		}
	}

	function getPath(){
		return $path;
	}

	function getTotalLength(){
		return $this->totalLength;
	}


}