<?php

class MySQLiteDatabase extends SQLiteDatabase {

   public function getTables()  {
      $q = $this->query(sprintf("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"));
      var_Dump($q);
      if (empty($error)){
         $result = $q->fetchAll();
         // var_Dump($result); exit;
         foreach($result as $tot_table) {
            $table = $tot_table['name'];
            // logtrace(2,sprintf("%s",$table));
            $tables[]=$table;
         }
         return($tables);
      }
   }
}

$database="BLAHBLAH.sqlite";
$db = new MySQLiteDatabase($database, 0666, $err);
if ($err) {
   trigger_error($err);
   return($err);

}
print_r($db->getTables());

?>
