<?php

namespace App\Controller;

use App\Request;
use App\Utils\Helper;
use App\View;

class Controller
{
  private const DEFAULT_ACTION = 'home';

  private $request;
  private $helper;

  function __construct(Request $request, Helper $helper)
  {
    $this->view = new View();
    $this->request = $request;
    $this->helper = $helper;
  }

  final public function run()
  {

    $action = $this->action() . 'Action';
    if (!method_exists($this, $action)) {
      $action = self::DEFAULT_ACTION . 'Action';
    }
    $this->$action();
  }

  private function action()
  {
    return $this->request->getParam('action', self::DEFAULT_ACTION);
  }

  private function homeAction()
  {
    $this->view->render('home');
  }

  private function parseFileAction()
  {
    $file = getcwd() . '/wo_for_parse.html';

    $html = file_get_contents($file);
    $html = str_replace(' & ', ' &amp; ', $html);

    // Load DOM
    $dom = $this->loadHtml($html);

    // Get DOM Elements
    $elements = $this->getElements($dom);


    $csvFile = $this->generateCSV($elements);

    readfile($csvFile);

    // return Response::download($csvFile, 'work_order.csv');
  }

  private function generateCSV($data)
  {
    $headers = array(
      'Content-Type' => 'text/csv; charset=utf-8',
      'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
      'Content-Disposition' => 'attachment; filename=download.csv',
      'Expires' => '0',
      'Pragma' => 'public',
    );

    $filename =  "work_order.csv";
    $handle = fopen($filename, 'w');

    fputcsv($handle, $data);
    fclose($handle);

    return $filename;
  }

  private function loadHtml($html)
  {
    $dom = new \DomDocument;
    @$dom->loadHtml($html);

    return $dom;
  }

  private function toDateTime($date)
  {
    $str = $this->helper->trim($date);
    $unixtime = strtotime($str);
    $dateTime = date("(Y-m-d H:i)", $unixtime);

    return $dateTime;
  }


  private function currencyToFloat($currency)
  {
    // convert "," to "."
    $s = str_replace(',', '.', $currency);

    // remove everything except numbers and dot "."
    $s = preg_replace("/[^0-9\.]/", "", $s);

    // remove all seperators from first part and keep the end
    $s = str_replace('.', '', substr($s, 0, -3)) . substr($s, -3);

    return $s;
  }

  private function regStreetName($text)
  {
    $text = $this->helper->trim($text);
    if (preg_match('/[A-Z|a-z]+ [S|s]treet [0-9]+/', $text, $matches)) {
      return $matches[0];
    }
  }

  private function regCityName($text)
  {
    $text = $this->helper->trim($text);

    if (preg_match('/(?<=[S|s]treet [0-9]{3}) \w+/', $text, $matches)) {
      return $matches[0];
    }
  }

  private function regStateName($text)
  {
    $text = $this->helper->trim($text);

    if (preg_match('/[A-Z]{2}/', $text, $matches)) {
      return $matches[0];
    }
  }

  private function regZipCode($text)
  {
    $text = $this->helper->trim($text);

    if (preg_match('/[0-9]{5}/', $text, $matches)) {
      return $matches[0];
    }
  }

  private function phoneToNumber($phone)
  {
    $phone = $this->helper->trim($phone);
    $phone = str_replace('-', '', $phone);
    return $phone;
  }

  public function getElements($html)
  {
    $content = [
      'Tracking Number' => $this->helper->trim($html->getElementById('wo_number')->nodeValue),
      'PO Number' => $this->helper->trim($html->getElementById('po_number')->nodeValue),
      'Scheduled' => $this->toDateTime($html->getElementById('scheduled_date')->nodeValue),
      'Customer' => $this->helper->trim($html->getElementById('customer')->nodeValue),
      'Trade' => $this->helper->trim($html->getElementById('trade')->nodeValue),
      'NTE' => $this->currencyToFloat($html->getElementById('nte')->nodeValue),
      'Store ID' => $this->helper->trim($html->getElementById('location_name')->nodeValue),
      'Street' => $this->regStreetName($html->getElementById('location_address')->nodeValue),
      'City' => $this->regCityName($html->getElementById('location_address')->nodeValue),
      'State' => $this->regStateName($html->getElementById('location_address')->nodeValue),
      'Zip' => $this->regZipCode($html->getElementById('location_address')->nodeValue),
      'Phone' => $this->phoneToNumber($html->getElementById('location_phone')->nodeValue),
    ];
    return $content;
  }

}
