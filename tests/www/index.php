<?php

require dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if (! in_array('haphpy', stream_get_wrappers())) {
  stream_wrapper_register('haphpy', '\\Haphpy\\Module');
}

require 'haphpy://lib/base.haphpy';
require 'haphpy://lib/router.haphpy';
require 'haphpy://lib/session.haphpy';

require 'haphpy://app/helpers.haphpy';
require 'haphpy://app/routes.haphpy';

session_start();
run();
