<?php
namespace CDGNG\Statistics;

abstract class Statistic
{
    public $length = 0;
    public $title = "";

    public $dtstart;
    public $dtend;

    public $calendars = array();

    abstract protected function getData($calendar);

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

    private function extendTitle($name)
    {
        if ($this->title !== "") {
            $this->title .= '+';
        }
        $this->title .= $name;
    }
}
