<?php

require_once "../vendor/autoload.php";

use Pju\Mesh\Services\Stock;

$stock = new Stock();
$result = $stock->get(["sku" => '0487-BLA', "warehouse" => "Vrhnika"]);
print_r($result);