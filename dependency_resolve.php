#!/usr/local/bin/php
<?php declare(strict_types=1);

const DRSLV_VERSION                  = '1.0.1';

const DRSLV_SUCCESS                  = 0;
const DRSLV_ERROR_WRONG_PARAMS       = 1;
const DRSLV_ERROR_PROCESS_FAILED     = 2;
const DRSLV_ERROR_LDD_NOT_FOUND      = 3;
const DRSLV_ERROR_LDD_NOT_SUPPORTED  = 4;
const DRSLV_ERROR_BINARY_NOT_FOUND   = 5;

const DRSLV_PROCESS_WAIT_USEC        = 10;

/** @param list<string> $argv */
function usage(array $argv): void {
    $version = DRSLV_VERSION;
    $binary = $argv[0] ?? '<unknown>';

    echo <<<EOS
    dependency resolve - distroless packaging support v${version}
    
    usage: ${binary} [ldd_binary_path] ...[target_binary_paths]
    
    EOS;
}

function version(): void {
    $version = DRSLV_VERSION;
    fputs(STDOUT, "${version}\n");
}

/** 
 * @param list<string> $arguments 
 * @return array{0: string, 1: string}
 */
function proc_exec(string $binary_path, array $arguments): array {
    /** @var list<string> $args */
    $args = [$binary_path, ...$arguments];
    $proc = proc_open(
        $args,
        [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ],
        $pipes,
        '/',
        null,
    );
    if ($proc === false) {
        fputs(STDERR, "process execution failed: ${binary_path}\n");
        exit(DRSLV_ERROR_PROCESS_FAILED);
    }
    while (proc_get_status($proc)['running']) {
        usleep(DRSLV_PROCESS_WAIT_USEC);
    }
    
    $stdout = stream_get_contents($pipes[1]);
    $stdout = ($stdout === false) ? '' : trim($stdout);
    $stderr = stream_get_contents($pipes[2]);
    $stderr = ($stderr === false) ? '' : trim ($stderr);
    
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    return [$stdout, $stderr];
}

function check_ldd(string $ldd_path): void {
    if (!file_exists($ldd_path)) {
        fputs(STDERR, "ldd not found: ${ldd_path}\n");
        exit(DRSLV_ERROR_LDD_NOT_FOUND);
    }
    [$stdout, $stderr] = proc_exec($ldd_path, ['--version']);
    if (
        !str_contains($stdout, 'GLIBC') &&  // glibc
        !str_contains($stderr, 'musl libc') // musl
    ) {
        fputs(STDERR, "ldd executable not supported: ${ldd_path}\n");
        exit(DRSLV_ERROR_LDD_NOT_SUPPORTED);
    }
}

function check_binary(string $binary_path): void {
    if (!file_exists($binary_path)) {
        fputs(STDERR, "binary not found: ${binary_path}\n");
        exit(DRSLV_ERROR_BINARY_NOT_FOUND);
    }
}

/** @return list<string> */
function dependency_resolve(string $ldd_path, string $binary_path): array {
    $result = [$binary_path];
    $fi = new SplFileInfo($binary_path);

    if ($fi->isLink()) {
        // binary is symlink, resolve recursively.
        $result = array_unique(array_merge($result, dependency_resolve($ldd_path, $fi->getRealPath())));
    } else {
        // binary is file, resolve dependency from ldd.
        [$stdout, $stderr] = proc_exec($ldd_path, [$binary_path]);
        if (
            str_contains($stdout, 'not a dynamic executable') || // glibc
            str_contains($stdout, 'Not a valid dynamic program') // musl
        ) {
            return $result;
        }

        foreach (explode("\n", $stdout) as $line) {
            $library = explode(' ', trim($line))[2] ?? '';
            if ($library === '') {
                continue;
            }
            $result = array_unique(array_merge($result, dependency_resolve($ldd_path, $library)));
        }
    }

    return $result;
}

// check arguments
if ($argc < 3) {
    $arg = $argv[1] ?? '';
    $return_code = DRSLV_SUCCESS;

    switch (true) {
        case $arg === '-v':
        case $arg === '--version':
            version();
            exit($return_code);
        default:
            $return_code = DRSLV_ERROR_WRONG_PARAMS;
        case $arg === '-h':
        case $arg === '--help':
            usage($argv);
            exit($return_code);
    }
}

// check ldd compatibility
$ldd_path = $argv[1] ?? '';
check_ldd($ldd_path);

// check binary paths
$binary_paths = [];
for ($i = 2; $i < $argc; $i++) {
    $binary_path = $argv[$i];
    check_binary($binary_path);
    $binary_paths[] = $binary_path;
}

// generate dependencies list
$list = [];
foreach ($binary_paths as $binary_path) {
    $list = array_unique(
        array_merge($list, dependency_resolve($ldd_path, $binary_path))
    );
}

// sort and output lists
sort($list);
foreach ($list as $entry) {
    fputs(STDOUT, "${entry}\n");
}
exit(DRSLV_SUCCESS);
