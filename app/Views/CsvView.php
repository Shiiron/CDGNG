<?php
namespace CDGNG\Views;

class CsvView extends InterfaceView
{
    public function __construct($filename, $csv)
    {
        $this->csv = $csv;
        $this->filename = $filename;
    }

    public function show()
    {
        header("Content-type: text/CSV");
        header("Content-disposition: attachment; filename='$this->filename'");
        $this->csv->print();
    }
}
