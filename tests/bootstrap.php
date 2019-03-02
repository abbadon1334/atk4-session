<?php

require_once __DIR__ . '/../vendor/autoload.php';

// if any file output something session will start and all test will fail
// this is a normal behaviour of PHP Session
ob_start();
