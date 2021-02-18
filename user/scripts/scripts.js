(function() {
    var pathname = (window.location.pathname + window.location.search).toLowerCase();
    var token = commonModule.getCookie('webapitoken');
    const packageVersion = "1.0.1";
    const localstorageLifetime = 86400;
    var hostname = window.location.hostname;
    var scriptSrc = document.currentScript.src;
    var packagePath = scriptSrc.replace('/scripts/scripts.js', '').trim();
    var re = /([a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12})/i;
    var packageId = re.exec(scriptSrc.toLowerCase())[1];
    var customFieldPrefix = packageId.replace(/-/g, "");
    var userId = $('#userGuid').val();
    var getPackageCustomFieldCache = userId + "_" + packageId;
    var clientsecret,mailchimp_status;
 
    var timezone_offset_minutes = new Date().getTimezoneOffset();
    timezone_offset_minutes = timezone_offset_minutes == 0 ? 0 : -timezone_offset_minutes;
    isnext =  false;

    function getURLParam(key, target) {
        var values = [];
        if (!target) target = location.href;

        key = key.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");

        var pattern = key + '=([^&#]+)';
        var o_reg = new RegExp(pattern, 'ig');
        while (true) {
            var matches = o_reg.exec(target);
            if (matches && matches[1]) {
                values.push(matches[1]);
            } else {
                break;
            }
        }
        if (!values.length) {
            return null;
        } else {
            return values;
        }
    }
    
    function getPackageCustomFields(callback) {
        if (window.localStorage.getItem(getPackageCustomFieldCache) != null) {
            var value = JSON.parse(window.localStorage.getItem(getPackageCustomFieldCache));
            var version = value['version'];
            if (version === packageVersion) {
                var customFields = value['customFields'];
                callback(customFields);
                return;
            }
        }

        var apiUrl = '/api/developer-packages/custom-fields?packageId=' + packageId;
        $.ajax({
            url: apiUrl,
            method: 'GET',
            contentType: 'application/json',
            success: function(response) {
                if (response) {
                    const packageInfo = {
                        version: packageVersion,
                        customFields: response,
                    }
                    window.localStorage.setItem(getPackageCustomFieldCache, JSON.stringify(packageInfo));
                    callback(response);
                }
            }
        });
    }

    function getMarketplaceCustomFields(callback) {
        if (window.localStorage.getItem(hostname) != null) {
            var value = JSON.parse(window.localStorage.getItem(hostname));
            var version = value['version'];
            if (version === packageVersion) {
                var customFields = value['customFields'];
                callback(customFields);
                return;
            }
        }

        var apiUrl = '/api/v2/marketplaces'
        $.ajax({
            url: apiUrl,
            method: 'GET',
            contentType: 'application/json',
            success: function(response) {
                if (response) {
                    const marketplaceInfo = {
                        version: packageVersion,
                        customFields: response.CustomFields,
                    }
                    window.localStorage.setItem(hostname, JSON.stringify(marketplaceInfo));
                    callback(response.CustomFields);
                }
            }
        });
    }
    function getUserInfo(id, callback) {

        var apiUrl = '/api/v2/users/' + (id == null ? userId : id);
        $.ajax({
            url: apiUrl,
            method: 'GET',
            contentType: 'application/json',
            success: function(response) {
                if (response) {
                    callback(response);
                }
            }
        });
    }

    //for new users
    function update_consumers()
    {
        var email = $('#notification-email').val();
        var lastname = $('#input-lastName').val();
        var firstname = $('#input-firstName').val(); 
        var address = $('#myaddress').val();
        var country = $('#country :selected').text();
        var state = $('#state').val();
        var city = $('#city').val();
        var postcode = $('#postal-code').val();
        var phone = $('#input-contactNumber').val();
       
        var data = {
            'email': email, 'firstname': firstname, 'lastname': lastname, 'address': address, 'country': country, 'state': state, 'city': city, 'postcode': postcode, 'phone' : phone,
            'userId': userId, 'timezone': timezone_offset_minutes
        };

        console.log(data);
                var apiUrl = packagePath + '/update_consumer_info.php';
                $.ajax({
                    url: apiUrl, 
                    type: 'POST',
                    data: JSON.stringify(data),
                    success: function (result)
                    {
                        
                        console.log(JSON.stringify(result));
                    }

                });
         //  }

}

function update_basic_info(){   
        getMarketplaceCustomFields(function(result) {
            $.each(result, function(index, cf) {
                if (cf.Name == 'Mailchimp Client Secret' && cf.Code.startsWith(customFieldPrefix)) {
                   
                    clientsecret = cf.Values[0];                          
                }
                if (cf.Name == 'Single Sync Status' && cf.Code.startsWith(customFieldPrefix)) {
                    
                    mailchimp_status = cf.Values[0];   
                }
            })
           // if (mailchimp_status == 'true') { 
                var data = { 'firstname': $('#input-firstName').val(), 'lastname': $('#input-lastName').val(), 'contactnumber': $('#input-contactNumber').val(), 'email' : $('#notification-email').val(), 'userId': userId};
                var apiUrl = packagePath + '/update_basic_info.php';
                $.ajax({
                    url: apiUrl, 
                    type: 'POST',
                    data: JSON.stringify(data),
                    success: function (result)
                    {
                        console.log(JSON.stringify(result));
                    }
                });
           // }   
        });
    }

 function update_address(){
        getMarketplaceCustomFields(function(result) {
            $.each(result, function(index, cf) {
                if (cf.Name == 'Mailchimp Client Secret' && cf.Code.startsWith(customFieldPrefix)) {
                    var code = cf.Code;
                    clientsecret = cf.Values[0];                        
                }

                if (cf.Name == 'Mailchimp Status' && cf.Code.startsWith(customFieldPrefix)) {
                    var code = cf.Code;
                    mailchimp_status = cf.Values[0];   
                }

            })
           // if (mailchimp_status == 'true') {    
                var data = { 'address': $('#myaddress').val(), 'country': $('#country').val(), 'city': $('#city').val(), 'state' : $('#state').val(), 'zip' : $('#postal-code').val(), 'userId': userId, 'client-secret': clientsecret, 'email': $('#notification-email').val() };
                var apiUrl = packagePath + '/update_address.php';
                $.ajax({
                    url: apiUrl, 
                    type: 'POST',
                    data: JSON.stringify(data),
                    success: function(result) {
                    }
                });
            // }

         });

}

    $(document).ready(function ()
    {

        getMarketplaceCustomFields(function (result)
        {
            $.each(result, function (index, cf)
            {
                if (cf.Name == 'Mailchimp Client Secret' && cf.Code.startsWith(customFieldPrefix)) {
                    var code = cf.Code;
                    clientsecret = cf.Values[0];
                }

                if (cf.Name == 'Mailchimp Status' && cf.Code.startsWith(customFieldPrefix)) {
                    var code = cf.Code;
                    mailchimp_status = cf.Values[0];
                }
                
            })
           
        });

        if (pathname.indexOf('/user/marketplace/user-settings') > -1 || pathname.indexOf('/user/marketplace/seller-settings') > -1) {

            console.log('in user settings mailchimp');
            $('body').on("click", '#profile #next-tab', function ()
        {
            console.log('basic info');

            update_basic_info(); // for basic infor
      
        });

        $('body').on("click",'.btn-area .my-btn', function ()
        {
            console.log('address');
            update_address();
            update_consumers();
        });

        //add new consumers
        // $('body').on("click", '#address #next-tab',function ()
        // {
        //     update_consumers();
        // });
    }
         
 });
   
})();