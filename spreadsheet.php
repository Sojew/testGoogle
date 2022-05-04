<?php

use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
require 'vendor/autoload.php';

function append_to_sheet($values,$sheet=null) {
    $vars = file_get_contents('vars.json');
    $vars = json_decode($vars);
    $credentials_path_relative = $vars->google_vars->credentials_path_relative;
    $spreadsheetId = $vars->google_vars->spreadsheetId;
    $google_client = new Google\Client();
    $google_client->setScopes([Sheets::SPREADSHEETS]);
    $google_client->useApplicationDefaultCredentials();
    $google_client -> setAuthConfig(__DIR__.$credentials_path_relative);
    $service = new Sheets($google_client);
    $response = $service->spreadsheets->get($spreadsheetId);

    $sheet_title = $response->getSheets()[0]->getProperties()->title; //было 0

    $range = $sheet_title;

    if ($sheet != null) {
        if (gettype($sheet) == "integer") {
            $sheet_title = $response->getSheets()[$sheet]->getProperties()->title;
            $range = $sheet_title;
        }
        else if (gettype($sheet) == "string") {
            $range = $sheet;
        }
    }

    $body =  new Google\Service\Sheets\ValueRange([
        'values'=>$values
    ]);

    $params = [
        'valueInputOption' => 'RAW'
    ];
    $insert = [
        "insertDataOption" => "INSERT_ROWS"
    ];

    $result = $service->spreadsheets_values->append (
        $spreadsheetId,
        $range,
        $body,
        $params,
        $insert
    );

}


// append_to_sheet([[1,2,3,43,55]])


?>
