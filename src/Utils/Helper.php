<?php

namespace App\Utils;

class Helper
{

  public function trim($text)
  {
    $str = trim($text);
    $str = preg_replace('!\s+!', ' ', $str);
    $str = str_replace("\r\n", "", $str);
    return $str;
  }
}
