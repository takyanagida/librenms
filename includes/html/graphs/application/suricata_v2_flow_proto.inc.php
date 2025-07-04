<?php

$name = 'suricata';
$unit_text = 'flows';
$colours = 'psychedelic';
$dostack = 0;
$printtotal = 0;
$addarea = 0;
$transparency = 15;

if (isset($vars['sinstance'])) {
    $rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'instance_' . $vars['sinstance'] . '___flow__udp']);
    $flow__tcp_rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'instance_' . $vars['sinstance'] . '___flow__tcp']);
    $flow__icmpv4_rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'instance_' . $vars['sinstance'] . '___flow__icmpv4']);
    $flow__icmpv6_rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'instance_' . $vars['sinstance'] . '___flow__icmpv6']);
} else {
    $rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'totals___flow__udp']);
    $flow__tcp_rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'totals___flow__tcp']);
    $flow__icmpv4_rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'totals___flow__icmpv4']);
    $flow__icmpv6_rrd_filename = Rrd::name($device['hostname'], ['app', $name, $app->app_id, 'totals___flow__icmpv6']);
}

$rrd_list = [
    [
        'filename' => $flow__icmpv4_rrd_filename,
        'descr' => 'ICMPv4',
        'ds' => 'data',
    ],
    [
        'filename' => $flow__icmpv6_rrd_filename,
        'descr' => 'ICMPv6',
        'ds' => 'data',
    ],
    [
        'filename' => $flow__tcp_rrd_filename,
        'descr' => 'TCP',
        'ds' => 'data',
    ],
    [
        'filename' => $rrd_filename,
        'descr' => 'UDP',
        'ds' => 'data',
    ],
];

require 'includes/html/graphs/generic_multi_line.inc.php';
