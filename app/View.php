<?php
namespace CDGNG;

/**
 * Class View
 *
 * @author Loris Puech
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 *
 * @version GIT: $Id$
 */
class View
{

    private $model;

    /**
     * Constructor
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /************************************************************************
     * Show Form
     ************************************************************************/
    public function showForm()
    {
        include("app/views/form.phtml");
    }

    /**
     * Show Result as HTML
     *
     * @param
     * @param
     */
    public function showResults($cal_path, $ts_start, $ts_end, $slotTime = "All")
    {
        if($this->model->strToTime($ts_end) < $this->model->strToTime($ts_start))
            list($ts_start,$ts_end) = array($ts_end,$ts_start); //swap

        $this->model->analyseCal($cal_path, $ts_start, $ts_end);
        $total = $this->model->getTotal();
        $errors = $this->model->getErrors();

        include("app/views/result.phtml");
    }

    /**
     * Export data array
     * @param string $data array name to export
     * @param bool $show_archived true
     */
    public function exportTableauCDG($data, $show_archived = TRUE)
    {

        $tab = $GLOBALS[$data];

        $csv = new CSV();
        $csv->Insert(array('Code', 'Intitulé', 'Description'));

        foreach ($tab as $code => $tab_code) {

            if (!isset($tab_code['Visible']) || $tab_code['Visible'] == 1
                                             || $show_archived) {
                $row = array(
                    $code,
                    $tab_code["Intitulé"],
                    $tab_code["Description"]
                );

                $csv->Insert($row);
            }
        }

        $csv->output($data);
    }

    /**
     * print data by period in a certain order
     *
     * @param string $type show result per actions or per modalites
     * @param string $slot define slot time : day, week, year, month, All
     */
    private function printCalendar($type = "actions", $slot = "All")
    {
        $data = $this->model->getData($slot);

        if($type == "actions") {
            $type2 = 'modalites';
        } else {
            $type = 'modalites';
            $type2 = 'actions';
        }
        //Parcours les calendriers
        foreach ($data as $calName => $calData) {
            print("<h3>"."$calName (".$this->format($calData['duration'])."h) </h3>");
            //Parcours les périodes (jours, semaines, mois, années)
            foreach ($calData as $slotName => $slotData) {
                if($slotName == 'duration') continue;
                print("<h4>"."$slotName (".$this->format($slotData['duration'])."h)</h4>");
                ksort($slotData[$type]);
                //Parcours les codes (actions)
                foreach ($slotData[$type] as $code => $subData) {
                    if($code == 'duration') continue;
                    print($code ." : ".$GLOBALS[$type][$code]['Intitulé']
                                ." (".$this->format($subData['duration'])."h)\n");
                    print("<ul>");
                    ksort($subData);
                    //Parcours les souscodes (modalités)
                    foreach ($subData as $subCode => $duration) {
                        if($subCode == 'duration') continue;
                        print("<li>".$subCode." : "
                            .$GLOBALS[$type2][$subCode]['Intitulé']." ("
                            .$this->format($duration)."h) </li>\n");
                    }
                    print("</ul>");
                }
            }
        }
    }


    /**
     *
     *
     */
    public function showCsv($paths, $ts_start, $ts_end, $slot = "All")
    {
        if ($this->model->strToTime($ts_end) < $this->model->strToTime($ts_start))
            list($ts_start,$ts_end) = array($ts_end,$ts_start); //swap

        $this->model->analyseCal($paths, $ts_start, $ts_end);

        $calendar_name = $this->model->getName();

        $data = $this->model->getData($slot);

        switch ($slot) {
            case 'day':
                $title = 'Date (YYYY/MM/DD)';
                break;
            case 'week':
                $title = 'Semaine (YYYY/SS)';
                break;
            case 'month':
                $title = 'Mois (YYYY/MM)';
                break;
            case 'year':
                $title = 'Année';
                break;
            default:
                $title = '';
        }

        $csv = new CSV();

        // Headers
        $header = array('Nom', 'Actions', 'Modalités', 'Temps(Min)');
        if ($title != "")
            array_splice($header, 1, 0, $title);

        $csv->Insert($header);

        foreach ($data as $calName => $calData) {
            foreach ($calData as $slotName => $slotData) {
                if ($slotName == 'duration')
                    continue;
                //Parcours les codes (actions)
                ksort($slotData['actions']);
                foreach ($slotData['actions'] as $code => $subData) {
                    if ($code == 'duration')
                        continue;
                    //Parcours les souscodes (modalités)
                    ksort($subData);
                    foreach ($subData as $subCode => $duration) {
                        if ($subCode == 'duration')
                            continue;

                        $row = array($calName, $code, $subCode, $duration/60);
                        if ($title != "")
                            array_splice($row, 1, 0, $slotName);

                        $csv->Insert($row);
                    }
                }
            }
        }

        $csv->Output($calendar_name);
    }

    public function showRealised($paths, $date)
    {
        $year = (int)explode('-', $date)[2];
        $month = (int)explode('-', $date)[1];

        if ($month <= 8)
            $year -= 1;

        $ts_start = "01-09-".($year);
        $ts_end = "31-08-".($year + 1);


        $this->model->analyseCal($paths, $ts_start, $ts_end);
        $calendar_name = $this->model->getName();
        $data = $this->model->getData('day');
        // On ne prend en compte que le premier élément.
        $data = reset($data);

        $csv = new CSV();

        // Headers
        $header = array(
            'Septembre', '', '', 'Octobre', '', '', 'Novembre', '', '',
            'Décembre', '', '', 'Janvier', '', '', 'Février', '', '',
            'Mars', '', '', 'Avril', '', '', 'Mai', '', '',
            'Juin', '', '', 'Juillet', '', '', 'Aout', '', '',
        );

        $day_name = array(
            'Mon' => 'L', 'Tue' => 'M', 'Wed' => 'M', 'Thu' => 'J',
            'Fri' => 'V', 'Sat' => 'S', 'Sun' => 'D',
        );

        $months = array(9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8);

        $csv->Insert($header);

        for ($day=1; $day <= 31; $day++) {
            $row = array();
            foreach ($months as $month) {
                if ($month <= 8)
                    $timestamp = mktime(0, 0, 0, $month, $day, $year + 1);
                else
                    $timestamp = mktime(0, 0, 0, $month, $day, $year);
                $date = date("Y/m/d", $timestamp);
                $add = array();
                // Vérifie si le jour existe dans le mois.
                if (date("m", $timestamp) != $month)
                    $add = array('', '', '');
                // Vérifie si des heures ont été contabilisée pour ce jour
                else if (isset($data[$date]['duration']))
                    $add = array(
                        $day,
                        $day_name[date("D", $timestamp)],
                        number_format($data[$date]['duration']/3600, 2, ',', ' ')
                    );
                else
                    $add = array($day, $day_name[date("D", $timestamp)], '');

                $row = array_merge($row, $add);
            }
            $csv->Insert($row);
        }
        $csv->Output($calendar_name);
    }

    /**
     * Print error list using template
     *
     * @param string $template template filename in /php/views/
     *
     */
    private function printError($template)
    {
        $errors = $this->model->getErrors();
        foreach ($errors as $cal_name => $cal_errors) {
             foreach ($cal_errors as $key => $value){
                include("php/views/".$template);
            }
        }
    }

    /**
     * Second to hour round to two after dot.
     *
     * @param string $template template filename in /php/views/
     *
     */
    private function format($seconds)
    {
        return round(($seconds/3600), 2);
    }

}
