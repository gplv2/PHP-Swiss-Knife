<?php
$headexpires = gmdate('D, d M Y H:i:s') . " GMT";
header("Last-Modified: " . $headexpires);
header("Pragma: no-cache");
header("Expires: " . $headexpires);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
/* This doesnt seem to work on all browsers */
//header("Expires: -1");
//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
?>
