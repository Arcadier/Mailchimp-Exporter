<!-- begin header -->
<link href="css/adminstyle.css" rel="stylesheet" type="text/css">
<link href="css/mailchimp.css" rel="stylesheet" type="text/css">
<!-- end header -->
<?php
require '../license/license.php';
$licence = new License();
if (!$licence->isValid()) {
    exit;
}

?>
<div class="gutter-wrapper">
    <div class="panel-box">
        <div class="page-content-top">
        <div>
            <i class="icon icon-mailchimp icon-3x"></i>
        </div>
        <div>
            <p>Would you like to activate MailChimp for your marketplace? </p>
            <span>Easily export your marketplace user list to MailChimp</span>
    </div>
                
        <div class="private-setting-switch">
            <div class="onoffswitch">
                <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox switch-private-checkbox " id="myonoffswitch" checked="checked">
                <label class="onoffswitch-label" for="myonoffswitch">
                    <span class="onoffswitch-inner"></span>
                    <span class="onoffswitch-switch"></span>
                </label>
            </div>
        </div>
                
</div>
        <div class="mailchimp-top-note">
            <div><span class="note-red">Please note:</span> This plug-in requires a MailChimp subscription plan of "Essential" or higher to work as intended.</div>
                 <div>Click on the info icon for more information.</div>
           </div>
        </div>
           <div class="panel-box">
            <div class="form-area" id ="mailchampkey-frm-sec">
                <div class="form-element">
                  <label for="client-secret">API Key <a href="https://support.arcadier.com/hc/en-us/articles/360025607493" target="_blank"><span><img src="images/info.svg"></span></a></label>
                  <input type="text" name="client-secret" id="client-secret" class="txt">
                  <div class="pull-right btn-area auto"><a href="https://mailchimp.com/" target="_blank" class="btn-blue">Login to MailChimp</a></div>
                </div>
                <div class="form-element">
                    <div class="user-info-field">
                      <div class="col-md-6">
                        <label>Default First Name</label>
                        
                        <input type="text" name="first-name" id="temp-fname" value="John" class="txt">
                      </div>
                      <div class="col-md-6">
                        <label>Default Last Name</label>
                        <input type="text" name="last-name" id="temp-lname" value="Doe" class="txt">
                      </div>
                    </div>
                  </div>
               <!-- <div class="btn-area"> <a href="javascript:void(0);" class="btn-black" id="save">Save</a> </div> -->
               <div class="btn-area"> <a class="btn-black-mdx" id="save">Save</a> </div>
               <div class="mailchampk-notes-sec">
                <p>Exporting will automatically occur when a new user is created on your marketplace, or a user updates their settings. </p> 
              </div>
               <div class="sync-data">
                   <label>Manually export your list</label>
                   <div class="btn-area" id = "syncclick">
                    <a href="#" class="btn-blue" id="sync" onclick="mlchamplist_sync(this); ">Export List</a> 
                    <div id="brnd_preloader" style="display:none;"></div>
                    <span class="mailchampk-lastsyn-date">Last Export: - <strong id = "syncdate">--</strong></span> </div>
                    <div class="mailchampk-sync-msg hide">

                     
                       <div class="mailchampk-sync-tmsg">Total Sync Count: <span id = "total">  </span> </div>  
                      <div class="mailchampk-sync-smsg"> Successful users: <span id = "success">  </span> </div>
                      <div class="mailchampk-sync-emsg"> Failed users: <span id = "error">  </span> </div>
                      <div class="clearfix"></div>
                   </div>
                   <div class="btn-area vsync-logbtnsec"> <a href="mailchimp_synclog.php" class="btn-black-mdx1" id="vsync_log_btn">View Export Logs</a> </div>
               </div>
            </div>
         
           </div>
          
         </div>
        </div>
    </div>
 <div class="clearfix"></div>

     </div>
        
        <div class="popup-area item-remove-popup confirm-edit-api" id="DeleteCustomMethod">
                <div class="wrapper">
                    <a href="javascript:void(0);" class="close-popup"><img src="images/cross-icon.svg"></a>
                    <div class="content-area">
                        <p>Are you sure you want to edit this?</p>
                    </div>
                    <div class="btn-area text-center smaller">
                        
                            <input  type="button" value="Cancel" class="btn-black-mdx" id="cancel">
                        
                            <input id="edit-confirm" type="button" value="Okay" class="btn-blue">
                        
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
 <!-- begin footer -->
 <script>
        
    
          jQuery(document).ready(function() {

        jQuery(".panel-box-title").click(function() {
            jQuery(this).parents('.panel-box').toggleClass('active');
            jQuery(this).parents('.panel-box').find('.panel-box-content').slideToggle();
        });

        jQuery(".mobi-header .navbar-toggle").click(function(e) {
            e.preventDefault();
            jQuery("body").toggleClass("sidebar-toggled");
        });
        jQuery(".navbar-back").click(function() {
            jQuery(".mobi-header .navbar-toggle").trigger('click');
        });

        /*nice scroll */
        jQuery(".sidebar").niceScroll({ cursorcolor: "#000", cursorwidth: "6px", cursorborderradius: "5px", cursorborder: "1px solid transparent", touchbehavior: true, preventmultitouchscrolling: false, enablekeyboard: true });

        jQuery(".sidebar .section-links li > a").click(function() {
            jQuery(".sidebar .section-links li").removeClass('active');
            jQuery(this).parents('li').addClass('active');
        });
        
        
        jQuery('.private-setting-switch #myonoffswitch').change(function(){ 
                if(jQuery(this).is(':checked'))
                {

                    jQuery('#mailchampkey-frm-sec').slideDown();
                }
                else
                {
                    jQuery('#mailchampkey-frm-sec').slideUp();                  
                }
        });
        
        
        
    });
    $(window).load(function(){
              
        getSyncResults();


            if($('#temp-fname').val() && $('#temp-lname').val() ){

                $(".form-area .btn-black-mdx").attr("id" , "edit");
                setTimeout(function(){$(".form-area .btn-black-mdx").addClass("editView");},500);
                $( ".form-area #edit" ).html( "Edit" );
                $("#temp-fname").attr("disabled", true);
                $("#temp-lname").attr("disabled", true);
            } else{

                $(".form-area .btn-black-mdx").attr("id" , "save");
                $( ".form-area .btn-black-mdx" ).html( "Save" );
                $(".form-area .btn-black-mdx").removeClass("editView");
            }
            $("body").on("click" , "#edit" , function(){
                if($(this).hasClass("editView")){

                    DeleteCustomMethod(this);     
                }
                
            })
            $("#cancel , .close-popup").on("click" , function(){
                cancel_remove();       
            });
            $("body").on("click" , "#edit-confirm" , function(){
                edit_conform();
                $(".form-area .btn-black-mdx").attr("id" , "save");
                $( ".form-area .btn-black-mdx" ).html( "Save" );
                $(".form-area .btn-black-mdx").removeClass("editView");
            });
            $("body").on("click" , "#save" , function(){ 
                if($('#temp-lname').val() &&$('#temp-fname').val() ){
                    $("#temp-lname").attr("disabled", true);
                    $("#temp-fname").attr("disabled", true);
                    $(".form-area .btn-black-mdx").attr("id" , "edit");
                    setTimeout(function(){$(".form-area .btn-black-mdx").addClass("editView");},500);
                    $( ".form-area .btn-black-mdx" ).html( "Edit" );
                } else{
                    $(".form-area .btn-black-mdx").attr("id" , "save");
                    $( ".form-area .btn-black-mdx" ).html( "Save" );
                    $(".form-area .btn-black-mdx").removeClass("editView");
                }           
            });

        });
        function DeleteCustomMethod(obj) {
            jQuery("#cover").fadeIn();
            jQuery(".popup-area.item-remove-popup").fadeIn();
            jQuery(obj).parents('.list_row').addClass('confirm-delete');
        }
        function cancel_remove(ele) {
            var that = jQuery(ele);
            jQuery(".popup-area.item-remove-popup").fadeOut();
            jQuery("#cover").fadeOut();
            jQuery('.list-body .list_row').removeClass('confirm-delete');
        }
        function edit_conform(ele){
        
            jQuery(".popup-area.item-remove-popup").fadeOut();
            jQuery("#cover").fadeOut();
            jQuery('.list-body .list_row').removeClass('confirm-delete');
            $("#temp-lname").attr("disabled", false);
            $("#temp-fname").attr("disabled", false);
        }
        
        function mlchamplist_sync(x)
        {
            jQuery(x).css('background-color','#999');
            //try to align the timing of loader to the ajax response
            $(document).ajaxStart(function () {

                $("#brnd_preloader").show();
              }).ajaxStop(function (mlchamplist_sync) {

                $("#brnd_preloader").hide();
                var today = new Date();
                
                var time = today.getHours() + ":";
                var minutes=today.getMinutes();
                if(minutes<=9)
                {
                    minutes=+'0'+minutes.toString();
                }
                time=time+minutes;
                
                var date = today.getDate()+'/'+(today.getMonth()+1)+'/'+today.getFullYear();
                jQuery('.mailchampk-lastsyn-date strong').text(date+' '+time);
                jQuery('.mailchampk-lastsyn-date').show();
                
                jQuery('.mailchampk-sync-msg').show();
               // jQuery('#brnd_preloader').hide();
                jQuery(x).css('background-color','#50c8ff');     
            
            });
        }
    
        function getSyncResults() {
            <?php
            include 'syncLogs.php';
            $isSingleSync =  getSyncStatus();
            error_log('sync stats ' . $isSingleSync);
            ?> 
            var success = <?php if ($isSingleSync == 0) {
            echo getSuccessSyncTotal(); }
            else { echo getSuccessSyncTotalSingleSync();
            }
            ?>; 
            if(success == '') {
                success = 0;
            }
            document.getElementById("success").innerHTML = success; 

            var error = <?php if ($isSingleSync == 0) {
                echo getFailedSyncTotal(); }
                else if($isSingleSync == 1) {
                    echo getFailedSyncTotalSingleSync();
                }else {
                    echo 0;
                }
            ?>;
             if(error == '') {
                error = 0;
            }
            document.getElementById("error").innerHTML = error; 

            var total =  <?php if ($isSingleSync == 0) {
                 echo getOperationTotal(); }
                    elseif($isSingleSync == 1) { echo getOperationTotalSingleSync();
                     }else {
                         echo 0;
                     }
                ?>;
                 if(total == '') {
                total = 0;
            }
            document.getElementById("total").innerHTML = total; 
        }

            </script>    
            <script type="text/javascript" src="http://bootstrap.arcadier.com/adminportal/js/custom-nicescroll.js"></script>
            <script type="text/javascript" src="scripts/package.js"></script>
            <script type="text/javascript" src="scripts/jquery.dataTables.js"></script>
            <!-- end footer -->        
        