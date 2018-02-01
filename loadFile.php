<?php
/**
 * BrainFuckInterpreter
 *
 * Yet another interpreter to run Brainfuck applications, this time, in PHP.
 *
 * PHP Version 7.0 (conventions used make this not portable to 5. You should upgrade anyway.)
 *
 * LICENSE: MIT
 *
 * @package BrainFuckInterpreter
 * @author Robert Lerner
 * @copyright 2017-2018 Robert Lerner, All Rights Reserved
 * @see https://robert-lerner.com To make me happy.
 * @license https://opensource.org/licenses/MIT
 * @version 1.0.0
 * @link https://github.com/rlerner/BrainFuckInterpreter
 * @see http://semver.org/ For what the version means
 */
require_once "class/BrainFuckInterpreter.class.php";

$fileName = $argv[1];
if (!is_readable($fileName)) {
	die("Cannot read $fileName\n\n");
}

$program = file_get_contents($fileName);
$bfi = new BrainFuckInterpreter;
$bfi->memoryAutoScaling = true;
$program = $bfi->prefilter($program);
$bfi->loadProgram($program);
//$bfi->byteStream = [65,66,67,68,69,70,0]; Example input, terminating with 0
$bfi->run();
echo "BFOUTPUT> " . $bfi->output;
