<?php
require 'vendor/autoload.php';


$seven_days = 518400;
$one_day = 86400;
$unloadingTime = time();

$access_token = "kcTIPyXcTKq2hyt6pC9ikwapy52FGKTtEN2e8G73";
//объект для guzzle запроса
$client = new GuzzleHttp\Client ([
    'headers' => [
        "Authorization" => "Bearer ".$access_token,
        'Content-Type' => 'application/json'
    ]
]);
echo(strtotime('2022-04-24T00:00:00') - strtotime('2022-04-18T00:00:00'));

$operationsResponse = $client->request('POST', 'https://api.avito.ru/core/v1/accounts/operations_history/', [
    "json" => [
        "dateTimeFrom" =>  date("Y-m-d\TH:i:s", $unloadingTime - $one_day),
        "dateTimeTo" =>  date("Y-m-d\TH:i:s", $unloadingTime)
    ]]);

$operations = json_decode($operationsResponse->getBody()->getContents());
print_r($operations);
$lostMoney = 0;
$tariffArr = [];
$values_arr = [['ar', 2]];
foreach ($operations->result->operations as $oper) {
    if(property_exists($oper, 'serviceType')) {
        $lostMoney += intval($oper->amountRub);
    if ($oper->serviceType == 'tariff'){
            array_push($tariffArr, $oper->amountRub, $oper->operationName);
    }
  }
}
  echo(sizeof($tariffArr));
  $a = sizeof(($tariffArr));
    for ($i = 0; $i < $a - 1; $i++) {
        array_push($values_arr[$i], $tariffArr[$i], $tariffArr[$i+1]);
    }


  print_r($values_arr);

?>