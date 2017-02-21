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

    public function exportActionsNoArchivesToCsv()
    {
        $csv = new Csv();
        $csv->insert(array('Code', 'Intitulé', 'Description', 'Référent'));
        foreach ($this->actions as $code => $action) {
            if ($action['Visible'] === 1) {
                $csv->insert(
                    array(
                        $code,
                        $action['Intitulé'],
                        $action['Description'],
                        $action['Referent']
                    )
                );
            }
        }
        return $csv;
    }

    public function exportModesToCsv()
    {
        $csv = new Csv();
        $csv->insert(array('Code', 'Intitulé', 'Description'));
        foreach ($this->modes as $code => $mode) {
            $csv->insert(
                array(
                    $code,
                    $mode['Intitulé'],
                    $mode['Description']
                )
            );
        }
        return $csv;
    }

    public function exportActionsWithArchivedToCsv()
    {
        $csv = new Csv();
        $csv->insert(array('Code', 'Intitulé', 'Description', 'Référent'));
        foreach ($this->actions as $code => $action) {
            $csv->insert(
                array(
                    $code,
                    $action['Intitulé'],
                    $action['Description'],
                    $action['Referent']
                )
            );
        }
        return $csv;
    }
}
