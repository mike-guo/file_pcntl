# file_pcntl

Read File Module with multi-process, mainly to deal with big file.

Written in PHP language.

Extension needed: pcntl, posix.

System support: UNIX/Linux/MacOS, but no Windows (because Windows has no pcntl extension).

Usage: php file_pcntl.php file_path proc_num [max_mem]

For example: php file_pcntl.php test.log 4 20

Note: Memory size is calculated in B(Byte).
