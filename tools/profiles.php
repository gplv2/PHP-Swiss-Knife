<?php

function profiler($return=false) {
   static $m=0;
   if ($return) return "$m bytes";
   if (($mem=memory_get_usage())>$m) $m = $mem;
}

register_tick_function('profiler');
declare(ticks=1);

/*
   Your code here
 */

echo profiler(true);

?>
