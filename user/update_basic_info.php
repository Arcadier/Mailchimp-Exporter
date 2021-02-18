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
//put  variables from ajax here
$firstname =  $content['firstname'];
$lastname =  $content['lastname'];
$email = $content['email'];
$contactnumber = $content['contactnumber'];
$clientSecret =  '';
$status='';

//the the API key 
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);
$single_sync_id = '';
foreach ($marketplaceInfo['CustomFields'] as $cf) {
    if ($cf['Name'] == 'Mailchimp Client Secret' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $clientSecret = $cf['Values'][0];
        error_log('This should be the API KEY ' . $clientSecret);
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

 $MailChimp = new MailChimp($clientSecret);

 $mailchimp_account = $MailChimp->get("/", $clientSecret);
 $account_type =  json_encode($mailchimp_account['pricing_plan_type']);

 $account_type =  str_replace('"', '', $account_type); 
 echo json_encode(['plan results' => $account_type]);
 //2. Set condition if the account type is 'forever-free'
     if ($account_type == 'forever_free') {
 
        echo json_encode(['plan' => $account_type]);
     }
 
else {
    $mailchimp_result = $MailChimp->get("lists", $clientSecret);
    $merchantID = '';
    $consumerID = '';
   
    error_log($mailchimp_result);
   
    foreach($mailchimp_result['lists'] as $list) {
        $name = $list['name'];
       // error_log($name);
        if($name == 'Consumers'){
            $consumerID = $list['id'];
            error_log('This is consumer id' . $consumerID);
        }
        if($name == 'Merchants'){
           $merchantID = $list['id'];
           error_log('this is merchant ID' . $merchantID);
       }
    }
}

$data = [
    'apikey' => $clientSecret, 
    // 'merchantID' => $merchantID,
    // 'consumerID' => $consumerID,
    'singleID' => $single_sync_id,
    'email'     => $email,
    'status'    => 'subscribed',
    'firstname' => $firstname,
    'lastname'  => $lastname,
    'phone'     => $contactnumber
];
if($status == '1' || $status == 1) {
    echo json_encode(['stat' => $status]);
$api_response_code = listSubscribe($data, $single_sync_id);
echo json_encode(['result' => $api_response_code]);
}else {
//$api_response_code =  
listSubscribe($data, $merchantID);
//$api_response_code =  
listSubscribe($data, $consumerID);
//error_log($api_response_code);
}
/**
 * Mailchimp API- List Subscribe added function.In this method we'll look how to add a single member to a list using the lists/subscribe method.Also, We will cover the different parameters for submitting a new member as well as passing in generic merge field information.
 *
 * @param array $data Subscribe information Passed.
 *
 * @return mixed
 */
function listSubscribe(array $data, $id)
{
    $apiKey = $data['apikey'];
    //$listId = $data['singleID'];
    $memberId =  md5(strtolower($data['email']));
    $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url        = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' .  $id . '/members/' . $memberId . '?skip_merge_validation=true';
    $json       = json_encode([
        'email_address' => $data['email'],
        'status'        => $data['status'], // "subscribed","unsubscribed","cleaned","pending" 
        'merge_fields'  => [
            'FNAME' => $data['firstname'],
            'LNAME' => $data['lastname'],
            'PHONE' => $data['phone'],
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
    echo json_encode(['result' => $httpCode]);
    return $httpCode;


}



//for testing only

// function listSubscribe1(array $data)
// {
//     $apiKey = $data['apikey'];
//     $listId = $data['singleID'];

//     $memberId   = md5(strtolower($data['email']));
//     $dataCenter = substr($apiKey, strpos($apiKey, '-') + 1);
//     $url        = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' .   $listId . '/members/' . $memberId;
//     $json       = json_encode([
//         'email_address' => $data['email'],
//         'status'        => $data['status'], // "subscribed","unsubscribed","cleaned","pending" 
//         'merge_fields'  => [
//             'FNAME' => $data['firstname'],
//             'LNAME' => $data['lastname'],
//             'PHONE' => $data['phone'],
//         ]
//     ]);
//     $ch = curl_init($url);
//     curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

//     $result   = curl_exec($ch);
//     echo $result;
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     curl_close($ch);

//     return $httpCode;  
// }

?>