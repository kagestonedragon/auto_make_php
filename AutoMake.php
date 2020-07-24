<?php
require_once('init.php');

$autoMake = new AutoMake($argv);
$autoMake->createMakefile();
$autoMake->writeLog();