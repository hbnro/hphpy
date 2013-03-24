<?php

// Usage:
//   php tests/run.php tests/samples/script.hphpy

require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if (! in_array('hphpy', stream_get_wrappers())) {
  stream_wrapper_register('hphpy', '\\Hphpy\\Module');
}

echo isset($argv[1]) ? file_get_contents("hphpy://$argv[1]") : die("Missing script!");
