<?php
namespace CDGNG\Parser;

class Calendar
{
    public $name;
    public $filename;

    public $events = array();

    public function __construct($filename)
    {
        $this->name = basename($filename, '.ics');
        $this->filename = $filename;
    }

    /**
     * [parse description]
     * @return [type] [description]
     */
    public function parse()
    {
        $this->events = array();

        $lines = file($this->filename, FILE_SKIP_EMPTY_LINES);
        $lines = array_map('trim', $lines);

        foreach ($lines as $line) {
            switch ($line) {
                case 'BEGIN:VEVENT':
                    $event = new Event();
                    break;

                case 'END:VEVENT':
                    if (isset($event)) {
                        $this->events[] = $event->data;
                        unset($event);
                    }
                    break;

                default:
                    if (isset($event)) {
                        $event->addLine($line);
                    }
                    break;
            }
        }
    }
}
