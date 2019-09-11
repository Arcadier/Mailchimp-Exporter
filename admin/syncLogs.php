<?php 
include 'callAPI.php';
include 'admin_token.php';
include 'MailChimp.php';
use \DrewM\MailChimp\MailChimp;

$clientsecret = getSecretKey();
$contentBodyJson = file_get_contents('php://input');
$content = json_decode($contentBodyJson, true);
$timezone = $content['timezone'];
error_log('Timezone ' .$timezone);
$timezone_name = timezone_name_from_abbr("", $timezone*60, false); 
date_default_timezone_set($timezone_name);
error_log('Default timezone set to ' . $timezone_name);
$MailChimp = new MailChimp($clientsecret);

function getSecretKey(){
    $admin_token = getAdminToken();
    $customFieldPrefix = getCustomFieldPrefix();
    $baseUrl = getMarketplaceBaseUrl(); 
    $url = $baseUrl . '/api/v2/marketplaces/';
    $marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);
    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        if ($cf['Name'] == 'Mailchimp Client Secret' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $clientsecret = $cf['Values'][0];
            error_log('Mailchimp API KEY ' . $clientsecret);
        }
        if ($clientsecret == ''){
            $clientsecret =  '';
        }
    }
    return $clientsecret;
}

//get single sync status

function getSyncStatus(){
    $admin_token = getAdminToken();
    $customFieldPrefix = getCustomFieldPrefix();
    $baseUrl = getMarketplaceBaseUrl(); 
    $url = $baseUrl . '/api/v2/marketplaces/';
    $marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);
    
    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        if ($cf['Name'] == 'Single Sync Status' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $single_sync_status = $cf['Values'][0];
            error_log('Sync Stat '. $single_sync_status);
        }
    
    }
    return $single_sync_status;
}

function getLastRun(){
    $admin_token = getAdminToken();
    $customFieldPrefix = getCustomFieldPrefix();
    $baseUrl = getMarketplaceBaseUrl(); 
    $url = $baseUrl . '/api/v2/marketplaces/';
    $marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);
    
    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        //  $lastrun = '';
        if ($cf['Name'] == 'Sync Last Run' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $lastrun = $cf['Values'][0];
            error_log('Last Run Counter' . $lastrun);
        }
        if ($lastrun == null){
            $lastrun =  0;
       }
       
    }
    return $lastrun;
}
                                                           
//for premium account only
function getBatchID_Consumer(){
    $admin_token = getAdminToken();
    $customFieldPrefix = getCustomFieldPrefix();
    $baseUrl = getMarketplaceBaseUrl(); 
    $url = $baseUrl . '/api/v2/marketplaces/';
    $marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);

    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        
        if ($cf['Name'] == 'Batch ID Consumer' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $batch_ID_consumer = $cf['Values'][0];
            error_log('consumer batch ID ' . $batch_ID_consumer);
        }
    }
    return $batch_ID_consumer;
}
//for premium account only
function getBatchID_Merchant(){
    $admin_token = getAdminToken();
    $customFieldPrefix = getCustomFieldPrefix();
    $baseUrl = getMarketplaceBaseUrl(); 
    $url = $baseUrl . '/api/v2/marketplaces/';
    $marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);

    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        
        if ($cf['Name'] == 'Batch ID Merchant' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $batch_ID_merchant = $cf['Values'][0];
            error_log('merchant batch ID ' . $batch_ID_merchant);
        }
    }
    return $batch_ID_merchant;
}

//For free acccount
function getBatchID_single_sync(){
    $admin_token = getAdminToken();
    $customFieldPrefix = getCustomFieldPrefix();
    $baseUrl = getMarketplaceBaseUrl(); 
    $url = $baseUrl . '/api/v2/marketplaces/';
    $marketplaceInfo = callAPI("GET", $admin_token['access_token'], $url, false);

    foreach ($marketplaceInfo['CustomFields'] as $cf) {
        
        if ($cf['Name'] == 'Single Batch ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
            $batch_ID_single = $cf['Values'][0];
            error_log('single sync ID ' . $batch_ID_single);
        }
    }
    return $batch_ID_single;
}

//SAVING FROM API TO LOCAL JSON DATA
//CONSUMER DATA
function saveConsumerData(){

    $batch_ID_consumer = getBatchID_Consumer();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_cons = $MailChimp->get('batches/' . $batch_ID_consumer);
    $batch_response_cons  = json_encode($batch_response_cons);
    $dir =  realpath("downloads/consumer/json") . '/'. $batch_ID_consumer . '.json';
            $file = fopen($dir,'w');
            fwrite($file, $batch_response_cons);
            fclose($file);
            error_log('json file for consumer has been created ');
}
//MERCHANT DATA
function saveMerchantData(){ 

    $batch_ID_consumer = getBatchID_Merchant();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_cons = $MailChimp->get('batches/' . $batch_ID_merchant);
    $batch_response_merch  = json_encode($batch_response_merch);
    $dir =  realpath("downloads/merchant/json") . '/'. $batch_ID_merchant . '.json';
            $file = fopen($dir,'w');
            fwrite($file, $batch_response_merch);
            fclose($file);
            error_log('json file for merchant has been created ');

}
//ALL BATCHES
function saveAllBatches(){ 
    $batchName =  'allbatches';
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $get_all_batch = $MailChimp->get('batches?count=500');
    $get_all_batch = json_encode($get_all_batch);
    $dir =  realpath("downloads/batches") . '/'. $batchName . '.json';
            $file = fopen($dir,'w');
            fwrite($file,  $get_all_batch);
            fclose($file);
            error_log('json file for all batches has been created ');
           
}

// FOR FREE ACCOUNTS ONLY
function getSingleSyncSuccessCount() {
    $batch_id_single  = getBatchID_single_sync();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_single = $MailChimp->get('batches/' .  $batch_id_single );
    $successSync= $batch_response_single['finished_operations'];
    if ($successSync == ''){
        $successSync =  0;
    }
    return $successSync;
}

function getSingleSyncFailedCount() {
    $batch_id_single  = getBatchID_single_sync();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_single = $MailChimp->get('batches/' .  $batch_id_single );
    $failedSync = $batch_response_single['errored_operations'];
    if ($failedSync == ''){
        $failedSync =  0;
    }
    return $failedSync;
}


//FOR PREMIUM ACCOUNTS
//this values should get the data to the json files
function getBatchInfo_consumerStatus(){

    $batch_ID_consumer = getBatchID_Consumer();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_cons = $MailChimp->get('batches/' . $batch_ID_consumer);
    $status =  $batch_response_cons['status'];

    return $status;
}

function getBatchInfo_consumerSuccessCount(){

    $batch_ID_consumer = getBatchID_Consumer();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_cons = $MailChimp->get('batches/' . $batch_ID_consumer);
    $successSync = $batch_response_cons['finished_operations'];

    return $successSync;

}

function getBatchInfo_consumerFailedCount(){
    $batch_ID_consumer = getBatchID_Consumer();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);
    $batch_response_cons = $MailChimp->get('batches/' . $batch_ID_consumer);
    $failedSync = $batch_response_cons['errored_operations'];


    return $failedSync;
}

//MERCHANT BATCH LOGS

function getBatchInfo_merchantSuccessCount(){                                                                                              
    $batch_ID_merchant = getBatchID_Merchant();
    $clientsecret = getSecretKey();                                                                                                       
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_merch = $MailChimp->get('batches/' . $batch_ID_merchant);
    $successSync = $batch_response_merch['finished_operations'];

    return $successSync;
}

function getBatchInfo_merchantFailedCount(){
    $batch_ID_merchant = getBatchID_Merchant();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_merch = $MailChimp->get('batches/' . $batch_ID_merchant);
    $failedSync = $batch_response_merch['errored_operations'];

    return $failedSync;
}

function getBatchInfo_merchantStatus() {
    $batch_ID_merchant = getBatchID_Merchant();
    $clientsecret = getSecretKey();
    $MailChimp = new MailChimp($clientsecret);

    $batch_response_merch = $MailChimp->get('batches/' . $batch_ID_merchant);
    $status = $batch_response_merch['status'];

    return $status;

}
function getAllBatches() {
    // $clientsecret = getSecretKey();
    // $MailChimp = new MailChimp($clientsecret);
   // $get_all_batch = $MailChimp->get('batches/');  //toggle this if you want to get real time response from Mailchimp
     $get_all_batch = extractJSONDataBatches();
     $batches = json_encode($get_all_batch);
    // log all the batches for debugging
    error_log('synced all batches' .$batches);
    return $get_all_batch;
}

//TOTAL COUNTS FOR CONSUMER / MERCHANTS BATCHES ======================================================================

function getOperationTotal() {

    $merchant_success_sync_count = getBatchInfo_merchantSuccessCount();
    $consumer_success_sync_count = getBatchInfo_consumerSuccessCount();

    $total_success_sync_count = $merchant_success_sync_count + $consumer_success_sync_count;
    error_log('Total ' . $total_success_sync_count);
    return $total_success_sync_count;
}

//FOR FREE ACCOUNT OPTION

function getOperationTotalSingleSync() {

    $TotalSyncCount = getSingleSyncSuccessCount();
    error_log('Total ' . $TotalSyncCount);

    return $TotalSyncCount;
}

function getFailedSyncTotal() {
   
   $merchant_failed_sync_count =  getBatchInfo_merchantFailedCount();
   $consumer_failed_sync_count =  getBatchInfo_consumerFailedCount();

   $total_failed_sync_count =  $merchant_failed_sync_count + $consumer_failed_sync_count;

   if ($total_failed_sync_count == 'NULL'){
       return 0;
   }
   else {
    error_log('Failed ' . $total_failed_sync_count);
    return $total_failed_sync_count; //=  $merchant_failed_sync_count + $consumer_failed_sync_count;
   }
  
}

// FOR FREE ACCOUNT OPTION
function getFailedSyncTotalSingleSync() {
    $TotalSyncCount = getSingleSyncFailedCount();
    error_log('Total ' . $TotalSyncCount);
    return $TotalSyncCount;
 
    if ($TotalSyncCount == 'NULL'){
        return 0;
    }
    else {
     error_log('Failed single ' . $TotalSyncCount);
     return $$TotalSyncCount; //=  $merchant_failed_sync_count + $consumer_failed_sync_count;
    }
   
 }

function getSuccessSyncTotal() {
    $total_operation = getOperationTotal();
    $total_failed = getFailedSyncTotal();
    $total_success = $total_operation - $total_failed;
    error_log($total_success);
    return $total_success;
}

//FOR FREE ACCOUNT OPTION
function getSuccessSyncTotalSingleSync() {
    $total_operation = getOperationTotalSingleSync();
    $total_failed = getFailedSyncTotalSingleSync() ;
    $total_success = $total_operation - $total_failed;
    error_log($total_success);
    return $total_success;
}


////////////////////.TAR FILE FUNCTIONS//////////////////////////////

function download_remote_file_with_curl($file_url, $save_to)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 0); 
		curl_setopt($ch,CURLOPT_URL,$file_url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$file_content = curl_exec($ch);
		curl_close($ch);
 
		$downloaded_file = fopen($save_to, 'w');
		fwrite($downloaded_file, $file_content);
		fclose($downloaded_file);
 
    }

function getTarFile($batch_id,$clientSecret,$userType,$dir){  //$dir param will be used to store the json file into one dir = responses dir
    do {
        error_log('waiting for batch response....');   
        $MailChimp = new MailChimp($clientSecret);

        $batch_response_merch = $MailChimp->get('batches/' . $batch_id);
        error_log('resp ' .  json_encode($batch_response_merch));
        // 1. Get the URL of the tar file.
        $response_url = $batch_response_merch['response_body_url'];
        // 2. Download the tar file to the downloads dir
        $url = $response_url;
    } 
    while ($url === '');
    error_log('Response URL: ' . $url);
    download_remote_file_with_curl($url, realpath("downloads/") . '/' . $userType . '/'. $batch_id . '.tar');

//convert the tar file to .zip data to /downloads/usertype/json
$pwd = dirname(__FILE__);
$extractTo = realpath("downloads/") . '/' .$userType;
$tarGzArchive = realpath("downloads/") . '/' .$userType .'/' .$batch_id . '.tar';

$destinationPath = $extractTo;
$p = new PharData($tarGzArchive, RecursiveDirectoryIterator::SKIP_DOTS);
$p->convertToData(Phar::ZIP);
$createdZipArchive = str_replace('tar.gz', 'zip', $tarGzArchive);
$zip = new ZipArchive;
$res = $zip->open($createdZipArchive);
if ($res === TRUE) {
    $zip->extractTo($destinationPath);
    $zip->close();
}
//create a new directory with the batch id name;
//tempo change to $dir 
//second extraction
$destinationPath = realpath("downloads/") . '/' . $dir . '/json' . '/' .$batch_id;
error_log('this is the destination path '. $destinationPath);                                

if (!file_exists($destinationPath)) {
    mkdir($destinationPath, 0777, true);
}
$zip = realpath("downloads/") . '/' . $userType . '/'.$batch_id. '.zip';
$zip1 = new ZipArchive;
$res = $zip1->open($zip);
if ($res === TRUE) {
    $zip1->extractTo($destinationPath);
    $zip1->close();
    echo 'Debug:Log:: Zip extracted to '. $destinationPath;
}
}

//This must return a dynamin batch_id per sync
function getJSONFile($batch_id,$dir){   //$dir = responses
    //$batch_ID_merchant = getBatchID_Merchant();
    $batch_dir = realpath("downloads/") . '/' . $dir . '/json' . '/' .$batch_id . '/';

    error_log('batch dir ' . $batch_dir);
    $firstFile = scandir($batch_dir)[2];
    return $firstFile;
}

function extractJSONData($batch_id,$dir){
    //$batch_ID_merchant = getBatchID_Merchant();
    $jsonFile =  getJSONFile($batch_id,$dir);
    error_log($jsonFile);
    $batch_dir =  realpath("downloads/") . '/' . $dir . '/json' . '/' . $batch_id . '/' .$jsonFile;
    $str = file_get_contents($batch_dir);
    $json = json_decode($str,true);
    return $json;
}

function getJSONFileBatches(){
    $dir = realpath("downloads/batches/");
    $firstFile = scandir($dir)[2];
    return $firstFile;
}
//add another json extraction function to facilitate all the batches response

function extractJSONDataBatches(){
    $jsonFile =  getJSONFileBatches();
    error_log($jsonFile);
    $dir =  realpath("downloads/batches/") . '/' .$jsonFile;
    $str = file_get_contents($dir);
    error_log('This is str ' . $str);
    $json = json_decode($str,true);
    return $json;
}

function getData($batch_id,$dir) {
    $batch_result = extractJSONData($batch_id,$dir);
    return $batch_result;
}

function getDataBatches(){
    $batch_result = extractJSONDataBatches();
    return $batch_result;
}
//***************************************************************************************** FUNCTIONS TO MODIFY JSON DATE*************************************************************

//this function will classify if the batch is for merchant or consumer , since batch response does not provide list ID/name

function setUserToData($batch_id, $dir, $userType){
    $jsonFile =  getJSONFile($batch_id,$dir); 
    $userData  = getData($batch_id, $dir);
    $lastRunCount = getLastRun();
    error_log('run count userdata ' . $lastRunCount);
    $userData[0]['operation_id'] = $userType;
    $userData[0]['run_no'] =  $lastRunCount; //add the run_no key to json array.
    $userTypeValue = json_encode($userData);
    $json =  realpath("downloads/") . '/' . $dir . '/json' . '/' . $batch_id . '/' .$jsonFile;
    file_put_contents($json, $userTypeValue);
    error_log($userTypeValue);
    error_log('User / run no. has been set to json data');
}

//created this function to  convert the sync dates to a dynamic timezone
function convertSyncDate($timezone_name){
    $jsonFile =  getJSONFileBatches();
    $userData  = getDataBatches();
    date_default_timezone_set($timezone_name);
    error_log('Default timezone set to ' . $timezone_name);
    
    foreach($userData['batches'] as $key => $field) {
        $dateString =  $field['submitted_at'];
        $date = strtotime($dateString);
        $fdate =  date('d/m/Y H:i', $date);
        $fdate2 = date('Y-m-d H:i', $date);
        error_log($fdate2);
        $userData['batches'][$key]['submitted_at'] = $fdate;
        $userData['batches'][$key]['completed_at'] = $fdate2;  //converted this one with diff format so I can sort it on frontend table 
    }


     $dateValue = json_encode($userData);
     $json = realpath("downloads/batches/") . '/' .$jsonFile;
     file_put_contents($json, $dateValue);
     error_log('Date has been modified to json data');
}

//adding this function to set the role for each batch, merchant or consumer. we will modfy the status key inside the allbatches.json file  and set the role.
function setRoleToBatch() {  //should be change to setRoleLastRun();

    //1.Loop through each batch details and get the batch ID
    $jsonFile =  getJSONFileBatches();
    $batchData  = getDataBatches();

    foreach($batchData['batches'] as $key => $field){
        $batch_id  = $field['id']; //this will value will be use to search the json file inside the ..downloads/responses/json
        $status = $field['status'];  //we will modify this key and set the role since this will not be used for any display purposes

    //2. Get the json data for each batch id
     $responseData = getData($batch_id,'responses');
     error_log('run info ' . json_encode($responseData));
     $role = $responseData[0]['operation_id'];  // this is the role key
     $lastrun =   $responseData[0]['run_no'];  //this is the last run count
     error_log($lastrun);
     error_log('last run ' . $lastrun);
    //3. Modify the status key to the role.
     $batchData['batches'][$key]['status'] = $role; 
     $batchData['batches'][$key]['response_body_url'] = $lastrun;      
    }
     $roleValue = json_encode($batchData);
     error_log('Set role/ run func ' . $roleValue);
     $json = realpath("downloads/batches/") . '/' .$jsonFile;
     file_put_contents($json, $roleValue);
     error_log('Role and run number has been set to json data');
}

function processUserData($clientSecret,$batch_id,$userType,$dir){
    getTarFile($batch_id,$clientSecret,$userType,$dir);
    getJSONFile($batch_id,$dir); //this should be removed
    extractJSONData($batch_id,$dir); //this as well
    getData($batch_id,$dir);
    setUserToData($batch_id, $dir, $userType); //added function to modify the json data
    // addRunData($batch_id, $dir);
    error_log('successfully processed the ' .$userType . ' data');
}

?>
