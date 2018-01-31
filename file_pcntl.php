<?php
/**
 * @name file_pcntl.php
 * @desc read file with pcntl
 * @author MikeGuo(mike.gp@foxmail.com)
 */

define("MAX_PROC_NUM", 10);
define("MAX_MEMORY_DEFAULT", 1024);

//ini_set("memory_limit", '10');

function read_file($proc_id, $path, $size, $mem) {
    $fp = fopen($path, 'rb');
    if (!$fp) {
        echo "Error! Invalid file handler in Proc " . $proc_id . ".\n";
    } else {
        if (flock($fp, LOCK_SH)) {
            fseek($fp, ($proc_id - 1) * $size);
            $loop = 1;
            while ($size > 0 && !feof($fp)) {

                // roll back
                $str_head = "";
                if ($proc_id > 1 || $loop > 1) {
                    $char = null;
                    $off = 0;
                    fseek($fp, 1, SEEK_CUR);
                    do {
                        $off++;
                        fseek($fp, -2, SEEK_CUR);
                        $char = fread($fp, 1);
                    } while ($char != PHP_EOL && $off < $mem);
                    if ($off > 1) {
                        $str_head = fread($fp, $off - 1);
                        //echo "Proc $proc_id: rollback $off ---\n" . "$str_head\n" . "---\n";
                    }
                }

                // normal read
                $str = fread($fp, min($size, $mem));
                $len = strlen($str);
                $size -= $len;
                //echo "Proc $proc_id: normal $len ---\n" . "$str\n" . "---\n";

                // cut tail
                $off = 0;
                while ($str[$len - 1 - $off] != PHP_EOL && $off < $len - 1) {
                    $off++;
                }
                $str_new = substr($str, 0, $len - 1 - $off);
                //echo "Proc $proc_id: cut_tail $off ---\n" . "$str_new\n" . "---\n";

                // final output
                echo "Proc $proc_id: final ---\n" . "$str_head$str_new\n" . "---\n";

                $loop++;
            }
            flock($fp, LOCK_UN);
        }
    }
    fclose($fp);
}

function proc_manage($path, $num, $mem) {
    $size = filesize($path);
    $size = intval($size / $num) + 1;

    for ($i = 1; $i <= $num; $i++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo "Error! Fork Proc " . $i . " failed.\n";
        } elseif ($pid > 0) {
            //echo "Parent pid = " . posix_getpid() . ".\n";
            //$pid_arr[] = $pid;
        } else {
            //echo "Child pid = " . posix_getpid() . ".\n";
            read_file($i, $path, $size, $mem);
            exit;
        }
    }

    //var_dump($pid_arr);

    // Wait for Child Proc complete
    while (pcntl_waitpid(0, $status) != -1) {
        //var_dump($status);
        $status = pcntl_wexitstatus($status);
        echo "Child Proc complete - status $status.\n";
    }
}

function pow_of_2($num) {
    while ($num > 1) {
        if ($num % 2 == 1)  return false;
        $num = intval($num / 2);
    }
    if ($num == 1)  return true;
    else  return false;
}

function parse_args($argc, $argv) {
    if ($argc < 3 || $argc > 4) {
        echo "Error! Wrong args number.\n";
        echo "Usage: php file_pcntl.php file_path proc_num [max_mem]\n";
        return;
    }
    $path = $argv[1];
    $num = $argv[2];
    $mem = MAX_MEMORY_DEFAULT;
    if (!file_exists($path)) {
        echo "Error! File doesn't exist.\n";
        return;
    }
    if ($num < 1 || $num > MAX_PROC_NUM) {
        echo "Error! Wrong proc number, supposed to be in 1 ~ " . MAX_PROC_NUM . ".\n";
        return;
    }
    if ($argc == 4) {
        if ($argv[3] > 0)  $mem = $argv[3];
    }
    proc_manage($path, $num, $mem);
}

parse_args($argc, $argv);
