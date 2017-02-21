<?php
namespace CDGNG;

class Csv {

    private $csv = array();

    public function insert($row)
    {
        $this->csv[] = $row;
    }

    function removeColumn($number)
    {
        foreach (array_keys($this->csv) as $key) {
            array_splice($this->csv[$key], $number, 1);
        }
    }

    function print()
    {
        $output = "";
        foreach ($this->csv as $row) {
            foreach ($row as $cell) {
                $output .= '"' . $cell . '",';
            }
            $output .= "\n";
        }
        print $output;
    }
}
