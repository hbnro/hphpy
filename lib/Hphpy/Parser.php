<?php

namespace Hphpy;

class Parser
{

  private static $indent = 2;

  private static $lambda = '\s*(?:\(([^()]*?)\))?\s*~>(.*)?';
  private static $ifthen = '\b(?:(?:else\s*?)?if|while|switch|for(?:each)?|catch)\b';
  private static $block = '\b(?:else|trait|interface|[\s\w]*class|do|try|namespace|finally)\b';
  private static $isfn = '\b(?:[\s\w]*function)\b';



  public static function parse($source)
  {
    $source = \Hphpy\Helpers::prepare($source);
    $source = \Hphpy\Helpers::extract($source, $tmp);

    $tokens = static::tree($source);
    $output = static::fix($tokens);

    $output = \Hphpy\Helpers::restore($output, $tmp);
    $output = \Hphpy\Helpers::repare($output);

    return "$output\n";
  }

  private static function tree($source)
  {
    if (preg_match('/^ +(?=\S)/m', $source, $match)) {
      static::$indent = strlen($match[0]);
    }

    $code  = '';
    $stack = array();
    $lines = explode("\n", $source);
    $lines = array_values(array_filter(array_map('rtrim', $lines), 'strlen'));

    $unescape = '\\Hphpy\\Helpers::unescape';

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

      $line  = \Hphpy\Helpers::escape($line);

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

    $isfn  = '/^\s*' . static::$isfn . '/i';
    $block  = '/^\s*' . static::$block . '/i';
    $ifthen  = '/^\s*' . static::$ifthen . '/i';

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

    $block  = '/^\s*(?:' . static::$ifthen . '|' . static::$block . '|' . static::$isfn . ')/i';
    $lambda = '/' . static::$lambda . '/i';

    if ( ! is_scalar($value)) {
      return;
    }

    if (preg_match($lambda, $value)) {
      $lft = substr_count($value, '(');
      $rgt = substr_count($value, ')');

      $close = str_repeat(')', $lft - $rgt);

      return "} : false$close;";
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

      static::ret($sub[$tree[-1]]);

      if ($suffix = static::close($tree[-1])) {
        $sub[$tree[-1]] []= $span . $suffix;
      }

      $out []= static::fix($sub, $indent + 1);
    } elseif ($tree) {
      foreach ($tree as $key => $value) {
        if (is_string($value)) {
          ($overwrite = static::lambda($value, TRUE)) && $value = $overwrite;
          ($overwrite = static::open($value)) && $value = $overwrite;

          $close = '';

          if (substr($value, -1) === '{') {
            $value = "$value }";
            $close = '';
          } elseif (preg_match('/^\s*(?:\$[_a-zA-Z]|\w)/', $value)) {
            $close = ';';
          }

          $out []= static::ln($value . $close, '', $indent);
        } else {
          if (substr($key, -2) === '=>') {
            $value = array_map('trim', \Hphpy\Helpers::flatten($value));
            $value = array_filter($value, 'strlen');

            $last = array_pop($value);

            if (strpos($last, 'return') === 0) {
              $last = trim(substr($last, 6));
            }

            $value []= $last;

            $key   = trim(substr($key, 0, -2));
            $value = join(', ', $value);

            $out []= "$key = [$value];";
          } else {
            ($overwrite = static::lambda($key)) && $key = $overwrite;

            $out []= static::ln(preg_match('/#\d{1,7}/', $key) ? FALSE : $key, static::fix($value, $indent), $indent);
          }
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

  private static function ret(&$set, $indent = 0)
  {
    if (is_array(end($set))) {
      return;
    }

    $last = array_pop($set);

    if (preg_match('/^\s*[$\'"[:]/', $last)) {
      $last = static::span($indent + 1) . 'return ' . trim($last);
    }

    $set []= $last;
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

      $function  = "!! (\$__ = get_defined_vars()) | 1 ? function ($test[1]) use (\$__) {";
      $function .= " extract(\$__, EXTR_SKIP | EXTR_REFS); unset(\$__);";

      if ($inline) {
        $lft = substr_count($value, '(');
        $rgt = substr_count($value, ')');

        $close = str_repeat(')', $lft - $rgt);

        $suffix = trim($test[2]);
        $suffix = $suffix ? " $suffix;" : '';

        return "$prefix $function$suffix } : false$close";
      } else {
        return "$prefix $function";
      }
    }
  }

}
