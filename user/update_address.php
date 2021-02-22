<?php

include 'callAPI.php';
include 'admin_token.php';   
include 'Mailchimp.php';
use \DrewM\MailChimp\MailChimp;

$admin_token = getAdminToken();
$customFieldPrefix = getCustomFieldPrefix();
$baseUrl = getMarketplaceBaseUrl();
//get and parse the data from AJAx call
$contentBodyJson = file_get_contents('php://input');
$content = json_decode($contentBodyJson, true);
$email = $content['email'];
//address 
$main_address = $content['address'];
$country = $content['country'];
$city= $content['city'];
$state  =  $content['state'];
$zipcode = $content['zip'];
//the the API key 
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);
$single_sync_id = '';

foreach ($marketplaceInfo['CustomFields'] as $cf) {
    if ($cf['Name'] == 'Mailchimp Client Secret' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $clientsecret = $cf['Values'][0];
        error_log('Mailchimp API KEY ' . $clientsecret);
    }

    if ($cf['Name'] == 'Single Sync ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $single_sync_id = $cf['Values'][0];
        error_log('Sync id '. $single_sync_id);
    }
    if ($cf['Name'] == 'Single Sync Status' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $status = $cf['Values'][0];
        error_log('Stat '. $single_sync_id);
    }
}

 $MailChimp = new MailChimp($clientsecret);
 $mailchimp_result = $MailChimp->get("lists", $clientsecret);
 $merchantID = '';
 $consumerID = '';
 
 $mailchimp_account = $MailChimp->get("/", $clientSecret);
 $account_type =  json_encode($mailchimp_account['pricing_plan_type']);

 $account_type =  str_replace('"', '', $account_type); 
 
 //2. Set condition if the account type is 'forever-free'
     if ($account_type == 'forever_free') {
     }else {
        foreach($mailchimp_result['lists'] as $list) {
            $name = $list['name'];
            if($name == 'Consumers'){
                $consumerID = $list['id'];
               
            }
            if($name == 'Merchants'){
               $merchantID = $list['id'];
               
            }
        }
     }

$data = [
    'apikey' => $clientsecret,
    'merchantID' => $merchantID,
    'consumerid' => $consumerID,
    'singleID' => $single_sync_id,
    'email'     => $email,

    'main_address' =>$main_address,
    'country' => $country,
    'city' => $city,
    'state' => $state,
    'zip' => $zipcode
];

if($status == 1) { //for free accounts
$api_response_code = listSubscribe($data, $single_sync_id);
}else {
//for  essential accounts
//$api_response_code = 
listSubscribe($data,$merchantID);
//$api_response_code = 
listSubscribe($data,$consumerID);
//error_log($api_response_code);
}
/**
 * Mailchimp API- List Subscribe added function.In this method we'll look how to add a single member to a list using the lists/subscribe method.Also, 
 * We will cover the different parameters for submitting a new member as well as passing in generic merge field information.
 *
 * @param array $data Subscribe information Passed.
 *
 * @return mixed
 */
function listSubscribe(array $data, $id)
{
    $apiKey = $data['apikey'];
    //$listId = $data['singleID'];

    $memberId   = md5(strtolower($data['email']));
    $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url        = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $id . '/members/' . $memberId;
    $json       = json_encode([
        'email_address' => $data['email'],
        'merge_fields'  => [
            'ADDRESS' =>  array(
                'addr1' => $data['main_address'],
                'city' =>  $data['city'],
                'state' => $data['state'],
                'zip' =>   $data['zip'],
                'country' => $data['country']),
        ]
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

    $result   = curl_exec($ch);
    echo $result;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode;
}

?>
