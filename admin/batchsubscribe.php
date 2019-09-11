<?php
require_once 'syncLogs.php';
use \DrewM\MailChimp\MailChimp;

$contentBodyJson = file_get_contents('php://input');
$content = json_decode($contentBodyJson, true);
$clientSecret = $content['clientSecret'];
error_log( 'clientsecret  '  .json_encode($clientSecret));
$userId = $content['userId'];
error_log( 'userid  '  .json_encode($userId));
//FOR TIMEZONES - POSTING LAST SYNC DETAILS   02/11/19 
$timezone = $content['timezone'];  
// Convert minutes to seconds
$timezone_name = timezone_name_from_abbr("", $timezone*60, false);
date_default_timezone_set($timezone_name);
$timestamp = date("d/m/Y H:i"); 
$batchID_merchant = '';
$batchID_consumer = '';
$defaul_lastname = '';
$default_firstname = '';

$baseUrl = getMarketplaceBaseUrl();
$admin_token = getAdminToken();
$customFieldPrefix = getCustomFieldPrefix();

// Query to get marketplace id
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", null, $url, false);
 
// Query to get package custom fields
$url = $baseUrl . '/api/developer-packages/custom-fields?packageId=' . getPackageID();
$packageCustomFields = callAPI("GET", null, $url, false);

 //REVISION 08/07/19
 //TODO: Add condition for free accounts, so they will be able to sync for a single Audience only.

//1. Get the Single sync status from customfields. if Status == 1; perform single sync functions.
 $single_sync_status = '';   
 $single_sync_id = '';
    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        if ($cf['Name'] == 'Single Sync Status' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $single_sync_status = $cf['Values'][0];
            error_log('Sync Stat '. $single_sync_status);
        }
        if ($cf['Name'] == 'Single Sync ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $single_sync_id = $cf['Values'][0];
            error_log('Sync id '. $single_sync_id);
        }

        if ($cf['Name'] == 'Default Lastname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $default_lastname = $cf['Values'][0];
        
        }

        if ($cf['Name'] == 'Default Firstname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $default_firstname= $cf['Values'][0];
        }
    }

    if ($single_sync_status ==  '1') {
        
        //get address details
        $url = $baseUrl . '/api/v2/users/' . $userId .'/addresses';
        $useraddressinfo = callAPI("GET", $admin_token['access_token'], $url, false);

        //get user details
        $url = $baseUrl . '/api/v2/admins/'. $userId . '/users?role=user&ignoreGuest=true&pageSize=100'; //set the ignore guest to true
        $result = callAPI("GET", $admin_token['access_token'], $url, false);
        error_log('This is log for users api  ' . json_encode($result));

        //insert Merchant / Buyer to the same Audience /List
            $api_key = $clientSecret;
            $MailChimp = new MailChimp($api_key);
            $finalData = [];
            foreach($result['Records'] as $find){
                if (!in_array('Admin',$find['Roles'])){ 
                    $url = $baseUrl . '/api/v2/users/' . $find['ID'] .'/addresses';
                    $useraddressinfo = callAPI("GET", $admin_token['access_token'], $url, false);
                        foreach($useraddressinfo['Records'] as $address) {
                            $user_address = $address['Line1'];
                            //handle the exception here if in the absense of $address array
                        }
                        if (in_array('User',$find['Roles']) && count($find['Roles']) == 1){ 
                            $userRole = 'Consumer';
                        }else {
                            $userRole = 'Merchant';
                        }

                        $individulData = array(
                            'email_address' => $find['Email'], 
                            'status'        => 'subscribed',
                            'merge_fields'  => array(
                                'FNAME' =>  array_key_exists('FirstName', $find) ? $find['FirstName'] : $default_firstname,
                                'LNAME' =>  array_key_exists('LastName', $find) ? $find['LastName'] : $default_lastname,                                                   
                                'PHONE' =>  array_key_exists('PhoneNumber', $find) ? $find['PhoneNumber'] : '00070817',
                                'ADDRESS' =>  array(
                                'addr1' => array_key_exists('Line1', $address) ? $address['Line1'] : 'Address',
                                'city' =>  array_key_exists('City', $address) ? $address['City'] : 'city',
                                'state' => array_key_exists('State', $address) ? $address['State'] : 'state',
                                'zip' => array_key_exists('PostCode', $address) ? $address['PostCode'] : '000',
                                'country' => array_key_exists('CountryCode', $address) ? $address['CountryCode'] : 'US' ),
                                'MMERGE6' => $userRole,
                        )
                        );
                        $json_individulData    = json_encode($individulData);     
                    
                    $finalData['operations'][] =
                    array(
                        "method" => "POST",
                        "path"   => "/lists/$single_sync_id/members/",
                        "body"   => $json_individulData
                    );
            
                } else { 
                }
                }
                error_log('this is the final data ====== ' . print_r($finalData,true));
                $api_response_cons = batchSubscribe($finalData, $api_key);
                error_log('This is for consumers only' . $api_response_cons);    
                //test response
                $api_response_cons = json_decode($api_response_cons,true);
                $batchID_single = $api_response_cons['id'];
                error_log('batch id single ' . $batchID_single);

                $mailchimpLastSyncCustomField = '';
                $batch_last_run = '';
                $sync_last_run = getLastRun();
                error_log('last run batchsubs ' . $sync_last_run);
                $save_last_run = $sync_last_run + 1; //increment the last sync run everytime the user sync 
                error_log('Last Run ' . $sync_last_run);
    
                foreach ($packageCustomFields as $cf) {
    
                    if ($cf['Name'] == 'Mailchimp Last Sync' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                        $mailchimpLastSyncCustomField = $cf['Code'];
                    }
                    //added additional custom fields for Last sync date 2/11/19
    
                    //2/27/18 - added additional fields for consumer batch ID and merchant batch id
                    // if ($cf['Name'] == 'Batch ID Merchant' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    //     $batch_id_merchant = $cf['Code'];
                    // }
    
                    if ($cf['Name'] == 'Single Batch ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                        $single_id_cons = $cf['Code'];
                    }
    
                    if ($cf['Name'] == 'Sync Last Run' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                        $batch_last_run = $cf['Code'];
                    }
                
                }
                // if ($batchID_consumer == '') {
                //     $batchID_consumer = 0;
                // }
                $data = [
                    'ID' => $marketplaceInfo['ID'],
                    'CustomFields' => [
                        [
                            'Code' => $mailchimpLastSyncCustomField,
                            'Values' => [$timestamp],
                        ],
    
                        [
                            'Code' => $batch_last_run,
                            'Values' => [$save_last_run],
                        ],
                    
                        [
                            'Code' => $single_id_cons,
                            'Values' => [$batchID_single],
                        ],
                    
                    ],
                ];
    
                $id =  $marketplaceInfo['ID'];
                $url = $baseUrl . '/api/v2/marketplaces/';
                $result = callAPI("POST", $admin_token['access_token'], $url, $data);

                sleep(6);//delay 3 seconds 

                processUserData($clientSecret, $batchID_single, 'free_account', 'responses');
                // processUserData($clientSecret, $batchID_consumer, 'consumer', 'responses'); 
                saveAllBatches(); //save all batch details to local json file  ../downloads/batches/allbatches.json
                convertSyncDate($timezone_name);
                setRoleToBatch();
    
    }



    else {
        
            //values of List ID's get the consumer and merchant list ID's
            $MailChimp = new MailChimp($clientSecret);
            $mailchimp_result = $MailChimp->get("lists", $clientSecret);
            $merchantID = '';
            $consumerID = '';
            foreach($mailchimp_result['lists'] as $list) {
                $name = $list['name'];
                if($name == 'Consumers Test'){
                    $consumerID = $list['id'];
                    error_log('This is consumer List ID ' . $consumerID);
                }
                if($name == 'Merchants Test'){
                    $merchantID = $list['id'];
                    error_log('This is merchant List ID ' . $merchantID);
                }
            }


            //for user addresses
            $url = $baseUrl . '/api/v2/users/' . $userId .'/addresses';
            $useraddressinfo = callAPI("GET", $admin_token['access_token'], $url, false);

            error_log(json_encode($useraddressinfo));

            // FOR CREATING NEW LISTS TO MAILCHIMP FOR ARCADIER PURPOSE ONLY---------------------FOR CREATING NEW LISTS TO MAILCHIMP ------------------------FOR CREATING NEW LISTS TO MAILCHIMP------------------------------------------------------------------------------------
            $url = $baseUrl . '/api/v2/admins/'. $userId . '/users?role=user&ignoreGuest=true&pageSize=100'; //set the ignore guest to true
            error_log('this is the url ' . $url);
            $result = callAPI("GET", $admin_token['access_token'], $url, false);
            error_log('This is log for users api  ' . json_encode($result));

            foreach ($marketplaceInfo['CustomFields'] as $cf) {
                if ($cf['Name'] == 'Default Lastname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $default_lastname = $cf['Values'][0];
                
                }

                if ($cf['Name'] == 'Default Firstname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $default_firstname= $cf['Values'][0];
                }

            }
            //INSERT USERS TO THE CONSUMERS LISTS ================================INSERT USERS TO USERS LISTS =============================================================== -- WORKING
            $api_key = $clientSecret;
            $MailChimp = new MailChimp($api_key);
            $finalData = [];
            foreach($result['Records'] as $find){
                if (in_array('User',$find['Roles']) && count($find['Roles']) == 1){ 
                    $url = $baseUrl . '/api/v2/users/' . $find['ID'] .'/addresses';
                    $useraddressinfo = callAPI("GET", $admin_token['access_token'], $url, false);
                        foreach($useraddressinfo['Records'] as $address) {
                            $user_address = $address['Line1'];
                            //handle the exception here if in the absens of $address array
                        }
                
                        $individulData = array(
                            'email_address' => $find['Email'], 
                            'status'        => 'subscribed',
                            'merge_fields'  => array(
                                'FNAME' =>  array_key_exists('FirstName', $find) ? $find['FirstName'] : $default_firstname,
                                'LNAME' =>  array_key_exists('LastName', $find) ? $find['LastName'] : $default_lastname,                                                   
                                'PHONE' =>  array_key_exists('PhoneNumber', $find) ? $find['PhoneNumber'] : '00070817',
                                'ADDRESS' =>  array(
                                'addr1' => array_key_exists('Line1', $address) ? $address['Line1'] : 'Address',
                                'city' =>  array_key_exists('City', $address) ? $address['City'] : 'city',
                                'state' => array_key_exists('State', $address) ? $address['State'] : 'state',
                                'zip' => array_key_exists('PostCode', $address) ? $address['PostCode'] : '000',
                                'country' => array_key_exists('CountryCode', $address) ? $address['CountryCode'] : 'US' ),
                        )
                        );
                        $json_individulData    = json_encode($individulData);     
                    
                    $finalData['operations'][] =
                    array(
                        "method" => "POST",
                        "path"   => "/lists/$single_sync_id /members/",
                        "body"   => $json_individulData
                    );
            
                } else { 
                }
                }
                error_log('this is the final data ====== ' . print_r($finalData,true));
                $api_response_cons = batchSubscribe($finalData, $api_key);
                error_log('This is for consumers only' . $api_response_cons);    
                //test response
                $api_response_cons = json_decode($api_response_cons,true);
                $batchID_consumer = $api_response_cons['id'];
                

                //*********** */sync all merchant users***********************
                $url = $baseUrl . '/api/v2/admins/'. $userId . '/users?role=merchant&pageSize=100';
                $result = callAPI("GET", $admin_token['access_token'], $url, false);
                error_log('This is log for merchant api  ' . json_encode($result));
                $finalData = [];
                foreach($result['Records'] as $find){
                    $url = $baseUrl . '/api/v2/users/' . $find['ID'] .'/addresses';
                    $useraddressinfo = callAPI("GET", $admin_token['access_token'], $url, false);
                
                        foreach($useraddressinfo['Records'] as $address) {
                            $user_address = $address['Line1'];
                        }

                        $individulData = array(
                            'email_address' => $find['Email'],
                            'status'        => 'subscribed',
                            'merge_fields'  => array(
                                'FNAME' =>  array_key_exists('FirstName', $find) ? $find['FirstName'] : $default_firstname,
                                'LNAME' =>  array_key_exists('LastName', $find) ? $find['LastName'] : $default_lastname,                                                   
                                'PHONE' =>  array_key_exists('PhoneNumber', $find) ? $find['PhoneNumber'] : '00070817',
                                'ADDRESS' =>  array(  
                                'addr1' => array_key_exists('Line1', $address) ? $address['Line1'] : 'Address',
                                'city' =>  array_key_exists('City', $address) ? $address['City'] : 'city',
                                'state' => array_key_exists('State', $address) ? $address['State'] : 'state',
                                'zip' => array_key_exists('PostCode', $address) ? $address['PostCode'] : '000',
                                'country' => array_key_exists('CountryCode', $address) ? $address['CountryCode'] : 'US' ),
                            )
                        );
                        $json_individulData  = json_encode($individulData);
                        
                        $finalData['operations'][] =
                            array(
                                "method" => "POST",
                                "path"   => "/lists/$single_sync_id/members/",
                                "body"   => $json_individulData
                            );
                                
                    }
                    $api_response_merch = batchSubscribe($finalData, $api_key);
                    error_log('This is  for merchants only' . $api_response_merch); 
                    $api_response_merch = json_decode($api_response_merch,true);
                    $batchID_merchant = $api_response_merch['id'];
                
                    //getting the batches after it is posted

            //added function for last sync
            $mailchimpLastSyncCustomField = '';
            $batch_id_cons = '';
            $batch_id_merchant = '';
            $batch_last_run = '';
            $sync_last_run = getLastRun();
            error_log('last run batchsubs ' . $sync_last_run);
            $save_last_run = $sync_last_run + 1; //increment the last sync run everytime the user sync 
            error_log('Last Run ' . $sync_last_run);

            foreach ($packageCustomFields as $cf) {

                if ($cf['Name'] == 'Mailchimp Last Sync' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $mailchimpLastSyncCustomField = $cf['Code'];
                }
                //added additional custom fields for Last sync date 2/11/19

                //2/27/18 - added additional fields for consumer batch ID and merchant batch id
                if ($cf['Name'] == 'Batch ID Merchant' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $batch_id_merchant = $cf['Code'];
                }

                if ($cf['Name'] == 'Batch ID Consumer' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $batch_id_cons = $cf['Code'];
                }

                if ($cf['Name'] == 'Sync Last Run' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $batch_last_run = $cf['Code'];
                }
            
            }
            if ($batchID_consumer == '') {
                $batchID_consumer = 0;
            }
            $data = [
                'ID' => $marketplaceInfo['ID'],
                'CustomFields' => [
                    [
                        'Code' => $mailchimpLastSyncCustomField,
                        'Values' => [$timestamp],
                    ],

                    [
                        'Code' => $batch_id_cons,
                        'Values' => [$batchID_consumer],
                    ],
                    [
                        'Code' => $batch_id_merchant,
                        'Values' => [$batchID_merchant],
                    ],
                    [
                        'Code' => $batch_last_run,
                        'Values' => [$save_last_run],
                    ],
                

                ],
            ];

            $id =  $marketplaceInfo['ID'];
            $url = $baseUrl . '/api/v2/marketplaces/';
            $result = callAPI("POST", $admin_token['access_token'], $url, $data);

            sleep(3);//delay 3 seconds 


            if($batchID_consumer != '') {
                processUserData($clientSecret, $batchID_merchant, 'merchant', 'responses');
                processUserData($clientSecret, $batchID_consumer, 'consumer', 'responses'); 
                saveAllBatches(); //save all batch details to local json file  ../downloads/batches/allbatches.json
                convertSyncDate($timezone_name);
                setRoleToBatch();
            }else {
                processUserData($clientSecret, $batchID_merchant, 'merchant', 'responses');
                // processUserData($clientSecret, $batchID_consumer, 'consumer', 'responses'); 
                saveAllBatches(); //save all batch details to local json file  ../downloads/batches/allbatches.json
                convertSyncDate($timezone_name);
                setRoleToBatch();
            }
    }

/**
 * Mailchimp API- List Batch Subscribe added function
 *
 */
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
