<?php
class CSV {
 
        private $csv = Null;
        /**
         * Insertion des lignes dans le fichiers Excel, il faut introduire les données sous formes de chaines
         * de caractère.
         * Attention a séparer avec une virgule.
         */
        function Insertion($file){
 
                $this->csv.=$file."\n";
                return $this->csv;
        }
 
        /**
         * fonction de sortie du fichier avec un nom spécifique.
         *
         */
        function output($NomFichier){
 
                header("Content-type: application/vnd.ms-excel");
                header("Content-disposition: attachment; filename=$NomFichier.csv");
                print $this->csv;
                exit;
 
        }
}
 
?>