<!DOCTYPE HTML>
<html>

<?php

$backendUrl = 'https://sis08.drivefx.net/5F9732BE';
$appId = '63E00C89F9';
$userCode = 'viren@evolutionitsolution.com';
$password = 'Portugal@2019';
$company = '';
$tokenLifeTime = 'Never';

$ch = curl_init();
//First we need to get an access token to make the requests to API
echo "> Requesting Token <br>";
$accessToken = requestAccessToken($ch);

if($accessToken == null){
    exit(1);
}else{
    echo "Access Token Generated Successfully! <br></br>";
}
curl_close($ch);


$request = array("entity" => 'Cl',
    "ndoc" => 1);

$data_string = json_encode($request);


//create an instance of 'Clientes' and changes values as we like (nome and pais are required)
$url = "https://api.drivefx.net/v3/getNew";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " .$accessToken ));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$out = curl_exec($ch);
curl_close($ch);

$val = json_decode($out);

$val->nome="Julio Graça";
$val->pais="Portugal";
$val->email="juliograca@naomail.pt";
$val->telefone= "999999999";
$val->url="www.juliosemgraca.com";
$val->nome2="Juls";
$val->cnome1="Julio";



//saves the values changed above and provides the information at Drive FX
$request = array("entity" => "Cl",
    "ndoc" => 1, "itemVO" => $val);

$data_string = json_encode($request);

$url = "https://api.drivefx.net/v3/saveInstance";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " .$accessToken ));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$out = curl_exec($ch);
curl_close($ch);

$val = json_decode($out);


//search for entity created with the name Julio Graça and it´s information
$request = array("queryObject" => array( "entityName" => "Cl",
    "filterItems" => [array("filterItem" =>"nome", "comparison" => 0, "valueItem" => "Julio Graça")]));


$data_string = json_encode($request);


$url = "https://api.drivefx.net/v3/searchEntities";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " .$accessToken ));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$out = curl_exec($ch);
curl_close($ch);

$val = json_decode($out);



//updates the values that we provide at the position 0 of the object, and with the operation 1 we can edit it
$val->entities[0]->telefone = "999999888";
$val->entities[0]->tipo="Cliente Final";
$val->entities[0]->morada="Rua das Caldeiras nº3";
$val->entities[0]->Operation = 1;
$request = array("entity" => "Cl",
    "ndoc" => 1, "itemVO" => $val->entities[0]);

$data_string = json_encode($request);

$url = "https://api.drivefx.net/v3/saveInstance";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " .$accessToken ));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$out = curl_exec($ch);
curl_close($ch);

$val = json_decode($out);





function requestAccessToken($ch){
    global $backendUrl, $appId, $userCode, $password, $company, $tokenLifeTime;

    //#1 - Specify endpoint of service
    $url = "http://api.drivefx.net/v3/generateAccessToken";

    //#2 - Specify request Params
    $params = '{
							  "credentials": {
								"backendUrl": "' .$backendUrl. '",
								"appId": "' .$appId. '",
								"userCode": "' .$userCode. '",
								"password": "' .$password. '",
								"company": "' .$company. '",
								"tokenLifeTime": "' .$tokenLifeTime. '"
							  }
							}';


    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: application/json"));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    $response = curl_exec($ch);
    $res = json_decode($response);
    if($res->code == 100){
        echo "Error on getAccessToken <br>";
        return null;
    }

    return $res->token;
}
?>



</html>