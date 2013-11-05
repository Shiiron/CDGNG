<?php

require("php/ical.class.php");

class Model {

	private $config;
	public $actions;
	public $modalites;
	public $total;
	public $tabError;
	public $tabAction;
	public $tabModalite;

	/**********************************************************************
	 * constructeur
	 **********************************************************************/

	function __construct($config_path) {
		include_once($config_path);

		$this->actions = array();
		$this->modalites = array();
		$this->total = 0;
		$this->tabError = array();
		include ('php/data/actions.php');
		include ('php/data/modalites.php');
	}

	/**********************************************************************
	 * Permet de charger la liste des calendriers disponibles.
	 **********************************************************************/

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

	/*********************************************************************
	 * Transforme une date en timestamp
	 *********************************************************************/

	function strToTime($str) {
		$tab = explode("-", $str, 3);
		return strtotime($tab[2] . "-" . $tab[1] . "-" . $tab[0]);
	}

	function strToTime_EndDate($str) {
		$tab = explode("-", $str, 3);
		$time = mktime(23, 59, 59, $tab[1], $tab[0], $tab[2]);
		return $time;
	}

	/*********************************************************************
	 * Parse un calendrier et retourne ses stats.
	 *********************************************************************/

	function parseCal($cal_path, $ts_start, $ts_end) {
		//Résultat final a retouner.
		//$this->results = array();
		$ical = new ical();
		$ical->parse($cal_path);

		//Ne conserve que les dates dans l'intervalle de temps.
		$tab_event = array();

		foreach ($ical->get_sort_event_list() as $events) {

			// Gestion des DTXXX qui ne sont pas des tableaux
			// Dans les calendriers édités avec google ce ne sont pas des tableaux
			// Alors que dans ceux avec thunderbird c'est le cas.
			// --> On met tout le monde d'accord avec des tableaux...

			if (!is_array($events["DTSTART"])) {
				//$ts = $events["DTSTART"];
				$events["DTSTART"] = array(
					"unixtime" => $events["DTSTART"],
					"TZID" => "Europe/Paris",);
			}

			// Bug du calendrier, dis bug de Corinne,
			// Champione toute catégorie confondu de l'evenement sans fin.
			if (isset($events["DTEND"])) {
				if (!is_array($events["DTEND"])) {
					//$ts = $events["DTEND"];
					$events["DTEND"] = array(
						"unixtime" => $events["DTEND"],
						"TZID" => "Europe/Paris",);
				}

				// Fin des gestions des tableaux

				if (($events["DTSTART"]["unixtime"] >= $ts_start)
						&& ($events["DTEND"]["unixtime"] <= $ts_end)) {
					//Vérification des évènement qui se chevauchent
					foreach ($tab_event as $key => $value) {
						//Evenement se chevauchent
						if(  !(($value["DTEND"]["unixtime"]  <= $events["DTSTART"]["unixtime"])
							|| ($events["DTEND"]["unixtime"] <= $value["DTSTART"]["unixtime"]))){
							if(((preg_match('#\[(?<code>[0-9]+[A-z])\]#', $events["SUMMARY"]))
								&& (preg_match('#\[(?<code>[0-9]+[A-z])\]#', $value["SUMMARY"])))){
								$this->addToError(2,
													$events["SUMMARY"]."et".$value["SUMMARY"],
													"se superposent",
													$events["DTEND"]["unixtime"],
													$events["DTEND"]["unixtime"]
												);
							}
						}
					}
					array_push($tab_event, $events);
				}
			}
		}

		foreach ($tab_event as $event) {

			//Ignore event without SUMMARY
			if(!isset($event["SUMMARY"]))
				continue;

			// Test si l'évènement est récursif
			// Et si il l'est, le mettre dans le log des erreurs
			if (array_key_exists("RRULE", $event)) {
				$this->addToError(2,
									$event["SUMMARY"],
									"Évènement récursif",
									$event["DTSTART"]["unixtime"],
									$event["DTEND"]["unixtime"]
								);
			}

			//cherche le code type [AAAM]
			else if (preg_match('#\[(?<code>[0-9]+[A-z])\]#', $event["SUMMARY"], $tab_matches)) {

				$code = strtoupper($tab_matches['code']);

				//Unification de la syntaxe ( 4 charactères )
				if (strlen($code) == 2) {
					$code = "00" . $code;
				} else if (strlen($code) == 3) {
					$code = "0" . $code;
				}

				$event_duration = $event["DTEND"]["unixtime"]
						- $event["DTSTART"]["unixtime"];

				// Si l'événement dure + de 12h on met une erreur
				if ($event_duration >= 43200) {
					$this->addToError(1,
									$event["SUMMARY"],
									"Évènement trop long (+ de 12h)",
									$event["DTSTART"]["unixtime"],
									$event["DTEND"]["unixtime"]
									);
				} else {

					$modalite = substr($code, -1);
					$action = substr($code, 0, -1);
					
					// Permet l'ajout du code et de la modalites dans un tableau pour le traitement dans la vue
					$this->addCode($action, $modalite, $event_duration, $this->actions, $this->tabAction, $event);
					$this->addCode($modalite, $action, $event_duration, $this->modalites, $this->tabModalite, $event);

					$this->total += $event_duration;
				}
			} 
			else {
				$this->addToError(0,
								$event["SUMMARY"],
								"Pas de code",
								$event["DTSTART"]["unixtime"],
								$event["DTEND"]["unixtime"]
								);
			}
		}
	
		return true;
	}

	protected function addCode($code, $subcode, $event_duration, &$tab, $list_codes, $event){
		// Ajoute un code dans le tableau qui va bien
		if (!array_key_exists($code, $list_codes)) {
			$this->addToError(2,
							$event["SUMMARY"],
							"Code mal écrit",
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

	function get_cal_name($cal_path) {
		$ical = new ical();
		$ical->parse($cal_path);
		$donnees = $ical->get_calender_data();
		if (isset($donnees["X-WR-CALNAME"])) {
			$nom = $donnees["X-WR-CALNAME"];
			return($nom);
		}
	}

	function addToError($niveau, $summary, $text, $dateStart, $dateEnd) {
		$this->tabError[] =array("niveau" => $niveau,
									"summary" => $summary,
									"texte" => $text,
									"dateDebut" => date("d-m-Y H:i", $dateStart),
									"dateFin" => date("H:i", $dateEnd));
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
