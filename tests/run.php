<?php

// Usage:
//   php tests/run.php tests/samples/script.haphpy | php

require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$tpl = isset($argv[1]) ? file_get_contents($argv[1]) : die("Missing script!");

$view = Haphpy\Parser::parse($tpl);

echo $view;
