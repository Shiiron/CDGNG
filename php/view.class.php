<?php

class View {

	private $model;

	/************************************************************************
	 * Constructor
	 ************************************************************************/
	function __construct($model) {	
		$this->model = $model;
	}

	/************************************************************************
	 * Show Form
	 ************************************************************************/
	function showForm() {
		include("php/views/form.phtml");
		include("php/views/footer.phtml");
	}

	/************************************************************************
	 * Show Result as HTML
	 ************************************************************************/
	function showResults($cal_path, $ts_start, $ts_end) {
		if($this->model->strToTime($ts_end) < $this->model->strToTime($ts_start))
			list($ts_start,$ts_end) = array($ts_end,$ts_start); //swap
		
		include("php/views/header.phtml");
		$this->model->analyseCal($cal_path, $ts_start, $ts_end);
		$total = $this->model->getTotal();
		$tabError = $this->model->getTabError();
		include("php/views/result.phtml");
		include("php/views/footer.phtml");
	}

	/************************************************************************
	 * Show result as CSV File
	 ************************************************************************/
	function showCsv($tab_cal, $ts_start, $ts_end) {

		$nomCal = $this->nomCal($tab_cal);

		// File headers (this is not html)
		header("Content-type: application/vnd.ms-excel");
		header("Content-disposition: attachment; filename=$nomCal.csv");

		print("\"Nom\";\"Actions\";\"Modalités\";\"Temps(min)\"");

		foreach ($tab_cal as $key => $cal) {
			$this->model->analyseCal(array($cal), $ts_start, $ts_end);
			$tab_results = $this->model->getActions();
			$cal_name = $this->nomCal(array($cal));
			
			// Send file content
			
			foreach($tab_results as $action => $code){
				foreach($code['subcode'] as $subcode => $value){
					print("\n\"".$cal_name.'";"'.$action.'";"'.$subcode.'";'.($value/60));
				}
			}
			
		}
	}

	private function printTab($tab){
		foreach($tab as $code => $content){
			
			$hour = floor($content["total"] / 3600);
			$minute = ($content["total"] % 3600) / 60;
			
			if (array_key_exists($code, $this->model->getTabAction())){
				$tabAction = $this->model->getTabAction();
				$title = $tabAction[$code]['Intitulé'];
				
			}
			else{
				$tabModalite = $this->getTabModalite();
				$title = $tabModalite[$code]['Intitulé'];
			}
			
			print($title." (".$code.") : ".$hour."h ");
			
			if($minute > 0) print($minute."m ");
			print(" (".round(($content["total"]*100)/$this->model->getTotal(),2)."%)");
		
			print("<ul>");
			foreach($content as $subcode => $subcontent) {
				if($subcode != "total") {
					$hour = floor($content[$subcode] / 3600);
					$reste = $content[$subcode] % 3600;
					$minute = $reste / 60;

					if (array_key_exists($subcode, $this->model->getTabAction())){
						$tabAction = $this->model->getTabAction();
						$subtitle = $tabAction[$subcode]['Intitulé'];
					}
					else{
						$tabModalite = $this->model->getTabModalite();
						$subtitle = $tabModalite[$subcode]['Intitulé'];
					}
					
					print("<li>".$subtitle." (".$subcode.")"." : ".$hour."h ");
					
					if($minute > 0) 
						print($minute."m ");
					print("</li>");
				}
			}
			print("</ul>");
		}
	}


	//Fonction qui permet d'obtenir un tableau trier par ordre décroissant
	private function printTabDesc($tab) {

		while (!empty($tab)){
			
			$code = "";

			//Cherche la clé restante avec le total le plus grand 
			foreach ($tab as $key => $value) {
				if($code == "")
					$code = $key;

				if($tab[$code]["total"] < $value["total"])
					$code = $key;
			}

			$hour = floor($tab[$code]["total"] / 3600);
			$minute = ($tab[$code]["total"] % 3600) / 60;
			
			if (array_key_exists($code, $this->model->getTabAction())){
				$tabAction = $this->model->getTabAction();
				$title = $tabAction[$code]['Intitulé'];
			}
			else{
				$tabModalite = $this->model->getTabModalite();
				$title = $tabModalite[$code]['Intitulé'];
			}

			print($title." (".$code.") : ".$hour."h ");
			
			if($minute > 0) print($minute."m ");
			print(" (".round(($tab[$code]["total"]*100)/$this->model->getTotal(),2)."%)");
	
			print("<ul>");
			

			while(!empty($tab[$code]['subcode'])){
				$subcode = "";
								
				//Cherche la clé restante avec le total le plus grand 
				foreach ($tab[$code]['subcode'] as $key2 => $value2) {
					if($subcode == "")
						$subcode = $key2;

					if($tab[$code]['subcode'][$subcode] <= $value2)
						$subcode = $key2;
				}
							
				
				if($subcode != "total") {
					$hour = floor($tab[$code]['subcode'][$subcode] / 3600);
					$reste = $tab[$code]['subcode'][$subcode] % 3600;
					$minute = $reste / 60;

					if (array_key_exists($subcode, $this->model->getTabAction())){
						$tabAction = $this->model->getTabAction();
						$subtitle = $tabAction[$subcode]['Intitulé'];
					}
					else{
						$tabModalite = $this->model->getTabModalite();
						$subtitle = $tabModalite[$subcode]['Intitulé'];
					}
					print("<li>".$subtitle." (".$subcode.")"." : ".$hour."h ");
					
					if($minute > 0) 
						print($minute."m ");
					print("</li>");
				}

				unset($tab[$code]['subcode'][$subcode]);
				
			}

			print("</ul>");

			// Supprime la ligne déjà traité
			unset($tab[$code]);
		}
	}
	
	function printError(){
		$errorTab = $this->model->getTabError();
		
		foreach ($errorTab as $key => $value){
			include("php/views/error.phtml");
		}
		
		
	}
	
	function exportTableauCDG(){
		if($_POST["action"] == "tableauAction")
			$tab = $this->model->getTabAction();
		else
			$tab = $this->model->getTabModalite();
		
		include("php/views/csvTableauCDG.phtml");
	}
	
	// Fonction qui permet d'obtenir le nom du calendrier
	function nomCal($cal_path){
		$nomCal = "";
		foreach ($cal_path as $value) {
		// Elimination de l'extension .ics
		$tab_Explode = explode(".", $value);
		$pathCal = $tab_Explode[0];

		// Elimination du cal/ devant le nom du calendrier
		$tab_NomCal = explode("/", $pathCal);
		$nomCal .= $tab_NomCal[1]."+";
		}
		return substr($nomCal, 0, -1);
	}
}
	