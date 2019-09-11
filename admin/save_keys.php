<?php
include 'callAPI.php';
include 'admin_token.php';
include 'MailChimp.php';
use \DrewM\MailChimp\MailChimp;
$contentBodyJson = file_get_contents('php://input');
$content = json_decode($contentBodyJson, true);

$clientSecret = $content['clientSecret'];
error_log( 'clientsecret  '  .json_encode($clientSecret));

function my_error_handler()
{
  $last_error = error_get_last();
  if ($last_error && $last_error['type']==E_ERROR)
      {
        header("HTTP/1.0 404 Not Found");
        $_SESSION['msg'] = "Successfully saved api keys";
        header("location:index.php");
      }
}
register_shutdown_function('my_error_handler');

$MailChimp = new MailChimp($clientSecret);
error_log($MailChimp->getLastError());

$userId = $content['userId'];
error_log( 'error log  '  .json_encode($userId));
$firstName = $content['firstname'];
$lastname =  $content['lastname'];
$baseUrl = getMarketplaceBaseUrl();
$admin_token = getAdminToken();
$customFieldPrefix = getCustomFieldPrefix();

// Query to get marketplace id
$url = $baseUrl . '/api/v2/marketplaces/';
$marketplaceInfo = callAPI("GET", null, $url, false);

// Query to get package custom fields
$url = $baseUrl . '/api/developer-packages/custom-fields?packageId=' . getPackageID();
$packageCustomFields = callAPI("GET", null, $url, false);

$mailchimpClientIDCustomField = '';
$mailchimpClientSecretCustomField = '';

foreach ($packageCustomFields as $cf) {

    if ($cf['Name'] == 'Mailchimp Client ID' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
           $mailchimpClientIDCustomField = $cf['Code'];
    }
    if ($cf['Name'] == 'Mailchimp Client Secret' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $mailchimpClientSecretCustomField = $cf['Code'];     
    }
    //added for default last/first names
    if ($cf['Name'] == 'Default Lastname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $def_lastname = $cf['Code'];
}

    if ($cf['Name'] == 'Default Firstname' && substr($cf['Code'], 0, strlen($customFieldPrefix)) == $customFieldPrefix) {
        $def_firstname = $cf['Code'];
    }
    
}
$data = [
    'ID' => $marketplaceInfo['ID'],
    'CustomFields' => [
        [
            'Code' => $mailchimpClientSecretCustomField,
            'Values' => [$clientSecret],
        ],
        [
            'Code' => $def_lastname,
            'Values' => [$lastname],
        ],
        [
            'Code' => $def_firstname,
            'Values' => [$firstName],
        ],

    ],
];
$id =  $marketplaceInfo['ID'];
$url = $baseUrl . '/api/v2/marketplaces/';
$result = callAPI("POST", $admin_token['access_token'], $url, $data);

?>

