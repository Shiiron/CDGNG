<?php
	include("./php/model.php");
	include("./php/view.php");

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
				print ("Aucun fichier n'as été sélectionner");
			break;

		case "Exporter":
			$view->showCsv($_POST["ics"],
                          ($_POST["startDate"]),
                          ($_POST["endDate"]));
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