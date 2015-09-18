<?php

require("php/ical.class.php");
require("php/event.class.php");


/**
 * Class Calendar
 * 
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 * 
 * @version GIT: $Id$
 *  
 */
class Calendar{

	/**
	 * Path to calendar file
	 * @var string $path
	 */
	private $path;
	/**
	 * Total duration for all valid events
	 * @var int $totalLength
	 */
	private $totalLength;
	/**
	 * Time per day per action/code [day][action][modalite][length]
	 * @var array $dailyTime
	 */
	private $dailyTime;
	/**
	 * List of bad events with error type [level][summary][description]
	 * @var array $errors
	 */
	private $errors;


	function __construct($path){
		$this->path = $path;
		$this->totalLength = 0;
		$dailyTime = array();
		$errors = array();
	}

	/**
	 * Parse calendar and check events
	 * 
	 * @param timestamp $ts_start time slot start
	 * @param timestamp $ts_end time slot end
	 */ 

	function parse($ts_start, $ts_end){

		$ical = new ical();
		$ical->parse($this->path);

		$tab_events = array();

		foreach ($ical->get_sort_event_list() as $event_desc) {
			
			$event = new Event($event_desc);

			// Event is in time slot
			if (($event->getStart() >= $ts_start)
				&& ($event->getEnd() <= $ts_end)) {
				
				if($event->isValid($tab_events, $error)){

					array_push($tab_events, $event);
					$this->addEvent($event);

				} else {
					// 0 -> level ; 1 -> description
					$this->addToError($error[0], $event, $error[1]);

				}
			}
		}
	}

	/**
	 * Add event to valid event array
	 * 
	 * @param Event $event Event to add
	 */
	
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

	/**
	 * Add error to error array
	 * 
	 * @param int 	 $level 		Error level (0-2)
	 * @param Event  $event 		Unvalid event
	 * @param string $description   Error description
	 * 
	 */
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
	 * Return calendar name from calendar file (X-WR-CALNAME field)
	 * 
	 * @return string
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

	function getData($slot="total"){
		switch ($slot) {
			case 'day':
				/*ksort($this->dailyTime);
				return $this->dailyTime;*/
				return $this->getDataBy("Y/m/d");
				break;
			
			case 'week':
				return $this->getDataBy("Y/W");
				break;

			case 'month':
				return $this->getDataBy("Y/m");
				break;

			case 'year':
				return $this->getDataBy("Y");
				break;

			default:
				return $this->getDataBy("All");
				break;
		}	
	}

	/**
	 * return data grouped by slot time
	 * 
	 * @param string $format Use date(format) cf. http://php.net/manual/en/function.date.php
	 * 
	 * @return array
	 */
	private function getDataBy($format){
		$output = array();

		$output['duration'] = 0;


		foreach ($this->dailyTime as $date => $dayCodes) {
			if($format == "All")
				$slot = "All";
			else
				$slot = date($format, $date);
			if(!isset($output[$slot]['duration']))
				$output[$slot]['duration'] = 0;
			foreach ($dayCodes as $action => $subCode) {
				
				//$output[$slot]['actions'][$action]['duration'] = 0;
				foreach ($subCode as $modalite => $duration) {
					if(		isset($output[$slot]['actions'][$action][$modalite])){
				//		&&	isset($output[$slot]['m'][$modalite][$action]){  //Inutile
						$output[$slot]['actions'][$action][$modalite] += $duration;
						$output[$slot]['modalites'][$modalite][$action] += $duration;
					} else {
						$output[$slot]['actions'][$action][$modalite] = $duration;
						$output[$slot]['modalites'][$modalite][$action] = $duration;
					}
					//$output[$slot]['actions'][$action]['duration'] 	+= $duration;
					if(isset($output[$slot]['actions'][$action]['duration']))
						$output[$slot]['actions'][$action]['duration'] += $duration;
					else
						$output[$slot]['actions'][$action]['duration'] = $duration;

					if(isset($output[$slot]['modalites'][$modalite]['duration']))
						$output[$slot]['modalites'][$modalite]['duration'] 	+= $duration;
					else
						$output[$slot]['modalites'][$modalite]['duration'] = $duration;


					$output[$slot]['duration'] += $duration;
					$output['duration'] += $duration;
				}
			}
		}

		ksort($output);
		return $output;
	}

	/**
	 * Return calendar's path
	 * 
	 * @return string
	 */
	function getPath(){
		return $path;
	}

	/**
	 * Return all valid events's duration
	 * 
	 * @return string
	 */
	function getTotalLength(){
		return $this->totalLength;
	}

	function getErrors(){
		return $this->errors;
	}


}
