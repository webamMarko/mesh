<?php
$rootDir = getcwd(); // Gets the root directory of the project
$serviceFile = $rootDir . '/service.json';

if (!file_exists($serviceFile)) {
    fwrite(STDERR, "Error: 'service.json' not found in the project root.\n");
    exit(1); // Exit with an error code
}

exit(0); // Success
