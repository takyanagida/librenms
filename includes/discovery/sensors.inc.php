<?php

use App\Facades\LibrenmsConfig;
use LibreNMS\Enum\Sensor;
use LibreNMS\OS;

/** @var OS $os */
$pre_cache = $os->preCache();

if ($device['os'] == 'rittal-cmc-iii-pu' || $device['os'] == 'rittal-lcp') {
    include 'includes/discovery/sensors/rittal-cmc-iii-sensors.inc.php';
} else {
    // Run custom sensors
    require 'includes/discovery/sensors/cisco-entity-sensor.inc.php';
    require 'includes/discovery/sensors/entity-sensor.inc.php';
    require 'includes/discovery/sensors/ipmi.inc.php';
}

if ($device['os'] == 'netscaler') {
    include 'includes/discovery/sensors/netscaler.inc.php';
}

if ($device['os'] == 'openbsd') {
    include 'includes/discovery/sensors/openbsd.inc.php';
}

if ($device['os'] == 'linux') {
    include 'includes/discovery/sensors/rpigpiomonitor.inc.php';
}

if (isset($device['hardware']) && strstr($device['hardware'], 'Dell')) {
    include 'includes/discovery/sensors/fanspeed/dell.inc.php';
    include 'includes/discovery/sensors/power/dell.inc.php';
    include 'includes/discovery/sensors/voltage/dell.inc.php';
    include 'includes/discovery/sensors/state/dell.inc.php';
    include 'includes/discovery/sensors/temperature/dell.inc.php';
}

if (isset($device['hardware']) && strstr($device['hardware'], 'ProLiant')) {
    include 'includes/discovery/sensors/state/hp.inc.php';
}

if ($device['os'] == 'gw-eydfa') {
    include 'includes/discovery/sensors/gw-eydfa.inc.php';
}

// filter submodules
$run_sensors = array_intersect(Sensor::values(), LibrenmsConfig::get('discovery_submodules.sensors', Sensor::values()));

sensors($run_sensors, $os, $pre_cache);
unset(
    $pre_cache,
    $run_sensors,
    $entitysensor
);
