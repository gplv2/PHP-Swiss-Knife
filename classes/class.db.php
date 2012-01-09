<?php
/*********************************************************************
  class.db.php

  simple Mysql DB class functions. 

  Niki Van Cleemput (niki@vancleemput.com) - Original version and idea of a lightweight abstraction class
  Glenn Plas (glenn@byte-consult.be) - reconnect features, class development, enhancements, fixes

  Copyright (c)  2011-2012 

  Released under the GNU General Public License WITHOUT ANY WARRANTY.
  See LICENSE.TXT for details.

  vim: expandtab sw=3 ts=3 sts=3:
 **********************************************************************/

class DBM {
   private $host;
   private $user;
   private $password;
   private $defaultdb;

   private $tries=6; # times
   private $delay=100000; # ms // ex: wait for 2 seconds: usleep(2000000);

   private $DB;
   private $mode=MYSQL_ASSOC; // Hate to force this for now

   private $result;
   private $affected=0;
   private $verbose=0;

   /* don't do damage */
   private $dry_run=0;

   public $eol;

   public function __construct($config){
      if (defined('STDIN')) {
         $this->eol="\n";
      } else {
         $this->eol="<BR/>";
      }

      $this->logtrace(4,__METHOD__);

      $this->host=$config['host'];
      $this->user=$config['user'];
      $this->password=$config['password'];

      if (!empty($config['default_db'])) {
         $this->defaultdb=$config['default_db'];
      }
      if (!empty($config['dry_run'])) {
         $this->dry_run=$config['dry_run'];
      }
      if (!empty($config['verbose'])) {
         $this->verbose=$config['verbose'];
      }

   }

   public function db_connect(){
      //if we already have a connection we don't need to reconnect
      $this->logtrace(4,__METHOD__);
      if ($this->DB) {
         return true;
      }else{
         // see https://bugs.php.net/bug.php?id=60656 , running this on mariadb throws warnings of no consequence
         error_reporting(E_ALL ^ E_WARNING);
         $this->DB = mysql_connect($this->host,$this->user,$this->password);
         error_reporting(E_ALL);
         if ($this->DB) {
            /* not class specific but handy */
            @mysql_query('SET NAMES "UTF8"');
            @mysql_query('SET COLLATION_CONNECTION=utf8_general_ci');
            if(!empty($this->defaultdb)) {
               $this->db_select_database($this->defaultdb);
            }
            return true;
         }
      }
      //if we get here, we can't connect to the database
      return null;
   }

   public function db_select_database($name = '') {
      $this->logtrace(4,__METHOD__);
      if (empty($name)) {
         $name = $this->defaultdb;
      }
      if (empty($name)) {
         return null;
      }
      if (!@mysql_select_db($name, $this->DB)) {
         return false;
      } else {
         return true;
      }
   }

   public function db_dry_run($value) {
      $this->logtrace(4,__METHOD__);
      if (isset($value)) {
         $this->dry_run = $value;
      }
   }

   public function db_get_link(){
      $this->logtrace(4,__METHOD__);
      return $this->DB;
   }

   private function db_reconnect() {
      $this->logtrace(4,__METHOD__);
      $works=true;
      if ($this->DB) {
         if (!@mysql_ping ($this->DB)) {
            //here is the major trick, you have to close the connection (even though its not currently working) for it to recreate properly.
            @mysql_close($this->DB);
            unset($this->DB);
            $works=$this->db_connect();
         } 
      } else {
         @mysql_close($this->DB);
         unset($this->DB);
         $works=$this->db_connect();
      }
      return $works;
   }

   public function db_version(){
      $this->logtrace(4,__METHOD__);
      preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', mysql_result($this->db_query('SELECT VERSION()'),0,0),$matches);
      return $matches[1];
   }

   public function db_count($query){
      $this->logtrace(4,__METHOD__);
      list($count)=db_fetch_row($this->db_query($query));
      return $count;
   }

   public function db_fetch_fields() {
      $this->logtrace(4,__METHOD__);
      return mysql_fetch_field($this->result);
   }

   public function db_affected_rows() {
      $this->logtrace(4,sprintf("%s - Affected %d",__METHOD__, $this->affected));
      return $this->affected;
   }

   public function db_num_rows() {
      $this->logtrace(4,sprintf("%s - Num Rows %d",__METHOD__, $this->affected));
      return $this->affected;
   }

   public function shellQry($sql) {
      $this->logtrace(4,__METHOD__);

      $server = explode(":", $this->host);

      /* Use a temp file to pipe this to the mysql server */
      $temp_file_name = tempnam("/tmp", "FOO");
      $handle = fopen($temp_file_name, "w");
      fwrite($handle, $sql);
      fclose($handle);

      $my_sql_files = "whereis -b mysql";
      exec($my_sql_files,$search_output);
      $mysql_files = preg_split('/\s+/',$search_output[0]);
      foreach ($mysql_files as $key => $path ) {
         if (is_file($path) && is_executable($path) && is_readable($path)) {
            // echo "mysql exec $path\n";
            $mysql_bin = $path;
            break;
         }
      }

      if (empty($mysql_bin)) {
         return -1;
      }

      logtrace(1,sprintf("Using command line shell to issue multiple sql statement"));
      $my_sql = sprintf("%s -h %s -u %s -p%s -P %d %s -A < %s",$mysql_bin, $server[0], $this->user, $this->password, $server[1], $this->defaultdb, $temp_file_name);
      logtrace(1,"cmd: " .$my_sql);

      if ($this->dry_run) {
         logtrace(2,"Dry run mode...Just print the query, don't execute");
         logtrace(2,$sql);
      } else {
         $mysql_output=trim(system($my_sql));
         logtrace(2,"output: " .$mysql_output);
         unlink($temp_file_name);
      }
   }


   public function db_query($qry){
      $this->logtrace(4,sprintf("%s : %s",__METHOD__,$qry));
      $tries=0;
      $result_query=null;

      /* For now the only function that will ensure a DB connection is here */
      while(!$this->DB AND $tries < $this->tries ){
         $this->logtrace(2,sprintf("%s : DB tries %s",__METHOD__,$tries));
         if(!$this->db_reconnect()) {
            usleep($this->delay);
         }
         $tries++;
      }
      /* Note, this still can go bad here since the connection might break between here and the next call */

      if ($this->dry_run) {
         logtrace(2,"Dry run mode...Just print the query, don't execute");
         logtrace(2,$qry);
         return null;   
      }

      if($this->DB){
         $this->logtrace(4,sprintf("%s : DB ok %s",__METHOD__,$this->DB));
         $this->result = mysql_query($qry, $this->DB);
         if($this->result) {
            $this->logtrace(4,sprintf("%s : Resultset %s",__METHOD__,$this->result));

            /* Analyse the query for type of query, carefully here */
            $ro_query=0;
            $pattern="/^SELECT|^Select|^select/";
            if (preg_match($pattern,$qry, $matches)) {
               if (!empty($matches[0])) {
                  $this->logtrace(4,"Seems a SELECT query");
                  $this->affected=$this->_num_rows();
                  $this->logtrace(1,sprintf("%s : Affected %s",__METHOD__,$this->affected));
                  $ro_query=1;
               }
            } else {
               $this->logtrace(4,"not a SELECT query.");
               $this->affected=$this->_affected_rows();
               $this->logtrace(1,sprintf("%s : Affected %s",__METHOD__,$this->affected));
            }
         } else {
            $this->logtrace(0,sprintf("%s : No results.",__METHOD__));
         }
         //printf("No database connection");
         // $DB->db_reconnect();
         //query failed for some reason
      } else {
         $this->logtrace(0,sprintf("%s : DB not ok.",__METHOD__));
      }

      // $alert='['.$query.']'."\n\n".db_error();
      // Sys::log(LOG_ALERT,'DB Error #'.db_errno(),$alert,($cfg && $cfg->alertONSQLError()));

      return $this->result;
   }

   public function db_squery($query){ 
      $this->logtrace(4,sprintf("%s : %s",__METHOD__,$qry));

      // utilizing args and sprintf
      $args  = func_get_args();
      $query = array_shift($args);
      $query = str_replace("?", "%s", $query);
      $args  = array_map('$this->db_real_escape', $args);
      array_unshift($args,$query);
      $query = call_user_func_array('sprintf',$args);
      return $this->db_query($query);
   }

   /* Get all of the rows from the link */
   public function db_fetch_array() {
      $this->logtrace(4,__METHOD__);
      $results = array();

      /* Getting result set back */
      while ($record = mysql_fetch_array($this->result,($this->mode)?$this->mode:MYSQL_ASSOC)){
         $results[]=$record;
      }
      $retval = (is_array($results)) ? $results : array() ;
      return db_output($retval);
   }

   public function db_errno() {
      $this->logtrace(4,__METHOD__);
      return @mysql_errno($this->DB);
   }

   public function my_string($string) {
      $this->logtrace(4,__METHOD__);
      return @mysql_real_escape_string($string, $this->DB);
   }

   public function db_error() {
      $this->logtrace(4,__METHOD__);
      return @mysql_error($this->DB);
   }

   public function __destruct() {
      $this->logtrace(4,__METHOD__);
      $this->db_close();
   }

   public function db_close() {
      $this->logtrace(4,__METHOD__);
      /* Close all the connections */
      if ($this->DB) {
         if(mysql_close($this->DB)) {
            $closed=sprintf("Closed.");
            $return=1;
         } else {
            $closed=sprintf("Close problem.");
            $return=null;
         }
      }
      return $return;
   }

   static public function db_output($param) {
      $this->logtrace(4,__METHOD__);
      if(!function_exists('get_magic_quotes_runtime') || !get_magic_quotes_runtime()) {
         // Not on!
         return $param;
      }

      if (is_array($param)) {
         reset($param);
         while(list($key, $value) = each($param)) {
            $param[$key] = $this->db_output($value);
         }
         return $param;
      } elseif(!is_numeric($param)) {
         $param=trim(stripslashes($param));
      }

      return $param;
   }

   private function _num_rows() {
      $this->logtrace(4,__METHOD__);
      // var_Dump($this->result);
      return ($this->result)?mysql_num_rows($this->result):0;
   }

   private function _affected_rows() {
      $this->logtrace(4,__METHOD__);
      $affected=mysql_affected_rows($this->DB);
      return ($affected)?$affected:0;
   }

   public function db_data_seek($row_number) {
      $this->logtrace(4,__METHOD__);
      return mysql_data_seek($this->result, $row_number);
   }

   public function db_data_reset(){
      $this->logtrace(4,__METHOD__);
      return mysql_data_seek($this->result,0);
   }

   public function db_insert_id() {
      $this->logtrace(4,__METHOD__);
      return mysql_insert_id();
   }

   public function db_free_result() {
      $this->logtrace(4,__METHOD__);
      return mysql_free_result($this->result);
   }

   public function db_real_escape($val,$quote=false){
      $this->logtrace(4,__METHOD__);
      $val=@mysql_real_escape_string($val);
      return ($quote)?"'$val'":$val;
   }

   public function db_fetch_row() {
      $this->logtrace(4,__METHOD__);
      if ($row = mysql_fetch_row($this->result,($this->mode)?$this->mode:MYSQL_ASSOC)){
         return ($row)?db_output($row):NULL;
      }
   }

   public function db_input($param,$quote=true) {
      $this->logtrace(4,__METHOD__);
      // is_numeric doesn't work all the time...9e8 is considered numeric..which is correct...but not expected.
      if($param && preg_match("/^\d+(\.\d+)?$/",$param)) {
         return $param;
      }

      if($param && is_array($param)){
         reset($param);
         while (list($key, $value) = each($s)) {
            $param[$key] = $this->db_input($value,$quote);
         }
         return $param;
      }
      return $this->db_real_escape($param,$quote);
   }


   private function logtrace($level,$msg) {
      if(empty($this->verbose)) {
         return null;
      }

      $DateTime=@date('Y-m-d H:i:s', time());

      if ( $level <= $this->verbose ) {
         $mylvl=NULL;
         switch($level) {
            case 0:
               $mylvl ="error";
               break;
            case 1:
               $mylvl ="core ";
               break;
            case 2:
               $mylvl ="info ";
               break;
            case 3:
               $mylvl ="notic";
               break;
            case 4:
               $mylvl ="verbs";
               break;
            case 5:
               $mylvl ="dtail";
               break;
            default :
               $mylvl ="exec ";
               break;
         }
         // 2008-12-08 15:13:06 [31796] - [1] core    - Changing ID
         //"posix_getpid()=" . posix_getpid() . ", posix_getppid()=" . posix_getppid();
         $content = $DateTime. " [" .  posix_getpid() ."]:[" . $level . "]" . $mylvl . " - " . $msg . $this->eol;
         echo $content;
      }
   }
}
?>
