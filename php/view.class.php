<?php

require('php/csv.class.php');

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
        include("php/views/form.phtml");
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
    
        include("php/views/result.phtml");
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
            $t = 'actions';
            $t2 = 'modalites';
        } else {
            $t = 'modalites';
            $t2 = 'actions';
        }
        //Parcours les calendriers
        foreach ($data as $calName => $calData) {
            print("<h3>"."$calName (".($calData['duration']/3600)."h) </h3>");
            //Parcours les périodes (jours, semaines, mois, années)
            foreach ($calData as $slotName => $slotData) {
                if($slotName == 'duration') continue;
                print("<h4>"."$slotName (".($slotData['duration']/3600)."h)</h4>");

                //Parcours les codes (actions)
                foreach ($slotData[$t] as $code => $subData) {
                    if($code == 'duration') continue;
                    print($code ." : ".$GLOBALS[$t][$code]['Intitulé']
                                ." (".($subData['duration']/3600)."h)\n");
                    print("<ul>");
                    //Parcours les souscodes (modalités)
                    foreach ($subData as $subCode => $duration) {
                        if($subCode == 'duration') continue;
                        print("<li>".$subCode." : "
                            .$GLOBALS[$t2][$subCode]['Intitulé']." ("
                            .($duration/3600)."h) </li>\n");
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
                foreach ($slotData['actions'] as $code => $subData) {
                    if ($code == 'duration') 
                        continue;
                    //Parcours les souscodes (modalités)
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

}
