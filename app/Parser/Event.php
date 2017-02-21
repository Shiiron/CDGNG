<?php
namespace CDGNG\Parser;

class Event
{
    public $data = array();

    public function addLine($line)
    {
        $explodedLine = explode(':', $line, 2);
        // Des fois des parametres sont ajouté comme le timezone pour DTEND
        // et DTSTART
        $parameter = explode(';', $explodedLine[0]);
        switch ($parameter[0]) {
            case 'DTSTART':
                $this->data['DTSTART'] = $this->parseDate($explodedLine[1]);
                break;

            case 'DTEND':
                $this->data['DTEND'] = $this->parseDate($explodedLine[1]);
                break;

            case 'SUMMARY':
                $this->data['SUMMARY'] = $explodedLine[1];
                break;

            case 'RRULE':
                $this->data['RRULE'] = $explodedLine[1];
                break;

            default:
                // nothing
                break;
        }

    }

    private function parseDate($string)
    {
        $date = new \DateTime($string);
        return $date->getTimestamp();
    }
}
