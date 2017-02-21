<?php
namespace CDGNG;

/**
 * Class Model
 *
 * @author Loris Puech
 * @author Florestan Bredow <florestan.bredow@daiko.fr>
 *
 * @version GIT: $Id$
 */
class Model
{
    private $config;
    public $calendars = array();
    public $actions = array();
    public $modes = array();

    /**
     * Constructeur
     *
     * @param string $configPath Path to config file
     */
    public function __construct($configPath, $actions, $modes)
    {
        include_once($configPath);
        $this->actions = $actions;
        $this->modes = $modes;
    }

    /**
     * Load calendars list
     *
     * @return array Calendars list
     */
    public function loadCalendarsList()
    {
        $this->calendars = array();
        $calPath = $this->config['calendars_path'];

        //Remplissage du tableau
        if ($handle = opendir($calPath)) {
            while (false !== ($entry = readdir($handle))) {
                if (substr_compare($entry, ".ics", -4, 4, true) === 0) {
                    $path = $calPath . $entry;
                    if (is_file($path)) {
                        $calendar = new Calendar($path);
                        $this->calendars[$calendar->name] = $calendar;
                    }
                }
            }
            closedir($handle);
        }
        //Tri du tableau
        ksort($this->calendars);
    }

    /**
     * Returns errors for all calendars
     *
     * @return array All calendars errors
     */
    public function getErrors()
    {
        $output = array();
        foreach ($this->calendars as $name => $calendar) {
            $output[$name] = $calendar->getErrors();
        }
        return $output;
    }

    /**
     * Return name of selected calendars
     *
     * @return string
     */
    public function getName()
    {
        $output = "";
        foreach ($this->calendars as $calendar) {
            $output .= $calendar->name."+";
        }
        return substr($output, 0, -1);
    }

    /**
     * Make timestamp from date string (start of day)
     *
     * @param string $str date as string
     *
     * @return int timestamp
     */
    private function strToTime($str)
    {
        $tab = explode("-", $str, 3);
        return strtotime($tab[2] . "-" . $tab[1] . "-" . $tab[0]);
    }

    /**
     * Make timestamp from date string (End of day)
     *
     * @param string $str date as string
     *
     * @return int timestamp
     */
    private function strToTimeEndDate($str)
    {
        $tab = explode("-", $str, 3);
        $time = mktime(23, 59, 59, $tab[1], $tab[0], $tab[2]);
        return $time;
    }

}
