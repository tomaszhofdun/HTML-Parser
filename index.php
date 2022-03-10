<?php


spl_autoload_register(function (string $classNamespace) {
  $path = str_replace(['\\', 'App/'], ['/', ''], $classNamespace);
  $path = "src/$path.php";
  require_once($path);
});

require_once("src/Utils/debug.php");

use App\Controller\Controller;
use App\Request;
use App\Utils\Helper;

$request = new Request($_GET, $_POST, $_SERVER);
$helper = new Helper();

(new Controller($request, $helper))->run();