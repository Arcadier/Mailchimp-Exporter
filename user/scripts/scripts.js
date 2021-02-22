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
   
    var timezone_offset_minutes = new Date().getTimezoneOffset();
    timezone_offset_minutes = timezone_offset_minutes == 0 ? 0 : -timezone_offset_minutes;
    isnext =  false;

    //for new users
function update_consumers(){
            var usertype = pathname.indexOf('/user/marketplace/user-settings') > -1 ? 'consumer' : 'merchant'; 
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
                'userId': userId, 'timezone': timezone_offset_minutes, 'usertype' : usertype
            };

                var apiUrl = packagePath + '/update_consumer_info.php';
                    $.ajax({
                        url: apiUrl, 
                        type: 'POST',
                        data: JSON.stringify(data),
                        success: function (result)
                        {
                            
                           
                        }

                    });

}
function update_basic_info(){   
     
    var data = { 'firstname': $('#input-firstName').val(), 'lastname': $('#input-lastName').val(), 'contactnumber': $('#input-contactNumber').val(), 'email' : $('#notification-email').val(), 'userId': userId};
    var apiUrl = packagePath + '/update_basic_info.php';
    $.ajax({
        url: apiUrl, 
        type: 'POST',
        data: JSON.stringify(data),
        success: function (result)
        {
           
        }
    });
}
 function update_address(){
     
            var data = {
                'address': $('#myaddress').val(), 'country': $('#country').val(),
                'city': $('#city').val(), 'state': $('#state').val(), 'zip': $('#postal-code').val(),
                'userId': userId, 'client-secret': clientsecret, 'email': $('#notification-email').val()
            };
                var apiUrl = packagePath + '/update_address.php';
                $.ajax({
                    url: apiUrl, 
                    type: 'POST',
                    data: JSON.stringify(data),
                    success: function(result) {
                    }
                });

}

$(document).ready(function ()
    {

        if (pathname.indexOf('/user/marketplace/user-settings') > -1 || pathname.indexOf('/user/marketplace/seller-settings') > -1) {

                $('body').on("click", '#profile #next-tab', function ()
            {
                
                update_basic_info(); 
        
            });

            $('body').on("click",'.btn-area .my-btn', function ()
            {
                update_address();
                update_consumers();
            });

       }
         
 });
   
})();