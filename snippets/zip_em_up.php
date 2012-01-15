#!/usr/bin/php5
<?php

error_reporting(E_ALL);
ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);

/* how to store the working directory "from where" the script was called: */
$initial_cwd = preg_replace( '~(\w)$~' , '$1' . DIRECTORY_SEPARATOR , realpath( getcwd() ) );
chdir($initial_cwd);

//chdir( dirname ( __FILE__ ) );
#
#require('../include/inc_timing.php');
#

$count=0;

$max_day=29;
$max_month=12;
$max_year=2010;

$bzip="/bin/bzip2 -9";

$pattern="/^(\d+)\/(\d+)\/(\d+)\/(\d+)_(\d+).sdf$/D"; 

foreach(rglob('*.sdf') as $file) {
	// Filename: 2010/12/14/354476020128280_0.sdf
	preg_match($pattern,$file,$mymatches);
	/*
	    print_r($mymatches);
	 * Array
	 * (
	 * [0] => 2010/12/14/354476020128280_0.sdf
	 * [1] => 2010
	 * [2] => 12
	 * [3] => 14
	 * [4] => 354476020128280
	 * [5] => 0
	 * )
	 */
	$yy= (isset($mymatches[1])) ? $mymatches[1]: false;
	$mm= (isset($mymatches[2])) ? $mymatches[2]: false;
	$dd= (isset($mymatches[3])) ? $mymatches[3]: false;

	$zip_it=false;
	if (isset($yy) and $yy<$max_year) {
		$zip_it=true;
	} elseif (isset($yy) and $yy==$max_year) {
		if (isset($mm) and $mm<$max_month) {
			$zip_it=true;
		} elseif (isset($mm) and $mm==$max_month) {
			if (isset($dd) and $dd<=$max_day) {
				$zip_it=true;
			}
		}
	}

	if ($zip_it) {
		//echo sprintf("File: %s\n",$file);
		$cmd = sprintf("%s %s",$bzip,$file);
		echo sprintf("cmd: %s\n",$cmd);
		passthru($cmd, $error);
		//echo sprintf("Skipping: %s\n",$file);
	}
	$count++;

	#if($count>2) { break; }
}

//var_export(rglob('*.sdf'));


/**
 * Recursive glob()
 */

/**
 * @param int $pattern
 *  the pattern passed to glob()
 * @param int $flags
 *  the flags passed to glob()
 * @param string $path
 *  the path to scan
 * @return mixed
 *  an array of files in the given path matching the pattern.
 */

function rglob($pattern='*', $flags = 0, $path='')
{
    $paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
    $files=glob($path.$pattern, $flags);
    foreach ($paths as $path) { $files=array_merge($files,rglob($pattern, $flags, $path)); }
    return $files;
}

?>
