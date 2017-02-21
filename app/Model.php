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

        ksort($this->calendars);
    }
}
