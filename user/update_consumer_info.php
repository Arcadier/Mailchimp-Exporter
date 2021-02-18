<?php
// working code for batch subsscribe
include 'callAPI.php';
include 'admin_token.php';
include 'MailChimp.php';
use \DrewM\MailChimp\MailChimp;
$admin_token = getAdminToken();
$customFieldPrefix = getCustomFieldPrefix();
$baseUrl = getMarketplaceBaseUrl();
$contentBodyJson = file_get_contents('php://input');
$content = json_decode($contentBodyJson, true);
$username =  $content['username'];
$email = $content['email'];
$userId = $content['userId'];
$firstname = $content['firstname'];
$lastname = $content['lastname'];
$phone = $content['phone'];
$address =  $content['address'];
$country =  $content['country'];
$city = $content['city'];
$state =  $content['state'];
$postcode =  $content['postcode'];

$clientSecret = '';
$status = '';
//FOR TIMEZONES - POSTING LAST SYNC DETAILS   02/11/19 
$timezone = $content['timezone'];  // $_GET['timezone_offset_minutes']
// Convert minutes to seconds
$timezone_name = timezone_name_from_abbr("", $timezone*60, false);
date_default_timezone_set($timezone_name);
$timestamp = date("d/m/Y H:i"); 

//the the API key 
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);

$single_sync_id = '';

foreach ($marketplaceInfo['CustomFields'] as $cf) {
    if ($cf['Name'] == 'Mailchimp Client Secret' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $clientSecret = $cf['Values'][0];
        error_log('API KEY ' . $clientSecret);
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

echo json_encode(['id' => $single_sync_id]);
echo json_encode(['key' => $clientSecret]);



//values of List ID's
$MailChimp = new MailChimp($clientSecret);

$mailchimp_account = $MailChimp->get("/", $clientSecret);
$account_type =  json_encode($mailchimp_account['pricing_plan_type']);
$account_type =  str_replace('"', '', $account_type); 
//2. Set condition if the account type is 'forever-free'
    if ($account_type == 'forever_free') {

        echo json_encode(['stat' => $account_type]);
    }


else {
    $mailchimp_result = $MailChimp->get("lists", $clientSecret);
    $merchantID = '';
    $consumerID = '';
    $finalData = [];
     foreach($mailchimp_result['lists'] as $list) {
         $name = $list['name'];
         if($name == 'Consumers'){
             $consumerID = $list['id'];
             error_log('consumer List ID ' . $consumerID);
         }
         if($name == 'Merchants'){
            $merchantID = $list['id'];
            error_log('merchant List ID ' . $merchantID);
        }
     }
}


//INSERT USERS TO THE CONSUMERS LISTS / INSERT USERS TO USERS LISTS 

$data = [
    'clientsecret' => $clientSecret,
    'listId' => $consumerID,
    'ID' => $single_sync_id,
    'email'     =>  $email,
    'status'    => 'subscribed',
    'firstname' =>  $firstname, 
    'lastname'  =>  $lastname,
    'phone' => $phone,
    'address' => $address,
    'country' => $country,
    'city' => $city,
    'state' => $state,
    'zip' => $postcode
];

//sync new consumer data


if($status == '1' || $status == 1) {
    syncMailchimp($data);
}

    // if ($MailChimp->success()) {
		
	// } else {
	// 	// Display error
    //     error_log('isFailed' . json_encode($MailChimp->getLastResponse()));
    //     error_log(json_encode($MailChimp.getLastError()));
    // }

function syncMailchimp(array $data) {
  
    $finalData = [];
    $individulData = array(
        'email_address' => $data['email'], 
        'status'        => 'subscribed',
        'merge_fields'  => array(
            'FNAME' =>  $data['firstname'],
            'LNAME' =>  $data['lastname'],                                                   
            'PHONE' =>  $data['phone'],
            'ADDRESS' =>  array(
            'addr1' => $data['address'], 
            'city' =>  $data['city'], 
            'state' => $data['state'],
            'zip' => $data['zip'], 
            'country' => $data['country']   
    ))
    );
    $json_individulData    = json_encode($individulData);     
    echo json_encode(['data' => $json_individulData]);

$finalData['operations'][] =
array(
    "method" => "POST",
    "path"   => "/lists/" . $data['ID']. "/members/",
    "body"   => $json_individulData
);
echo json_encode(['finaldata' => $finalData]);

$api_response_cons = batchSubscribe($finalData, $data['clientsecret']);
echo json_encode(['result' => $api_response_cons]);
}

//SHOULD ADD ANOTHER CUSTOM FIELD FOR AUTO SYNC PROPERTIES
 //added function for last sync

$mailchimpLastSyncCustomField = '';


// Query to get package custom fields
$url = $baseUrl . '/api/developer-packages/custom-fields?packageId=' . getPackageID();
$packageCustomFields = callAPI("GET", null, $url, false);

foreach ($packageCustomFields as $cf) {

    if ($cf['Name'] == 'Mailchimp Last Sync' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
           $mailchimpLastSyncCustomField = $cf['Code'];
    }
    //added additional custom fields for Last sync date 2/11/19

}
$data = [
    'ID' => $marketplaceInfo['ID'],
    'CustomFields' => [
        [
            'Code' => $mailchimpLastSyncCustomField,
            'Values' => [$timestamp],
        ],

    ],
];
$url = $baseUrl . '/api/v2/marketplaces/';
$result = callAPI("POST", $admin_token['access_token'], $url, $data);

function batchSubscribe(array $data, $apikey)
{
    $auth          = base64_encode('user:' . $apikey);
    $json_postData = json_encode($data,true);
    $ch            = curl_init();
    $dataCenter    = substr($apikey, strpos($apikey, '-') + 1);
    $curlopt_url   = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches/';
    curl_setopt($ch, CURLOPT_URL, $curlopt_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'Authorization: Basic ' . $auth));
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/3.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_postData);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return $result;
}

?>