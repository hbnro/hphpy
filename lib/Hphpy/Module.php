<?php

namespace Hphpy;

class Module
{

  protected $stat;

  protected $offset = 0;
  protected $buffer = '';


  public function stream_open($path, $mode, $options, &$opened_path)
  {
    $path = str_replace('hphpy://', '', $path);
    $this->buffer = file_get_contents($path);

    if ($this->buffer === FALSE) {
      $this->stat = stat($path);
      return FALSE;
    }

    $this->buffer = preg_replace('/^\s*<\?php\s*$/', '<' . '?php;', $this->buffer);
    $this->buffer = preg_replace('/\<\?\=/', '<' . '?php echo ',  $this->buffer);

    $this->buffer = \Hphpy\Parser::parse($this->buffer);

    $this->stat = stat($path);

    return TRUE;
  }

  public function url_stat()
  {
    return $this->stat;
  }

  public function stream_read($count)
  {
    $ret = substr($this->buffer, $this->offset, $count);
    $this->offset += strlen($ret);
    return $ret;
  }

  public function stream_tell()
  {
    return $this->offset;
  }

  public function stream_eof()
  {
    return $this->offset >= strlen($this->buffer);
  }

  public function stream_stat()
  {
    return $this->stat;
  }

  public function stream_seek($offset, $whence)
  {
    switch ($whence) {
      case SEEK_SET;
        if ($offset < strlen($this->buffer) && $offset >= 0) {
          $this->offset = $offset;
          return true;
        } else {
          return false;
        }

      case SEEK_CUR;
        if ($offset >= 0) {
          $this->offset += $offset;
          return true;
        } else {
          return false;
        }

      case SEEK_END;
        if (strlen($this->buffer) + $offset >= 0) {
          $this->offset = strlen($this->buffer) + $offset;
          return true;
        } else {
          return false;
        }

      default;
        return false;
    }
  }

}
