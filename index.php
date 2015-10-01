<?php
/**
 * index file
 * 
 * @author Loris Puech
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 * 
 * @version GIT: $Id$
 *  
 */


include "./php/model.class.php";
include "./php/view.class.php";
include "./data/actions.php";
include "./data/modalites.php";

$model = new Model("./config.php");
$view = new View($model);

$action = "";
if (isset($_POST["action"]))
	$action = $_POST["action"];

switch ($action) {
	case "Show":
		if(isset($_POST["ics"]))
			$view->showResults($_POST["ics"], 
							   $_POST["startDate"], 
							   $_POST["endDate"],
							   $_POST["export"]);
		else
			print ("Aucun fichier n'a été sélectionné");
		break;

	case "Export":
		if(isset($_POST["ics"]))
			$view->showCsv($_POST["ics"],
                           $_POST["startDate"],
                           $_POST["endDate"],
                           $_POST["export"]);
		else
			print ("Aucun fichier n'a été sélectionné");
		break;

	case "Realised":
		$_POST['codes'] = array('Tous');
		if(isset($_POST["ics"]))
			$view->showRealised($_POST["ics"],
                           $_POST["date"]);
		else
			print ("Aucun fichier n'a été sélectionné");
		break;

	case "tableauAction":
		$view->exportTableauCDG("actions", isset($_POST["showArchived"]));
		break;
	
	case "tableauModalite":
		$view->exportTableauCDG("modalites");
		break;

	default:
		$view->showForm();
		break;
}
