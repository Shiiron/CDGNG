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
namespace CDGNG;

if (!is_dir('vendor')) {
    print('Vous devez executer "composer install".');
    exit;
}
$loader = require __DIR__ . '/vendor/autoload.php';

include "./data/actions.php";
include "./data/modalites.php";

$model = new Model("config.php", $GLOBALS['actions'], $GLOBALS['modalites']);
$model->loadCalendarsList();

$view = new View($model);

$action = "";
if (isset($_POST["action"])) {
    $action = $_POST["action"];
}
switch ($action) {
    case "Show":
    case "Export":
        if (isset($_POST["ics"])) {
            $tab = explode("-", $_POST["startDate"], 3);
            $dtstart = strtotime($tab[2] . "-" . $tab[1] . "-" . $tab[0]);

            $tab = explode("-", $_POST["endDate"], 3);
            $dtend = mktime(23, 59, 59, $tab[1], $tab[0], $tab[2]);

            switch($_POST["export"]) {
                case "day":
                    $stat = new Statistics\Day($dtstart, $dtend);
                    break;
                case "week":
                    $stat = new Statistics\Week($dtstart, $dtend);
                    break;
                case "month":
                    $stat = new Statistics\Month($dtstart, $dtend);
                    break;
                case "year":
                    $stat = new Statistics\Year($dtstart, $dtend);
                    break;
                default:
                    $stat = new Statistics\All($dtstart, $dtend);
                    break;
            }

            foreach ($_POST["ics"] as $calName) {
                $stat->add($model->calendars[$calName]);
            }

            if ($action === 'Show') {
                $view = new Views\Results($model, $stat);
            }

            if ($action === 'Export') {
                $view = new Views\CsvView(
                    $stat->title . '.csv',
                    $stat->exportAsCsv()
                );
            }

            $view->show();
            break;
        }
        print ("Aucun fichier n'a été sélectionné");
        break;

    case "Realised":
        $_POST['codes'] = array('Tous');
        if (isset($_POST["ics"])) {
            $view->showRealised($_POST["ics"], $_POST["date"]);
            break;
        }
        print ("Aucun fichier n'a été sélectionné");
        break;

    case "tableauAction":
        $view->exportTableauCDG("actions", isset($_POST["showArchived"]));
        break;

    case "tableauModalite":
        $view->exportTableauCDG("modalites");
        break;

    default:
        $view = new Views\Main($model);
        $view->show();
        break;
}
