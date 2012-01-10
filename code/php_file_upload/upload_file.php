<?php
   $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
    curl_setopt($ch, CURLOPT_URL, _VIRUS_SCAN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    // same as <input type="file" name="file_box">
    $post = array(
        "file_box"=>"@/path/to/myfile.jpg",
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    $response = curl_exec($ch);
?>
