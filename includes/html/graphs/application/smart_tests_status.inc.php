<?php

$name = 'smart';
$unit_text = '';
$unitlen = 20;
$bigdescrlen = 15;
$smalldescrlen = 15;
$colours = 'mega';
$dostack = 0;
$printtotal = 0;
$addarea = 1;
$transparency = 15;

$rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, $vars['disk']]);

$rrd_list = [
    [
        'filename' => $rrd_filename,
        'descr' => 'Completed',
        'ds' => 'completed',
    ],
    [
        'filename' => $rrd_filename,
        'descr' => 'Interrupted',
        'ds' => 'interrupted',
    ],
    [
        'filename' => $rrd_filename,
        'descr' => 'Read Failure',
        'ds' => 'readfailure',
    ],
    [
        'filename' => $rrd_filename,
        'descr' => 'Unknown Failure',
        'ds' => 'unknownfail',
    ],
];

require 'includes/html/graphs/generic_multi_line_exact_numbers.inc.php';
