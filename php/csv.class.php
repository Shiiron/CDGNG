<?php

class CSV {

    private $csv = Null;
    
    /**
    * Add a new line to csv file.
    *
    * @param array $line list of row cell
    */
    function Insert($row){
        $string = "";
        foreach ($row as $cell) {
            $string .= '"'.$cell.'",';
        }
        $this->csv .= $string."\n";
    }

    /**
    * Make CSV data from array
    * 
    * @param array $array Array to transform.
    */
    function Array2CSV($array){
        foreach ($array as $row) {
            $this->insert($row);
        }
    }

    /**
    * fonction de sortie du fichier avec un nom spécifique.
    *
    */
    function output($filename){
        header("Content-type: text/CSV");
        header("Content-disposition: attachment; filename=".$filename.".csv");
        print $this->csv;
        exit;
    }
}
