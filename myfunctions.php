<?php
use GuzzleHttp\Client;
require 'vendor/autoload.php';
require 'spreadsheet.php';


function refresh_access_token() {

    $client = new GuzzleHttp\Client();
    $response = $client->request(
        'POST',
        'https://api.avito.ru/token/',
        [
            'form_params' => [
                'client_id' => 'fLtNDoA20luptPSMbqa3',
                'client_secret' => 'ZK7khE05TupJaJadGzSPHgpAQYLLRDbyPkd0TTzl',
                'grant_type' => 'client_credentials'
            ]
        ]
    );
    $body = $response->getBody()->getContents();
    $access_token = json_decode($body)->access_token;                // Имя переменной которой обновим старое значение
    $file = file_get_contents('vars.json');     // Открыть файл data.json
    $file = json_decode($file,true);            // Декодировать в массив
    $file["access_token"]  = $access_token;  // Присвоить новое значение
    file_put_contents('vars.json',json_encode($file)); // Перекодировать в формат и записать в файл.
    return $access_token;
}

function get_items_information($access_token) {
    $client = new Client();

    $response = $client->request('GET','https://api.avito.ru/core/v1/items',[
        "headers"=> ["Authorization" => "Bearer ".$access_token]
        ]
    );
    $items = json_decode($response->getBody()->getContents());
    echo(sizeof($items->resources).'hehe');
    print_r($items->resources[0]->id);
    $values_arr = [];
    $counter = 0;
    foreach($items->resources as $i => $item) {
        echo $i." item processing...".PHP_EOL;
        // $values = [
        //     $item->id,
        //     $item->title,
        //     $item->price,
        //     $item->category->name,
        //     $item->status,
        //     $item->url,
        // ];
        $response = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/202741465/items/'.$items->resources[$counter]->id, [
            "headers"=> ["Authorization" => "Bearer ".$access_token]
            ]
        );
        $idshka = json_decode($response->getBody()->getContents());
        
        $statsStart = new DateTime($idshka->start_time);
        $statsStart = $statsStart->format('Y-m-d');
        $statsFinish = date('Y-m-d');
       
        
        sleep(6);
        $response = $client->request('POST', 'https://api.avito.ru/stats/v1/accounts/202741465/items', [
            "headers"=> [
                "Authorization" => "Bearer ".$access_token,
            ],
            "json" => [
                "dateFrom" => "$statsStart",
                "dateTo" => "$statsFinish",
                "fields" => [
                    "uniqViews",
                    "uniqContacts",
                    "uniqFavorites"
                ],
                "itemIds" => [
                    $items->resources[$counter]->id,
                ],
                "periodGrouping" => "week",
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
        
        $values = [
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
            $favourites
            //$stats->result->items[0]->stats[0]->uniqViews,
            //$stats->result->items[0]->stats[0]->uniqContacts,
            //$stats->result->items[0]->stats[0]->uniqFavorites,
            //$idshka->vas,
        ];
        array_push($values_arr,$values);
        $counter = $counter + 1;
    }
    // $id_arr = [];

    // for ($j = 0; $j <= sizeof($items->resources) - 1; $j++) {
    //     $response = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/202741465/items/'.$items->resources[$j]->id, [
    //         "headers"=> ["Authorization" => "Bearer ".$access_token]
    //         ]
    //     );
    //     $idshka = json_decode($response->getBody()->getContents());


        
    // }
    //print_r($values_arr);

    //foreach($items->resources as $i => $item)
    //$user_id = 202741465;
    // $response = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/202741465/items/2391634868',[
    //     "headers"=> ["Authorization" => "Bearer ".$access_token]
    //     ]
    // );
    
    // $items = json_decode($response->getBody()->getContents());
    // //print_r($items);
    // $response = $client->request('GET', 'https://api.avito.ru/core/v1/accounts/202741465/price/vas',[
    //     "headers"=> ["Authorization" => "Bearer ".$access_token]
    //     ]
    // );
    
    //$items = json_decode($response->getBody()->getContents());
    //print_r($items);
    // foreach($items->resources as $i => $item){
    //     echo $i."item processing...".PHP_EOL;
    //     $values = [
    //         $item->vas,
    //     ];
    //     array_push($values_arr, $values);
    // }

    
    append_to_sheet($values_arr, 3);
}


//Можно сделать не через проверку id того, кто посылает, а через direction "in" и "out"
function get_average_response_time($itemMessages, $currentAccountID, $timeFrom) {
    $i = sizeof($itemMessages)  - 1;
    $j = sizeof($itemMessages) - 1;
    $msgTime = [];
    $buyerAccountID = intval ($itemMessages[sizeof($itemMessages) - 1]->author_id);    // найти другой путь решения!
    $messagesAmount = 0;
    
    while ($i>0) {

        while (((intval ( $itemMessages[$i]->author_id)) != $currentAccountID) && $i > 0){
            $i--;
            if ($itemMessages[$i]->created >= $timeFrom) {
                $messagesAmount++;
            }
        }
        $operator_response_time = intval($itemMessages[$i]->created) - intval($itemMessages[$j]->created);
        array_push($msgTime, $operator_response_time);
        
        $j = $i;
        while (((intval ($itemMessages[$j]->author_id)) != $buyerAccountID) && $j > 0) {
            $j--;
        }
        $i = $j;
    }
    $average_response_time = 0;
    foreach($msgTime as $time) {
        $average_response_time += $time;
    }
    //print_r($msgTime);
    $averageTime = gmdate("H:i:s",intdiv($average_response_time, sizeof($msgTime)));

    return [$averageTime, $messagesAmount];  //Добавить message counter

    // for ($i; $i>0; $i--) {
    //     while (intval ( $itemMessages[$i]->author_id) != $currentAccountID) {
    //         continue;
    //     }
    //     $operator_response_time = intval($itemMessages[$i]->created) - intval($itemMessages[$j]->created);
    //     array_push($msgTime, $operator_response_time);
    //     $j = $i;
    //     while ((intval ($itemMessages[$i]->author_id)) != $buyerAccountID) {
    //         $j--;
    //     }
    //     $i = $j;
    // }
    // $average_response_time = 0;
    // foreach($msgTime as $time) {
    //     $average_response_time += $time;
    // }
    // print_r($msgTime);
    // return gmdate("H:i:s",intdiv($average_response_time, sizeof($msgTime)));
    
}


// function get_item_operation($currentAccountID) {

// }

// function get_finish_time($startTime, $currentTime){

// }
?>