<?php

define('START', microtime(TRUE));

require dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if (! in_array('hphpy', stream_get_wrappers())) {
  stream_wrapper_register('hphpy', '\\Hphpy\\Module');
}

require 'hphpy://lib/base.hphpy';
require 'hphpy://lib/router.hphpy';
require 'hphpy://lib/session.hphpy';

require 'hphpy://app/helpers.hphpy';
require 'hphpy://app/routes.hphpy';

session_start();
run();

echo '<br>&mdash;', ticks();
