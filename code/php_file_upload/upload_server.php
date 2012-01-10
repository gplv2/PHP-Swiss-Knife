<?php 

error_reporting(E_ALL);
ini_set('memory_limit', '20M');
ini_set("upload_max_filesize", "1M");  // This is per file apparantly
ini_set("post_max_size", "2M"); 

// $allowed_extensions = array("txt","csv","htm","html","xml", "css","doc","xls","rtf","ppt","pdf","swf","flv","avi", "wmv","mov","jpg","jpeg","gif","png"); 
$allowed_extensions = array("tms","hex","src"); 

$target_dir = "incoming";

// so we don't have to apply makeup
echo "<pre>";

// Just fix that silly $_FILES layout and never look back
fix_files_superglobal();

require_once '/var/www/nginx-default/lib/PHP-on-Couch/lib/couch.php';
require_once '/var/www/nginx-default/lib/PHP-on-Couch/lib/couchClient.php';
require_once '/var/www/nginx-default/lib/PHP-on-Couch/lib/couchDocument.php';

// set a new connector to the CouchDB server
$client=null;

// This is in a bad place
$client = new couchClient ('127.0.0.1:5984', 'configs');

/* I will not be handing out too much information to the end user about the upload progress, if it fails I want 
   that to happen silently, which is why we can nest these checks like below without else's
 */
if(!empty($_FILES)) {
   foreach ($_FILES as $file) { 
      echo sprintf("\n");
      // always double check web input 
      if (strlen($file['tmp_name']) > 0 and $file['error'] === UPLOAD_ERR_OK) { 
         // I just like shorter notations
         $source_file = $file["tmp_name"];
         $target_file = $file["name"];
         $file_size = $file["size"];
         echo sprintf("Uploaded file '%s' accepted\n",$target_file);
         echo "Size was: " . display_filesize($file_size / 1024) . "\n";

         // Extra security step, see the PHP manual for these functions!
         if (is_uploaded_file($source_file)) {
            echo sprintf("File comes from a POST operation, that is ok\n");

            // Find the dot in the name
            $dot_pos =  strripos($target_file, '.');

            // strip extention, one could alternatively use preg_split here instead or even path_info
            $file_basename = substr($target_file, 0, $dot_pos); 
            $file_ext      = substr($target_file, $dot_pos+1);

            // Alternatives:
            // $file_ext = pathinfo($target_file, PATHINFO_EXTENSION); 
            // $file_ext = strrchr($target_file, '.');  
            // Do not use explode, it doesn't handle filenames like foobar-1.1.1.tar gracefully
            // You definitely don't want to use split(), this function has been DEPRECATED as of PHP 5.3.0

            // Is there an extension at all since we require one! 
            if (!empty($file_ext)) {
               echo sprintf("Accepted extensions are: %s\n",implode($allowed_extensions,", "));
               // See if this type of file is allowed according to our list above
               if (in_array($file_ext, $allowed_extensions)) { 
                  echo sprintf("Extension accepted : %s\n",$file_ext);
                  // Ok, now we are pretty much sure that about everything is in order. 

                  // Prepend a path to the cleaned target file name 
                  $save_file=$target_dir . DIRECTORY_SEPARATOR . clean_name($target_file);

                  // if you don't call this function it will not be saved at all
                  if (!file_exists($save_file)) {
                     echo sprintf("Saving to filename : %s\n",$save_file);
                     move_uploaded_file($source_file, $save_file);
                     
                     echo sprintf("Storing in couch: %s\n",$save_file);
                     put_on_sofa(&$client, $save_file, $file_basename, $file_ext);
                  } else {
                     echo sprintf("Storing in couch: %s\n",$save_file);
                     put_on_sofa(&$client, $save_file, $file_basename, $file_ext);
                  }
               } 
            } 
         } 
      } 
   } 
} else {
	$all_docs = $client->getAllDocs();
	echo "Database got ".$all_docs->total_rows." documents.<BR>\n";
	foreach ( $all_docs->rows as $row ) {
    	echo "Document ".$row->id."<BR>\n";
	}
}

// http://localhost:5984/configs/ff367514ae289618a6f7b6d19986eeca/startblock_saraber.tms

echo "\n";
echo "</pre>";

/* Fixes the messed up array doing multiple file uploads using a single array post var like : file[1], file[2] */
function fix_files_superglobal() {
   $new_files = array();

   foreach($_FILES as $key => $attributes ) {
      // echo sprintf("%s => %s", $key , $attributes);
      foreach($attributes as $tagname => $tags ) {
         // echo sprintf("%s => %s\n", $tagname , $val);
         if (is_array($tags)) {
            foreach($tags as $file_key => $value ) {
               $new_files[$file_key][$tagname] = $value;
            }
         }
      } 
   }

   /* Only copy this back if we have content, when we don't we are dealing with
      a single file or form fields not like file[f1], file [f2], but just plain 'file' */
   if (!empty($new_files)) {
      $_FILES = $new_files;
   }
}

function display_filesize($filesize){
   if(is_numeric($filesize)){
      $decr = 1024; $step = 0;
      $prefix = array('Byte','KB','MB','GB','TB','PB');

      while(($filesize / $decr) > 0.9){
         $filesize = $filesize / $decr;
         $step++;
      } 
      return round($filesize,2).' '.$prefix[$step];
   } else {
      return 'NaN';
   }
}

function clean_name ($name) {
   /* - remove extra spaces/convert to _,
      - remove non 0-9a-Z._- characters,
      - remove leading/trailing spaces */
   return $safe_filename = preg_replace( array("/\s+/", "/[^-\.\w]+/"), array("_", ""), $name); 
}

function put_on_sofa($client, $file=NULL, $basename=NULL, $ext=NULL) {
   
   if (empty($file) OR empty($ext) OR empty($basename)) {
      return(-1);
   }
   echo __METHOD__ . "\n";
   /*
      try {
      $client->createDatabase();
      } catch (Exception $e) {
      echo "Unable to create database : ".$e->getMessage() . "\n";
      }
    */

   // document fetching by ID
   // $doc = $client->getDoc('some_doc_id');
   // updating document


   // GLENNMOD
   $crc=null;

   if (strcmp($ext,"tms")==0) {
      require_once("/var/www/nginx-default/lib/cgpsv71b5.php");
      // echo "GPL\n";

      //Create instance of the class
      $pcGPSsettings=new CGPSsettings(); 

      // now I have a $dirs array containing all the directories in $path
      //Feed existing settings data to the class (in this example from a disk file) 

      if($pcGPSsettings->SetSettingsData(@file_get_contents($file))) {
         $crc=$pcGPSsettings->GetSettingsCrc();

         echo $file . " \tsize: " . filesize($file) . " \tcrc: " . $crc . "\n";
         if (!is_link($file)) {
            // Is not symbolic lnk
         }
      } else {
         echo $file . " \tsize: " . filesize($file) . " \t-> bad tms file, no crc possible" . "\n";
      }
   }
   // GLENNMOD


   $new_doc = new stdClass();
   $new_doc->title = $file;
   $new_doc->crc=$crc;
   $new_doc->size=filesize($file);
   $new_doc->ext=$ext;

   echo "Storing \$doc : \$client->storeDoc(\$doc)\n";
   try {
      $response = $client->storeDoc($new_doc);
   } catch (Exception $e) {
      echo "Something weird happened: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
      exit(1);
   }
   echo "The document is stored. CouchDB response body: ".print_r($response,true)."\n";

   $doc = $client->getDoc($response->id);

   // storeAttachment($doc,$file,$content_type = 'application/octet-stream',$filename = null)
   $ok = $client->storeAttachment($doc,$file,'application/octet-stream', $basename . '.' . $ext);
   print_r($ok);

   echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
   
   //$doc = $client->getDoc($response->id);

   $doc = $client->asCouchDocuments()->getDoc($response->id);
   $fields = $doc->getFields();
   // $fields->_attachments;
   // print_r($fields);

   //var_Dump(get_object_vars( $fields ));exit;
   //var_Dump($fields); exit;
   foreach (get_object_vars( $fields ) as $key => $val) {
      if (!is_object($val)) {
         echo sprintf("%s = %s\n",$key,$val);
      } else {
         foreach (get_object_vars( $val ) as $k => $v) {
            if (!is_object($v)) {
               echo sprintf("%s = %s\n",$k,$v);
            } else {
               foreach (get_object_vars( $v) as $kk => $vv) {
                  if (!is_object($vv)) {
                     echo sprintf("%s = %s\n",$kk,$vv);
                  }
               }
            }
         }
      }
   }

   // $fields->_attachments;

   // http://localhost:5984/configs/e25f8e087bc9e574dc3cff289df6abe0/fulltracing_nano_rev8.tms

   // $fields = $client->getFields();
   // print $fields->_attachments;

   // print_r($ok->getFields());

   // http://localhost:5984/configs/ff367514ae289618a6f7b6d19986eeca/startblock_saraber.tms
   // stdClass ( "ok" => true, "id" => "BlogPost5676" , "rev" => "5-2342345476" )

/*
   $doc->newproperty = array("hello !","world");
   try {
      $client->storeDoc($doc);
   } catch (Exception $e) {
      echo "Document storage failed : ".$e->getMessage()."<BR>\n";
   }

   // view fetching, using the view option limit
   try {
      $view = $client->limit(100)->getView('orders','by-date');
   } catch (Exception $e) {
      echo "something weird happened: ".$e->getMessage()."<BR>\n";
   }

   //using couch_document class :
   $doc = new couchDocument($client);
   $doc->set( array('_id'=>'JohnSmith','imei'=>'Smith') ); //create a document and store it in the database
   echo $doc->name ; // should echo "Smith"
   $doc->name = "Brown"; // set document property "name" to "Brown" and store the updated document in the database
*/

}

?>
<html>
   <title>Testing multiple file upload functions</title>
   <body>
      <form action="upload.php" method="post" enctype="multipart/form-data">
         <label for="file">Filename:</label>
         <input type="file" name="file1" id="file1" />
         <input type="file" name="file2" id="file2" />
         <br />
         <input type="submit" name="submit" value="Submit" />
      </form>
   </body>
</html> 
