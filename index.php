<?php
	include "./php/model.class.php";
	include "./php/view.class.php";
	include "./data/actions.php";
	include "./data/modalites.php";

	$_ = array();

	$model = new Model("./config.php");
	$view = new View($model);

	$action = "";
	if (isset($_POST["action"]))
		$action = $_POST["action"];

	switch ($action) {
		case "Montrer résultats":
			if(isset($_POST["ics"]))
				$view->showResults($_POST["ics"], 
								   $_POST["startDate"], 
								   $_POST["endDate"]);
			else
				print ("Aucun fichier n'a été sélectionné");
			break;

		case "Exporter":
			if(isset($_POST["ics"]))
				$view->showCsv($_POST["ics"],
	                           $_POST["startDate"],
	                           $_POST["endDate"],
	                           $_POST["export"]);
			else
				print ("Aucun fichier n'a été sélectionné");
			break;
		
		case "tableauAction":
			$view->exportTableauCDG();
			break;
		
		case "tableauModalite":
			$view->exportTableauCDG();
			break;

		default:
			include("php/views/header.phtml");
			$view->showForm();
			break;
	}
?>