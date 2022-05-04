<?php
require 'vendor/autoload.php';
require 'myfunctions.php';
require 'constants.php';

$usr = 202741465;
ini_set('max_execution_time', '5000');
function get_items_information1($access_token, $usr) {
    
    $unloadingTime = time();//date('Y-m-d H:i:s');

    $client = new GuzzleHttp\Client ([
        'headers' => [
            "Authorization" => "Bearer ".$access_token,
            'Content-Type' => 'application/json'
        ]
    ]);
    $values_arr = [];
   //for ($p = 1;;$p++) {
        ///Получение активных объявлений, за одну итерацию - 100 штук.
        $response = $client->request('GET','https://api.avito.ru/core/v1/items',[
            "query" => ["per_page"=>100,
            "status" => ["active"],
            "page" => 2],
           
        ]);
        
        $items = json_decode($response->getBody()->getContents());
        //Проверка, существует ли страница с объявлением
        if ($items->resources) {
            
            //Пустой массив для выгружаемых данных и счетчик объявлений на получаемой из запроса странице 0 - 99
            
            $counter = 0;  
           

            foreach($items->resources as $i => $item) {
                echo $i." item processing...".PHP_EOL;
                //Получение более подробной информации о конкретном объявлении
                $response = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/202741465/items/'.$items->resources[$counter]->id);
                $idshka = json_decode($response->getBody()->getContents());
                
                //Получение информации о новых чатах
                $new_chats_counter = 0;
                $message_counter = 0;
                $chatsResponse = $client->request('GET', 'https://api.avito.ru/messenger/v1/accounts/202741465/chats', [
                    "query" => ["item_ids" => $item->id]
                ]);
                $chats = json_decode($chatsResponse->getBody()->getContents());

                if ($chats->chats) {

                    foreach($chats->chats as $ch => $chitem){
                        $one_day = 86400;
                        if ($chitem->created > $unloadingTime - $one_day) {
                            $new_chats_counter+=1;
                        }
                        
                        if($chitem->updated > $unloadingTime - $one_day){
                            $usr = 202741465;
                            $msgResponse = $client->request('GET', 'https://api.avito.ru/messenger/v2/accounts/202741465/chats/'.$chitem->id.'/messages');
                            $messages = json_decode($msgResponse->getBody()->getContents());
                            $averageTime = get_average_response_time($messages->messages, $usr, $unloadingTime - $one_day);
                            $operator_response_time = strval($averageTime[0]);
                            $message_counter = $averageTime[1];
                        }
                        
                        
                    }
                    
                } else {
                    $operator_response_time = '-';
                    $new_chats_counter = 0;
                    $message_counter = 0;
                }


                $eeexpenses = 0;
                $one_day = 86400;
                $daily_amounts = [];
                $operationsResponse = $client->request('POST', 'https://api.avito.ru/core/v1/accounts/operations_history/', [
                    "json" => [
                        "dateTimeFrom" => date("Y-m-d\TH:i:s", $unloadingTime - $one_day),
                        "dateTimeTo" => date("Y-m-d\TH:i:s", $unloadingTime)
                    ]]);
                    $operations = json_decode($operationsResponse->getBody()->getContents());
                    foreach($operations->result->operations as $amount) {
                    if(property_exists($amount, 'itemId')){
                        if (intval($amount->itemId) == intval ($items->resources[$counter]->id) ) {
                            array_push($daily_amounts, intval ($amount->amountRub));
                        }
                    }
                }
                    foreach($daily_amounts as $amnt) {
                       
                        $eeexpenses += $amnt;
                    }


                $statsStart =date("Y-m-d", $unloadingTime - $one_day);
                $statsFinish = date('Y-m-d', $unloadingTime - $one_day);
                sleep(1);








                
                $response = $client->request('POST', 'https://api.avito.ru/stats/v1/accounts/202741465/items', [
                    "json" => [
                        "dateFrom" => "$statsStart",
                        "dateTo" => "$statsStart",
                        "fields" => [
                            "uniqViews",
                            "uniqContacts",
                            "uniqFavorites"
                        ],
                        "itemIds" => [
                            $items->resources[$counter]->id,
                        ],
                        "periodGrouping" => "day",
                    ]
                    ]);
                $stats = json_decode($response->getBody()->getContents());
                
                $views = 0;
                $contacts = 0;
                $favourites = 0;
                    
                foreach($stats->result->items[0]->stats as $stat){
                    $views = $views + intval($stat->uniqViews); 
                    $contacts = $contacts + intval($stat->uniqContacts);
                    $favourites = $favourites + intval($stat->uniqFavorites);
                }
                
                $accountNameRequest = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/self');
                $accountNameResp = json_decode($accountNameRequest->getBody()->getContents());
                $accountName = $accountNameResp->name;
                






                if ($views != 0 || $contacts != 0 || $favourites != 0 || $eeexpenses != 0 || $new_chats_counter != 0 || $message_counter !=0) {
                    $values = [
                        date('d.m.Y', $unloadingTime - $one_day),
                        $usr,
                        $accountName,
                        $item->id,
                        $item->title,
                        $item->price,
                        $item->category->name,
                        $item->status,
                        $item->url,
                        $idshka->start_time,
                        $idshka->finish_time,
                        $views,
                        $contacts,
                        $favourites,
                        $new_chats_counter,
                        $message_counter,
                        $operator_response_time,
                        $eeexpenses
                    ];
                    if($values){
                    array_push($values_arr,$values);
                    }
                }
                
                $counter = $counter + 1;
            }
           




            $operationsResponse = $client->request('POST', 'https://api.avito.ru/core/v1/accounts/operations_history/', [
                "json" => [
                    "dateTimeFrom" =>  date("Y-m-d\TH:i:s", $unloadingTime - $one_day),
                    "dateTimeTo" =>  date("Y-m-d\TH:i:s", $unloadingTime)
                ]]);
            
            $operations = json_decode($operationsResponse->getBody()->getContents());
            //print_r($operations);
            $lostMoney = 0;
            $tariffArr = [];
            foreach ($operations->result->operations as $oper) {
                if(property_exists($oper, 'serviceType')) {
                    $lostMoney += intval($oper->amountRub);
                if ($oper->serviceType == 'tariff'){
                        array_push($tariffArr, $oper->amountRub, $oper->operationName);
                }
              }
            }



            $availableBalance = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/202741465/balance/');
            $currentBalance = json_decode($availableBalance->getBody()->getContents());
            
            array_push($values_arr[0], intval($currentBalance->real), $lostMoney);
            
            $a = sizeof(($tariffArr));
            for ($i = 0; $i < $a - 1; $i++) {
                array_push($values_arr[$i], $tariffArr[$i], $tariffArr[$i+1]);
            }
            
            append_to_sheet($values_arr, 7);
            }
            
        }  
get_items_information1($access_token, $usr);
?>