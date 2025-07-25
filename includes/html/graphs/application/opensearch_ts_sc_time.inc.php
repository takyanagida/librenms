<?php

$name = 'opensearch';
$unit_text = 'Millisec/Sec';
$colours = 'greens';
$dostack = 0;
$printtotal = 0;
$addarea = 0;
$transparency = 15;

$rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'ts']);

$rrd_list = [
    [
        'filename' => $rrd_filename,
        'descr' => 'Scroll Time',
        'ds' => 'ts_sc_time',
    ],
];

require 'includes/html/graphs/generic_multi_line.inc.php';
