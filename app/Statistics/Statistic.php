<?php
namespace CDGNG\Statistics;

use CDGNG\Csv;

abstract class Statistic
{
    public $length = 0;
    public $title = "";

    public $dtstart;
    public $dtend;

    public $calendars = array();

    abstract protected function getData($calendar);
    abstract protected function getSlotName();

    public function __construct($dtstart, $dtend)
    {
        $this->dtstart = $dtstart;
        $this->dtend = $dtend;
    }

    public function add($calendar)
    {
        $this->extendTitle($calendar->name);
        $calendar->parse($this->dtstart, $this->dtend);
        $this->calendars[$calendar->name] = array(
            'calendar' => $calendar,
            'data' => $this->getData($calendar),
        );
        $this->length += $calendar->length;
    }

    public function exportAsCsv()
    {
        $csv = new Csv();
        $csv->insert(array('Nom', $this->getSlotName(), 'Actions', 'Modalités', 'Temps(Min)'));
        foreach ($this->calendars as $calName => $calendar) {
            foreach ($calendar['data'] as $slotName => $slot) {
                if ($slotName === 'duration') {
                    continue;
                }
                foreach ($slot['actions'] as $actionName => $action) {
                    if ($actionName === 'duration') {
                        continue;
                    }
                    foreach ($action as $modeName => $mode) {
                        if ($modeName === 'duration') {
                            continue;
                        }
                        $csv->insert(array($calName, $slotName, $actionName, $modeName, $mode));
                    }
                }
            }
        }
        return $csv;
    }

    private function extendTitle($name)
    {
        if ($this->title !== "") {
            $this->title .= '+';
        }
        $this->title .= $name;
    }
}
