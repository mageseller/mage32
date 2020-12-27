<?php

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://interface.phcsoftware.com/v3/generateAccessToken',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => '{
    "credentials": {
    "backendUrl": "https://sis08.drivefx.net/5F9732BE",
    "appId": "63E00C89F9",
    "userCode": "viren@evolutionitsolution.com",
    "password": "Portugal@2019",
    "company": "",
    "tokenLifeTime": "Never"
    }

}',

));

$response = curl_exec($curl);
if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
}
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if (isset($error_msg)) {
    echo "sdfsd";
    print_r($error_msg) ;
    // TODO - Handle cURL error accordingly
}else{
    echo "sdfsd1";
    echo $response;
}

