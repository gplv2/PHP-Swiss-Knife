<?php

$fLoadAvg = getLoadAverage();
if ($fLoadAvg > 20) {
   //echo "OVERLOADED" . PHP_EOL;
   header('HTTP/1.1 404 Too busy');
   exit;
}

$fBlocked = getBlockingProcesses();
if ($fBlocked > 4) {
   //echo "BLOCKED" . PHP_EOL;
   header('HTTP/1.1 404 Too blocked');
   exit;
}


//echo "LOAD_OK" . PHP_EOL;
header('HTTP/1.1 200 Server OK');

// print_r(array($fBlocked,$fLoadAvg));

function getBlockingProcesses() {
   $sStats = file_get_contents('/proc/stat');
   if (preg_match('/procs_blocked ([0-9]+)/i', $sStats, $aMatches))
   {
      return (int)$aMatches[1];
   }
   return 0;
}

function getLoadAverage() {
   $sLoadAverage = file_get_contents('/proc/loadavg');
   $aLoadAverage = explode(' ',$sLoadAverage);
   //print_r($aLoadAverage);
   return (float)$aLoadAverage[0];
}
?>
