(function() {
    var scriptSrc = document.currentScript.src;
    var packagePath = scriptSrc.replace('/scripts/package.js', '').trim();
    var token = commonModule.getCookie('webapitoken');
    var re = /([a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12})/i;
    var packageId = re.exec(scriptSrc.toLowerCase())[1];
    var customFieldPrefix = packageId.replace(/-/g, "");
    var userId = $('#userGuid').val();
    var consumerID;
    var merchantID;
    var timezone_offset_minutes = new Date().getTimezoneOffset();
    timezone_offset_minutes = timezone_offset_minutes == 0 ? 0 : -timezone_offset_minutes;
  
    function setTimezoneName() {
        var data = { 'timezone':  timezone_offset_minutes };
        var apiUrl = packagePath + '/mailchimp_synclog.php';
        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: JSON.stringify(data),
            success: function(result) {
            }
                             
        });    
    }

    function syncData(){
        getMarketplaceCustomFields(function(result) {
            $.each(result, function(index, cf) {
                if (cf.Name == 'Merchant ID' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                    merchantID =  cf.Values[0];
                }

                if (cf.Name == 'Consumer ID' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                     consumerID =  cf.Values[0];
             
                }             
            })
                                                                                                                                                       //date from inline js
            var data = { 'clientSecret': $('#client-secret').val(), 'merchantID' : merchantID, 'consumerID' : consumerID, 'userId': userId, 'timezone':  timezone_offset_minutes };
            var apiUrl = packagePath + '/batchsubscribe.php';
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: JSON.stringify(data),
                success: function(result) {
                    toastr.success('Emails successfully synced.');
                    localStorage.setItem("SyncSuccess", "Yes");
                    location.reload();
                }                     
            });
        });       
    }

    //added create mailing list function 2/16/19 - revised code for creating mailing list 
    function createList(){
        var data = { 'clientSecret': $('#client-secret').val(),'userId': userId, 'packagePath': packagePath};
        var apiUrl = packagePath + '/createMailingList.php';
        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: JSON.stringify(data),
            success: function(result) {
                }
        });          
    }

    function saveKeys() {
        var data = {  'clientSecret': $('#client-secret').val(), 'clientId': $('#client-id').val(), 'userId': userId,'firstname': $('#temp-fname').val(), 'lastname': $('#temp-lname').val()};
         var apiUrl = packagePath + '/save_keys.php';
        $.ajax({
            url: apiUrl,          
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function($result) {
                console.log($result);
                 toastr.success('Key is saved successfully');
                 $('#sync').removeClass('disabled');
             
            },
            error: function ($result) {
                toastr.error('Invalid Mailchimp API key supplied');
            }
        });
    }
 
    function getMarketplaceCustomFields(callback) {
        var apiUrl = '/api/v2/marketplaces'
        $.ajax({
            url: apiUrl,
            method: 'GET',
            contentType: 'application/json',
            success: function(result) {
                if (result) {
                    callback(result.CustomFields);
                }
            }
        });
    }

    function DisableButton()
        {
            var sync = document.getElementById('sync');
            var apikey = document.getElementById('client-secret');
            var sync = document.getElementById('sync');
                if ( apikey.value.length = 0)
                {
                    sync.Disable = true;
                    }

        }
    $(document).ready(function() {
        if ($('#client-secret').val() == ' '){
            console.log('yea h yeah');
            $('#sync').addClass('disabled');
        }
        // DisableButton();
        var getItem = localStorage.getItem("SyncSuccess");
        if (getItem === "Yes"){
            $('.mailchampk-sync-msg').removeClass("hide");
            localStorage.setItem("SyncSuccess", "No")
        }
        getMarketplaceCustomFields(function(result) {
            $.each(result, function(index, cf) {
                if (cf.Name == 'Mailchimp Client Secret' && cf.Code.startsWith(customFieldPrefix)) {
                    var code = cf.Code;
                    var clientSecret = cf.Values[0];
                    $('#client-secret').val(clientSecret);  
                        
                }
                 clientsecret = clientSecret;
                if (cf.Name == 'Mailchimp Client ID' && cf.Code.startsWith(customFieldPrefix)) {
                      var code1 = cf.Code;
                      var clientId =  cf.Values[0];
                      $('#client-id').val(clientId);    
                  }

                //added additional field for last sync customfields
                if (cf.Name == 'Mailchimp Last Sync' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                    var lastsync =  cf.Values[0];
                    $('#syncdate').text(lastsync);
                 
                }
                //added for default first/ last names
                if (cf.Name == 'Default Lastname' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                    var lastname =  cf.Values[0];
                    $('#temp-lname').val(lastname);
                   
                }

                if (cf.Name == 'Default Firstname' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                    var firstname =  cf.Values[0];
                    $('#temp-fname').val(firstname);
                   
                }

                //added merchant ID and consumer ID
                if (cf.Name == 'Merchant ID' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                     merchantID =  cf.Values[0];
                    
                }

                if (cf.Name == 'Consumer ID' && cf.Code.startsWith(customFieldPrefix)) {
                    var code1 = cf.Code;
                     consumerID =  cf.Values[0];
                    
                }

            })
        });

        $('#save').click(function() {
            $('#sync').addClass('disabled');
            if($('#save').text() == 'Save'){
                saveKeys();
                //add another condition if mailing lists already exists before calling this function
                createList();       
            } 
        });
        $('#sync').click(function() {
            if ( $('#save').text() == 'Edit' && $('#client-secret').val() == '') {
                // toastr.error('Please provide a valid API Key.');
            }else {
                syncData();
           }
         
        });

        $('#sync_log_btn').click(function() {
            setTimezoneName();
        });


    });
})();