# BrainFuckInterpreter
Brainfuck is, indeed, a language. You can find an interpreter for it in nearly every language, including in itself. A Turing-complete language, you can have a lot of fun with it, but you'll never find an actual application developed in it.

Anyways, I wanted to be able to run the programs, and I wanted to learn the language. The easiest way was to write an interpreter. So, here it is.

Yes, it has a bad word in the title. I'm sorry. It is a [real thing](https://en.wikipedia.org/wiki/Brainfuck) that I didn't name.

Wikipedia has the entire function list (which is eight individual constructs) if you'd like to learn it.


## Files in this Project

### loadFile.php
Accepts a command-line argument for a filename, initializes the interpreter, and starts the execution.

### programs/\*.bf
A few example programs you can try out.

### /class/BrainFuckInterpreter.class.php
This is where the magic happens. The good 'ol object.

| Property | Type | Visibility | Usage |
| -------- | ---- | ---------- | ----- |
| limitCycles | Int | Public | Terminates program if this cycle count is reached. Useful for stopping infinite loops. |
| outputDebug | Bool | Public | Enables or disables verbose debugging output |
| output | string | Public | The actual program output |
| cellMaxValue | Int | Public | The maximum integer value storable in a cell. 255 is the default, but adjustable for fun |
| memoryAutoScaling | Bool | Public | Instructs the interpreter to allot as many memory cells as required to run the program |
| cycleCounter | Int | Private | Count of cycles processed |
| program | String | Private | Storage string of actual program |
| cells | Array | Private | Memory Cell Array |
| pointer | Int | Private | Points to current memory cell |
| programCounter | Int | Private | Points to the current instruction of the program |
| braceSets | array | Private | Used internally to maintain bracket relationship information |
| memoryProvisioned | Bool | Private | Indiates if memory has been provisioned |


| Method | Properties | Returns | Visibility | Usage |
| ------ | ---------- | ------- | ---------- | ----- |
| setCell | int cell, int value | bool | Private | Writes *value* to *cell*, handles memory scaling, out-of-bound exceptions, memory manipulation, and value wrapping |
| getCell | int cell | int | Private | Reads value from *cell* and returns. |
| provisionMemory | int cellCount | bool | Public | Provisions *cellCount* cells of memory for the program to run |
| prefilter | string program | string | Public | Accepts *program* and strips all invalid questions. Each character is evaluated at run time, running this step can greatly reduce execution time |
| loadProgram | string program | bool | Public | Loads the *program* into memory, initializes program counter |
| findBraceSets | string str, string braces | void | Private | Parses the *str* looking for bracket matches for looping |
| step | n/a | void | Public | Steps the interpreter through the program. |
| run | n/a | void | Public | Runs the program by calling step() until the end of the program, or if an exception happens.



