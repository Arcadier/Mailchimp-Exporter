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
$clientSecret = '';

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

    if ($cf['Name'] == 'Default Lastname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $default_lastname = $cf['Values'][0];
       
    }

    if ($cf['Name'] == 'Default Firstname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $default_firstname= $cf['Values'][0];
    }

    if ($cf['Name'] == 'Single Sync ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $single_sync_id = $cf['Values'][0];
        error_log('Sync id '. $single_sync_id);
    }

}

//values of List ID's
$MailChimp = new MailChimp($clientSecret);
 $mailchimp_result = $MailChimp->get("lists", $clientSecret);
 $merchantID = '';
 $consumerID = '';
 $finalData = [];
 foreach($mailchimp_result['lists'] as $list) {
     $name = $list['name'];
     if($name == 'Consumers Test'){
         $consumerID = $list['id'];
         error_log('consumer List ID ' . $consumerID);
     }
     if($name == 'Merchants Test'){
        $merchantID = $list['id'];
        error_log('merchant List ID ' . $merchantID);
    }
 }


// Query to get marketplace id
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", null, $url, false);

// Query to get package custom fields
$url = $baseUrl . '/api/developer-packages/custom-fields?packageId=' . getPackageID();
$packageCustomFields = callAPI("GET", null, $url, false);

//INSERT USERS TO THE CONSUMERS LISTS / INSERT USERS TO USERS LISTS 

$MailChimp = new MailChimp($clientSecret);

$data = [
    'clientsecret' => $clientSecret,
    'listId' => $consumerID,
    'singleID' => $single_sync_id,
    'email'     =>  $email,
    'status'    => 'subscribed',
    'firstname' =>  $default_firstname, 
    'lastname'  =>  $default_lastname   
];

//sync new consumer data

syncMailchimp($data);

    if ($MailChimp->success()) {
		
	} else {
		// Display error
        error_log('isFailed' . json_encode($MailChimp->getLastResponse()));
        error_log(json_encode($MailChimp.getLastError()));
    }

function syncMailchimp($data) {
    $apiKey = $data['clientsecret'];
    $listId = $data['singleID'];

    $memberId = md5(strtolower($data['email']));
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

    $json = json_encode([
        'email_address' => $data['email'],
        'status'        => $data['status'], // "subscribed","unsubscribed","cleaned","pending"
        'merge_fields'  => [
            'FNAME'     => $data['firstname'],
            'LNAME'     => $data['lastname']
        ]
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                                                                 

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode;
}

//SHOULD ADD ANOTHER CUSTOM FIELD FOR AUTO SYNC PROPERTIES
 //added function for last sync
$mailchimpLastSyncCustomField = '';

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
$id =  $marketplaceInfo['ID'];
$url = $baseUrl . '/api/v2/marketplaces/';
$result = callAPI("POST", $admin_token['access_token'], $url, $data);
?>