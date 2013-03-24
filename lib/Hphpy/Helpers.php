<?php

namespace Hphpy;

class Helpers
{

  private static $qt = array(
                    '\\' => '&uß;',
                    '"' => '&u¥;',
                    "'" => '&u€;',
                    '$' => '&u£;',
                  );

  private static $fix = array(
                    '/\}\s*(catch|else)/' => '} \\1',
                    '/(?<!:):([_a-zA-Z][\w-]+)/' => "'\\1'",
                  );

  public static function escape($text, $rev = FALSE)
  {
    return strtr($text, $rev ? array_flip(static::$qt) : static::$qt);
  }

  public static function unescape($text)
  {
    return static::escape($text, TRUE);
  }

  public static function repare($code)
  {
    return preg_replace(array_keys(static::$fix), static::$fix, $code);
  }

}
