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
                    '/([_A-Z][A-Z\d_]+)\s*=\s*([^;]+?)(?=\s*;)/' => "defined('\\1') || define('\\1', \\2)",
                    '/(?<![:\w]):([_a-zA-Z][\w-]+)(?=\s*(?:[,)\]]|=>))/' => "'\\1'",
                    '/^\s*\}\s*(catch|else|finally)/m' => '} \\1',
                  );

  private static $esc = array(
                    '/[\r\n]/' => "\n",
                    "/\s*, *\n+\s*/" => ', ',
                    "/(\s*)\\\\ *\n+\s*/" => '\\1',
                  );

  private static $pair = array(
                    '&mc#;' => '/###(.+?)###/Us',
                    '&us#;' => '/"""(.+?)"""/Us',
                    '&ls#;' => "/'''(.+?)'''/Us",
                  );



  public static function extract($text, &$out)
  {
    $out = array();

    foreach (static::$pair as $key => $expr) {
      $text = preg_replace_callback($expr, function ($match)
        use (&$out, $key) {
          $key = str_replace('#', mt_rand(), $key);
          $out[$key] = $match[1];

          return $key;
        }, $text);
    }

    return $text;
  }

  public static function restore($text, array $set)
  {
    $text = preg_replace_callback('/&mc\d+;/', function ($match)
      use (&$set) {
        return "/*\n" . preg_replace('/^/m', ' * ', trim($set[$match[0]])) . "\n */";
      }, $text);

    $text = preg_replace_callback('/&us\d+;/', function ($match)
      use (&$set) {
        $rid = strtoupper(uniqid('USTR'));

        return "<<<$rid\n{$set[$match[0]]}\n$rid";
      }, $text);

    $text = preg_replace_callback('/&ls\d+;/', function ($match)
      use (&$set) {
        $rid = strtoupper(uniqid('LSTR'));

        return "<<<'$rid'\n{$set[$match[0]]}\n$rid";
      }, $text);

    return $text;
  }

  public static function prepare($text)
  {
    return preg_replace(array_keys(static::$esc), static::$esc, $text);
  }

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

  public static function flatten($set, $out = array())
  {
    foreach ($set as $one) {
      is_array($one) ? $out = static::flatten($one, $out) : $out []= $one;
    }
    return $out;
  }

}
