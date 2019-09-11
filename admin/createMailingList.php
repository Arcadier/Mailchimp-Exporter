<?php
include 'callAPI.php';
include 'admin_token.php';
include 'MailChimp.php';
use \DrewM\MailChimp\MailChimp;

$contentBodyJson = file_get_contents('php://input');
$content = json_decode($contentBodyJson, true);
$cons_id_exists = false;
$merch_id_exists = false;

//get the API Key
$clientSecret = $content['clientSecret'];
error_log( 'API Key from AJAX   '  .json_encode($clientSecret));
$userId = $content['userId'];
error_log( 'userid  '  .json_encode($userId));
$packagePath = $content['packagePath'];

$baseUrl = getMarketplaceBaseUrl();
$admin_token = getAdminToken();
$customFieldPrefix = getCustomFieldPrefix();

// Query to get marketplace id
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", null, $url, false);

// Query to get package custom fields
$url = $baseUrl . '/api/developer-packages/custom-fields?packageId=' . getPackageID();
$packageCustomFields = callAPI("GET", null, $url, false);

$api_key = $clientSecret;
    
$MailChimp = new MailChimp($clientSecret);

//validate if the lists already exist
// 1.Get all the list then array exists of list name

//08-06-2019 1 Audience only revision for free accounts
//This only applies for free accounts

//1.Get the account details and determine if this is a free account

$mailchimp_account = $MailChimp->get("/", $clientSecret);
$account_type =  json_encode($mailchimp_account['pricing_plan_type']);
$account_type =  str_replace('"', '', $account_type); 
//2. Set condition if the account type is 'forever-free'
    if ($account_type == 'forever_free') {
    //3. Set new audiece function here
    //get the current ID of the existing list/audiece on Mailchimp account, since this is free, it should only be one.
        $mailchimp_list = $MailChimp->get("lists", $clientSecret);
            foreach($mailchimp_list['lists'] as $lists) {
                    $name = $lists['name'];
                    $audience_id = $lists['id'];
                    error_log('audience id ' . $audience_id);
                
            }
            //4. Save the current audience ID to customfields -  Single Sync ID;  set the Single Sync Status to 1;
            //Save the list ID's to customfields 
            $single_sync_id='';
            $single_sync_status ='';
            foreach ($packageCustomFields as $cf) {
 
                if ($cf['Name'] == 'Single Sync ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $single_sync_id = $cf['Code'];
                }
                if ($cf['Name'] == 'Single Sync Status' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
                    $single_sync_status = $cf['Code'];     
                }
                
            }
            $data = [
                'ID' => $marketplaceInfo['ID'],
                'CustomFields' => [
                    [
                        'Code' => $single_sync_id,
                        'Values' => [$audience_id ],
                    ],
                    
                    [
                        'Code' =>  $single_sync_status,
                        'Values' => ['1'],
                    ],
            
                ],
            ];
            $id =  $marketplaceInfo['ID'];
            $url = $baseUrl . '/api/v2/marketplaces/';
            $result = callAPI("POST", $admin_token['access_token'], $url, $data);

            error_log(json_encode($result));

            //5. Create new merge fields for the audience to take care the User Role. :)
            //Validate if 'User Role' merge fields exists ?? add the merge field
            //Resource Link https://developer.mailchimp.com/documentation/mailchimp/reference/lists/merge-fields/#%20
       
        //get current fields 
        $merge_fields = $MailChimp->get("lists/$audience_id/merge-fields");
        error_log('merge list ' . json_encode($merge_fields));
        $user_role = '';
        foreach($merge_fields['merge_fields'] as $lists) {
                $name = $lists['name'];
                if($name == 'User Role'){
                    $user_role = true;
                 error_log('Merge field already exist.');
                 break;
                } 
            }
        if ($user_role != true) {
        //add mew merge fields
            $result = $MailChimp->post("lists/$audience_id/merge-fields", [
                'name' => 'User Role' ,
                'type'        => 'text',
                'default_value'  => 'User',
            ]);

            if ($MailChimp->success()) {
            // Success message
            error_log(json_encode($result));
            } else {
            // Display error
            error_log($MailChimp->getLastError());
            }
         }
       
    }

else { //if the account is paid /premium, run the normal function

    $mailchimp_list = $MailChimp->get("lists", $clientSecret);
        foreach($mailchimp_list['lists'] as $lists) {
                $name = $lists['name'];
                if($name == 'Consumers Test'){
                    $cons_id_exists = true;
                }
            if($name == 'Merchants Test') {
                    $merch_id_exists = true;
                }
                    
            }

    if($cons_id_exists == true) {
            error_log('Mailing List already exists for API KEY ' . $clientSecret);

        }
    else { 
            //CREATE LIST FOR CONSUMERS -- =============================================================================================================================
              // $MailChimp = new MailChimp($api_key);
               $mailchimp_new_list_data = array(
                   "name"             => "Consumers Test",
                   "contact"          => array(
                   "company" => "Arcadier",
                   "address1" => "aa",
                   "address2" => "aa",
                   "city" => "aa",
                   "state" =>"aa",
                   "zip"=>"111",
                   "country" =>"Philippines",
                   "phone"=>"33332",  
               ),
               
               "permission_reminder" => "You'\''re receiving this email because you signed up for updates about Arcadier Marketplaces.",
               "campaign_defaults" => array(
                   "from_name" => "Arcadier",
                   "from_email" =>"defaultemail@arcadier.com",
                   "subject" =>"hello",
                   "language"=> "en",
               ),
               "email_type_option"=> true,
           );
           $mailchimp_result = $MailChimp->post("lists", $mailchimp_new_list_data);
           error_log('Mailchimp result for Consumers List '. json_encode($mailchimp_result));
      
               if ($MailChimp->success()) {
                   // Success message
               } else {
                   // Display error
                   error_log(json_encode($MailChimp->getLastResponse()));
   
               }
               $consumers_list_id = $mailchimp_result['id'];
               error_log('This is the consumers LIST ID ' . $consumers_list_id);
   
       }
 

    if($merch_id_exists == true){
    //if (array_key_exists('Merchants Test', $list)){
        error_log('Merchants Lists already exists');
       }
   else {
    
	$mailchimp_new_list_data = array(
			"name"             => "Merchants Test",
			"contact"          => array(
			"company" => "Arcadier",
			"address1" => "aa",
			"address2" => "aa",
			"city" => "aa",
			"state" =>"aa",
			"zip"=>"111",
			"country" => "Philippines",
			"phone"=>"33332",  
		),
		
		"permission_reminder" => "You'\''re receiving this email because you signed up for updates about Arcadier Marketplaces.",
		"campaign_defaults" => array(
			"from_name" => "Arcadier",
			"from_email" =>"defaultemail@arcadier.com",
			"subject" =>"hello",
			"language"=> "en",
		),
		"email_type_option"=> true,
	);
    $mailchimp_result = $MailChimp->post("lists", $mailchimp_new_list_data);
    error_log('Mailchimp result for Merchants List'. json_encode($mailchimp_result));
    
    if ($MailChimp->success()) {
       // Success message 
   } else {
       // Display error
       error_log(json_encode($MailChimp->getLastResponse()));
   }
    $merchants_list_id = $mailchimp_result['id'];
    error_log('This is the merchants LIST ID ' . $merchants_list_id);

}
 //Save the list ID's to customfields 
 $merchant_listID = '';
 $consumer_listID = '';
 
 foreach ($packageCustomFields as $cf) {
 
     if ($cf['Name'] == 'Merchant ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $merchant_listID = $cf['Code'];
     }
     if ($cf['Name'] == 'Consumer ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $consumer_listID = $cf['Code'];     
     }
     
 }
 $data = [
     'ID' => $marketplaceInfo['ID'],
     'CustomFields' => [
         [
             'Code' => $merchant_listID,
             'Values' => [$merchants_list_id],
         ],
         
         [
             'Code' => $consumer_listID,
             'Values' => [$consumers_list_id],
         ],
 
     ],
 ];
 $id =  $marketplaceInfo['ID'];
 $url = $baseUrl . '/api/v2/marketplaces/';
 $result = callAPI("POST", $admin_token['access_token'], $url, $data);

 error_log(json_encode($result));

}


// temporary placing this function here
function batchSubscribe(array $data, $apikey)
{
    $auth          = base64_encode('user:' . $apikey);
    $json_postData = json_encode($data);
    $ch            = curl_init();
    $dataCenter    = substr($apikey, strpos($apikey, '-') + 1);
    $curlopt_url   = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/';
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
