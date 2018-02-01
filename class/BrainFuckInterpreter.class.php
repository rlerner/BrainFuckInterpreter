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
class BrainFuckInterpreter {

	/**
	 * Provides a maximum ::cycleCounter to run until. Prevents infinite loops. Set to "0" to disable. Does not count during ::step(), only ::run()
	 * @var int
	 */
	public $limitCycles = 0;

	/**
	 * Enables or disables verbose debugging output for the BF program.
	 * @var boolean
	 */
	public $outputDebug = false;

	/**
	 * Output string
	 * @var string
	 */
	public $output = "";

	/**
	 * Maximum value stored in a memory cell before wrapping back to "0". Typically 255 for 8-bit cell size.
	 * @var integer
	 */
	public $cellMaxValue = 255; // Wraps to 0

	/**
	 * Determines if the memory cell count will autoscale to the programs requirements (making provisioning unneeded)
	 * @var boolean
	 */
	public $memoryAutoScaling = false;

	/**
	 * When programs require an input, the program will use CLI functions to ask the user for that input, unless a valid
	 * byte is present in this stream. Each array value is a different value.
	 */
	public $byteStream = [];

	/**
	 * Current location of the array pointer used in byteStream.
	 */
	private $byteStreamPointer = 0;

	/**
	 * Counts cycles (steps) processed through the program, primarily for optimization, debugging, or infinite loop protection.
	 * @var integer
	 */
	private $cycleCounter = 0;

	/**
	 * Stores the brainfuck program
	 * @var string
	 */
	private $program = "";

	/**
	 * Stores memory cells and data
	 * @var array
	 */
	private $cells = [];

	/**
	 * Current memory cell pointer (memory location)
	 * @var int
	 */
	private $pointer = 0;

	/**
	 * Current program counter (PC) with the location of the current instruction within ::program
	 * @var int
	 */
	private $programCounter = 0;

	/**
	 * Stores bracket relationship data for looping
	 * @var array
	 */
	private $braceSets = [];

	/**
	 * Stores value in cell. Function controls auto-boundary scaling, out-of-bound exceptions, memory manipulation, and value wrapping.
	 * @param integer $cell
	 * @param integer $value
	 */
	private function setCell(int $cell, int $value): bool {

		if (!$this->memoryAutoScaling) {
			if ($cell<0 || $cell>count($this->cells)-1) {
				throw new \Exception("Cell manipulation out of bounds, trying to write to cell $cell, provisioned cells: 0-" . (count($this->cells)-1));
			}
		} else {
			if (!isset($this->cells[$cell])) {
				$this->cells[$cell] = 0;
			}
		}
		if ($value>$this->cellMaxValue) {
			$value = 0;
		}
		if ($value<0) {
			$value = 255;
		}
		$this->cells[$cell] = $value;
		return true;
	}

	/**
	 * Retrieves values from memory cells. Function controls auto-boundary scaling.
	 * @param integer $cell
	 */
	private function getCell(int $cell): int {
		if (!$this->memoryAutoScaling && !isset($this->cells[$cell])) {
			throw new \Exception("Out-Of-Bounds Error: Memory Cell $cell does not exist.");
		}
		if ($this->memoryAutoScaling && !isset($this->cells[$cell])) {
			$this->cells[$cell] = 0;
		}
		return $this->cells[$cell];
	}

	/**
	 * Initializes n cells to zero, ready for storage. (See ::memoryAutoScaling property)
	 * @param integer $cellCount
	 */
	public function provisionMemory(int $cellCount): bool {
		if ($cellCount<1) {
			throw new \Exception("Attempting to provision an invalid quantity of cells, trying to provision '$cellCount'");
		}
		for ($i=0;$i<$cellCount;$i++) {
			$this->cells[$i] = 0;
		}
		return true;
	}

	/**
	 * Prefiltering is an optional step that removes invalid characters from the provided program code.
	 * When BF is ran, every character counts as a "clock cycle" in the stepper. While comments will not
	 * impact the program, it can slow down processing. Prefiltering is an optional optimization.
	 * @param string $program
	*/
	public function prefilter(string $program): string {
		$program = str_split($program);
		$cleanProgram = "";
		foreach ($program as $v) {
			if (in_array($v,['>','<','+','-','.',',','[',']'])) {
				$cleanProgram .= $v;
			}
		}
		return $cleanProgram;
	}

	/**
	 * Loads program into the interpreter and initializes the program counter.
	 * @param string $program
	*/
	public function loadProgram(string $program): bool {
		// Sanity check to prevent running a program in a browser that reads STDIN, unless we're CLI or byteStream is provided.
		if (strstr(",",$program)!==false && PHP_SAPI!="cli") {
			if (count($this->byteStream)<1) {
				throw new \Exception("This BF program requires user input, which can only be provided in a CLI instance, or with the :: byteStream property.");
			}
		}

		$this->programCounter = 0;
		$this->program = trim($program).chr(0);
		$this->braceSets = $this->findBraceSets($this->program);
		return true;
	}

	/**
	 * Parses the program string and finds square bracket relationships for processing.
	 * @param string $str
	 * @param string $braces
	*/
	private function findBraceSets(string $str, string $braces="[]") {
		$stack = $result = [];
		$pos = -1;
		$end = strlen($str) + 1;

		while (true) {
			$p1 = strpos($str, $braces{0}, $pos + 1);
			$p2 = strpos($str, $braces{1}, $pos + 1);
			$pos = min(
				($p1 === false) ? $end : $p1,
				($p2 === false) ? $end : $p2);
			if ($pos == $end) {
				break;
			}
			if ($str{$pos} == $braces{0}) {
				array_push($stack, $pos);
			} else {
				if ($str{$pos} == $braces{1}) {
					if (!count($stack)) {
						throw new \Exception("Closing Bracket Mismatch at Offset $pos");
					} else {
						$result[array_pop($stack)] = $pos;
					}
				}
			}
		};
		if (count($stack)) {
			throw new \Exception("Opening Bracket Mismatch at Offset ".array_pop($stack));
		}
		ksort($result);
		return $result;
	}

	/**
	 * Executes the next instruction at ::programCounter in the ::program.
	*/
	public function step() {

		$instruction = substr($this->program,$this->programCounter,1);

		// Debugging Information
		if ($this->outputDebug) {
			// This debugger only shows the first 30 cells if they're initialized. If you need to see more, adjust it.
			for ($i=0;$i<30;$i++) {
				if (isset($this->cells[$i])) {
					echo $this->cells[$i] . ".";
				}
			}
			echo "\nCC=$this->cycleCounter, PC=$this->programCounter, PO=$this->pointer, IN=$instruction\n";
			echo $this->program . "\n";
			echo str_repeat(" ",$this->programCounter) . "^\n\n";
		}

		switch ($instruction) {
			case ">":
				$this->pointer++;
				break;
			case "<":
				$this->pointer--;
				break;
			case "+":
				$this->setCell($this->pointer,$this->getCell($this->pointer)+1);
				break;
			case "-":
				$this->setCell($this->pointer,$this->getCell($this->pointer)-1);
				break;
			case ".":
				$this->output .= chr($this->getCell($this->pointer));
				break;
			case ",":
				$input=null;
				while (!is_numeric($input)) {
					// Gets keyboard input from byteStream, or from actual keyboard if it doesn't exist.
					if (isset($this->byteStream[$this->byteStreamPointer])) {
						$input = $this->byteStream[$this->byteStreamPointer];
						$this->byteStreamPointer++;
					} else {
						echo "BFINPUT< ";
						$input = rtrim(fgets(STDIN,1024));
						echo "\n";
					}
				}
				$this->setCell($this->pointer,$input);
				break;
			case "[":
				if ($this->getCell($this->pointer)==0) {
					$this->programCounter = $this->braceSets[$this->programCounter];
					return true;
				}
				break;
			case "]":
				if ($this->getCell($this->pointer)!=0) {
					$newPos = array_search($this->programCounter,$this->braceSets);
					$this->programCounter = $newPos+1;
					return true;
				}
				break;
			case chr(0): //End of program
				return false;
				break;
		}
		$this->programCounter++;
		return true;
	}

	/**
	 * Run the program calling ::step() until program completes.
	*/
	public function run() {
		while (true) {
			$this->cycleCounter++;
			if ($this->limitCycles>0) {
				if ($this->cycleCounter>=$this->limitCycles) {
					throw new \Exception("Cycle Limiter Hit, Program Terminated at " . number_format($this->limitCycles) . " cycles.");
				}
			}
			$ret = $this->step();
			if ($ret==false) {
				return true;
			}
		}
	}
}
