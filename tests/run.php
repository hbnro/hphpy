<?php

// Usage:
//   php tests/run.php tests/samples/script.haphpy

require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if (! in_array('haphpy', stream_get_wrappers())) {
  stream_wrapper_register('haphpy', '\\Haphpy\\Module');
}

echo isset($argv[1]) ? file_get_contents("haphpy://$argv[1]") : die("Missing script!");
