<?php

// For testing purposes only.

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

include_once $baseDir . '/app/Models/FirstDummy.php';
include_once $baseDir . '/app/Models/SecondDummy.php';
include_once $baseDir . '/app/Models/ThirdDummy.php';
include_once $baseDir . '/app/Models/FourthDummy.php';
include_once $baseDir . '/app/Models/FifthDummy.php';
include_once $baseDir . '/app/Models/SixthDummy.php';
include_once $baseDir . '/app/Models/SeventhDummy.php';
include_once $baseDir . '/app/Models/EighthDummy.php';
include_once $baseDir . '/app/Models/NinthDummy.php';
include_once $baseDir . '/app/Models/TenthDummy.php';
include_once $baseDir . '/app/Models/ExternalDummy.php';

return [
    "App\\Models\\FirstDummy" => $baseDir . '/app/Models/FirstDummy.php',
    "App\\Models\\SecondDummy" => $baseDir . '/app/Models/SecondDummy.php',
    "App\\Models\\ThirdDummy" => $baseDir . '/app/Models/ThirdDummy.php',
    "App\\Models\\FourthDummy" => $baseDir . '/app/Models/FourthDummy.php',
    "App\\Models\\FifthDummy" => $baseDir . '/app/Models/FifthDummy.php',
    "App\\Models\\SixthDummy" => $baseDir . '/app/Models/SixthDummy.php',
    "App\\Models\\SeventhDummy" => $baseDir . '/app/Models/SeventhDummy.php',
    "App\\Models\\EighthDummy" => $baseDir . '/app/Models/EighthDummy.php',
    "App\\Models\\NinthDummy" => $baseDir . '/app/Models/NinthDummy.php',
    "App\\Models\\TenthDummy" => $baseDir . '/app/Models/TenthDummy.php',
    "MyPackage\\Models\\ExternalDummy" => $baseDir . '/app/Models/ExternalDummy.php'
];
