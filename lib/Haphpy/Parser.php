<?php

namespace Haphpy;

class Parser
{

  private static $indent = 2;

  private static $lambda = '\s*(?:\(([^()]*?)\))?\s*~>(.*)?';
  private static $ifthen = '\b(?:(?:else\s*?)?if|while|switch|for(?:each)?|catch)\b';
  private static $block = '\b(?:else|class|do|try|namespace)\b';
  private static $isfn = '\b(?:[\s\w]*function)\b';

  private static $esc = array(
                    '/[\r\n]/' => "\n",
                    "/\s*, *\n+\s*/" => ', ',
                    "/\s*\\\\ *\n+\s*/" => ' ',
                  );



  public static function parse($source)
  {
    $tokens = static::tree($source);
    $output = static::fix($tokens);

    $output = \Haphpy\Helpers::repare($output);

    return $output;
  }


  private static function tree($source)
  {
    $source = preg_replace(array_keys(static::$esc), static::$esc, $source);

    if (preg_match('/^ +(?=\S)/m', $source, $match)) {
      static::$indent = strlen($match[0]);
    }


    $code  = '';
    $stack = array();
    $lines = explode("\n", $source);
    $lines = array_values(array_filter(array_map('rtrim', $lines), 'strlen'));

    $unescape = '\\Haphpy\\Helpers::unescape';


    foreach ($lines as $i => $line) {
      $key    = '$out';
      $tab    = strlen($line) - strlen(ltrim($line));
      $next   = isset($lines[$i + 1]) ? $lines[$i + 1] : NULL;
      $indent = strlen($next) - strlen(ltrim($next));


      if ($indent > $tab) {
        $stack []= substr(mt_rand(), 0, 7);
      }

      foreach ($stack as $top) {
        $key .= "['#$top']";
      }

      if ($indent < $tab) {
        $dec = $tab - $indent;

        while ($dec > 0) {
          array_pop($stack);
          $dec -= static::$indent;
        }
      }

      $code .= $key;

      $line  = \Haphpy\Helpers::escape($line);

      $code .= $indent > $tab ? "=array(-1=>$unescape('$line'))" : "[]=$unescape('$line')";
      $code .= ";\n";
    }


    @eval($code);

    if (empty($out)) {
      return FALSE;
    }

    return $out;
  }

  private static function open($value)
  {
    $out = array();

    $isfn  = '/^\s*' . static::$isfn . '/';
    $block  = '/^\s*' . static::$block . '/';
    $ifthen  = '/^\s*' . static::$ifthen . '/';


    if ( ! is_scalar($value)) {
      return $value;
    }


    if (preg_match($isfn, $value, $test)) {
      $parts   = explode(' ', $test[0]);
      $match   = array_pop($parts);

      $value   = explode($match, $value, 2);
      $prefix  = array_shift($value);

      $values  = explode(' ', trim(array_pop($value)));
      $prefix .= "$match " . array_shift($values);
      $value   = join(' ', array_map('trim', $values));

      return "$prefix($value) {";
    } elseif (preg_match($ifthen, $value, $test)) {
      $parts = array_map('trim', explode(trim($test[0]), trim($value), 2));
      $value = join('', array_filter($parts, 'strlen'));

      return "{$test[0]} ($value) {";
    } elseif (preg_match($block, $value)) {
      return "$value {";
    }

    return $value;
  }

  private static function close($value)
  {
    $out = array();

    $block  = '/^\s*(?:' . static::$ifthen . '|' . static::$block . '|' . static::$isfn . ')/';
    $lambda = '/' . static::$lambda . '/';


    if ( ! is_scalar($value)) {
      return;
    }


    if (preg_match($lambda, $value)) {
      $lft = substr_count($value, '(');
      $rgt = substr_count($value, ')');

      $close = str_repeat(')', $lft - $rgt);

      return "}$close : false;";
    } elseif (preg_match($block, $value)) {
      return '}';
    }
  }

  private static function fix($tree, $indent = 0)
  {
    $out = array();
    $span = static::span($indent);

    if ( ! empty($tree[-1])) {

      ($overwrite = static::open($tree[-1])) && $tree[-1] = $overwrite;

      $sub[$tree[-1]] = array_slice($tree, 1);

      if ($suffix = static::close($tree[-1])) {
        $sub[$tree[-1]] []= $span . $suffix;
      }

      $out []= static::fix($sub, $indent + 1);
    } else {
      foreach ($tree as $key => $value) {
        if (is_string($value)) {
          ($overwrite = static::lambda($value, TRUE)) && $value = $overwrite;
          ($overwrite = static::open($value)) && $value = $overwrite;

          $close = preg_match('/^\s*(?:\$[_a-zA-Z]|\w)/', $value) ? ';' : '';
          $out []= static::ln($value . $close, '', $indent);
        } else {
          ($overwrite = static::lambda($key)) && $key = $overwrite;

          $out []= static::ln(preg_match('/#\d{1,7}/', $key) ? FALSE : $key, static::fix($value, $indent), $indent);
        }
      }
    }

    $out = join("\n", $out);

    return $out;
  }

  private static function ln($key, $text = '', $indent = 0)
  {
    $out = array();
    $span = static::span($indent);

    ($key = ($key)) && $out []= $key;
    ($text = trim($text)) && $out []= $span . $text;

    return join("\n", $out);
  }

  private static function span($indent = 0)
  {
    return $indent > 0 ? str_repeat(str_repeat(' ', static::$indent), $indent) : '';
  }

  private static function lambda($value, $inline = FALSE)
  {
    $closure = '/' . static::$lambda . '/';

    if (preg_match($closure, $value, $test)) {
      @list($prefix, $suffix) = explode($test[0], $value);

      $function  = "!! (\$__ = get_defined_vars()) ? function ($test[1]) use (\$__) {";
      $function .= " extract(\$__, EXTR_SKIP | EXTR_REFS); unset(\$__);";

      if ($inline) {
        $suffix = trim($test[2]);
        return "$prefix $function $suffix; } : false";
      } else {
        return "$prefix $function";
      }
    }
  }

}
