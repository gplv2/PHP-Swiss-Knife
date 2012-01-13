<?php

/* Just extend the class, add our method */
class MySQLiteDatabase extends SQLiteDatabase {

   /* A neat way to see which tables are inside a valid sqlite file */
   public function getTables()  {
      $tables=array();
      $q = $this->query(sprintf("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"));
      $result = $q->fetchAll();
      foreach($result as $tot_table) {
         $tables[]=$tot_table['name'];
      }
      return($tables);
   }
}

/* a sqlite file */
$database="BLAHBLAH.sqlite";

if (file_exists($database)) {
   $db = new MySQLiteDatabase($database, 0666, $err);
   if ($err) {
      trigger_error($err);
   } else {
      print_r($db->getTables());
   }
}


/* this sqlite db had 2 tables:
   Array
   (
   [0] => Account
   [1] => Device
   )

 */
?>
