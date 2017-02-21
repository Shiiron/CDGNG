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
        if (isset($_POST["ics"])) {
            $_POST['codes'] = array('Tous');
            $stat = new Statistics\Realised($_POST["date"]);
            foreach ($_POST["ics"] as $calName) {
                $stat->add($model->calendars[$calName]);
            }
            $view = new Views\CsvView(
                $stat->title . '_realised.csv',
                $stat->exportAsCsv()
            );
            $view->show();
            break;
        }
        print ("Aucun fichier n'a été sélectionné");
        break;

    case "tableauAction":
        if (isset($_POST["showArchived"])) {
            $csv = $model->exportActionsWithArchivedToCsv();
        }

        if (!isset($_POST["showArchived"])) {
            $csv = $model->exportActionsNoArchivesToCsv();
        }

        $view = new Views\CsvView('action.csv', $csv);
        $view->show();
        break;

    case "tableauModalite":
        $view = new Views\CsvView('modalites.csv', $model->exportModesToCsv());
        $view->show();
        break;

    default:
        $view = new Views\Main($model);
        $view->show();
        break;
}
