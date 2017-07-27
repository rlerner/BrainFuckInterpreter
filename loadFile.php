<?php

$fileName = $argv[1];
if (!is_readable($fileName)) {
	die("Cannot read $fileName\n\n");
}

require_once "class/BrainFuckInterpreter.class.php";

$program = file_get_contents($fileName);
$bfi = new BrainFuckInterpreter;
$bfi->memoryAutoScaling = true;
$program = $bfi->prefilter($program);
$bfi->loadProgram($program);
$bfi->run();
echo "\n\n";
