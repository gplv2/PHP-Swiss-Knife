#!/usr/bin/php5
<?php
require_once("File/CSV/DataSource.php");

/* how to store the working directory "from where" the script was called: */
$initial_cwd = preg_replace( '~(\w)$~' , '$1' . DIRECTORY_SEPARATOR , realpath( getcwd() ) );


     // usage sample
     $csv = new File_CSV_DataSource;
  
     // tell the object to parse a specific file
     if ($csv->load($initial_cwd . '357304030025675_0.sdf')) {
  
       // execute the following if given file is usable
  
       // get the headers found in file
       //$array = $csv->getHeaders();
      
       // get a specific column from csv file
       //$csv->getColumn($array[2]);
      
       // get each record with its related header
       // ONLY if all records length match the number
			 // of headers
			 /*
        if ($csv->isSymmetric()) {
            $array = $csv->connect();
        } else {
            // fetch records that dont match headers length
            $array = $csv->getAsymmetricRows();
        }

				*/
        // ignore everything and simply get the data as an array
			 $array = $csv->getrawArray();
			 print_r($array);
     }

?>
