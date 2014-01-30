<?php
class CSV {

        private $csv = Null;
        /**
        * Insertion des lignes dans le fichiers Excel, il faut introduire 
        * les données sous formes de chaines de caractère.
        * Attention a séparer avec une virgule.
        */
        function Insertion($file){

                $this->csv.=$file."\n";
                return $this->csv;
        }

        /**
        * Make CSV data from array
        * 
        * @param array $array Array to transform.
        */
        function Array2CSV($array){
                foreach ($array as $row) {
                        foreach ($row as $cell) {
                                $this->csv .= "\"";
                                $this->csv .= $cell;
                                $this->csv .= "\",";
                        }
                        $this->csv.="\n";
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

?>