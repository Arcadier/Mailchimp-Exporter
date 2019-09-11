<?php
include 'syncLogs.php';
$batch_id = $_GET['id'];
$date = $_GET['date'];
$userData =  getData($batch_id,'responses');
$user_role = $userData[0]['operation_id'];
?>
<!-- begin header -->
<!-- package css-->
<link href="css/adminstyle.css" rel="stylesheet" type="text/css">
<link href="css/mailchimp.css" rel="stylesheet" type="text/css">
<link href="css/mailchimp-responsive.css" rel="stylesheet" type="text/css">
<!-- end header -->
    <div class="page-content">
     <!-- <div class = "page-mailchimpsync-log"> -->
     <div class="page-topnav"> <a class="btn-back" href="mailchimp_synclog.php"><i class="icon icon-arrowleft"></i> Back</a> </div>
      <div class="gutter-wrapper">
        <div class="panel-box">
          <div class="page-content-top">
            <div class="sync-time">  
              <h3>Export Log Time: <span class="d-time"><?php echo $date ?> </span></h3> 
            </div>
           
            <div class="search-box-log">
              <input type="text" name="" id="search"  onkeyup='searchTable()' placeholder="Search name or email">
              <!-- <button class="bl-search"><img src="images/search-log-btn.svg" alt=""></button> -->
            </div>
          </div>

        <?php
            echo "<div class='page-mailchimpsync-log'>";
            echo "<div class='blsl-list-tblsec tbl-mailchimpsync-logs'>";
            echo "<div class='tab-content'>";
            echo "<div id='all' class='tab-pane  active' rold='tabpanel'>";
            echo "<table id='no-more-tables1'>";
            echo "<thead>";
            echo "<tr id = 'trHeader'>";
            echo "<th>First Name</th>";
            echo "<th>Last Name</th>";
            echo "<th>Email</th>";
            echo "<th style='display:none;'>Role</th>";
            echo "<th>Status</th>";
            echo "<th>Sync Error</th>";
            echo "</tr>";
            echo "</thead>";
            echo  "<tbody>";
            foreach($userData as $batch){
              echo  "<tr>";
              // insert loop here for the batches data
              try {
                  $response = $batch['response'];
                  //$response = $userData['response'];
                  $decResponse =  json_decode($response,true); 
                  $error= $decResponse['errors'];
                 
                  $role = $user_role;
                  $status = $batch['status_code'];
                  $syncError = $decResponse['detail'];
                  $errorTitle = $decResponse['title'];
                  $email = 'No details found.';
                  $firstname = 'No details found';
                  $lastname = 'No details found.';
                  //convert the status code to pass of fail . refer to list of mailchimp batch errors
                  //in this case, if the response !=200, it is considered failed.

                  if ($status != 200){
                    $status =  'Fail';
                    //add more conditions here to facilitate the values of email,fname,lname, if the status is Fail
                    switch($errorTitle) { //trim the response, remove the jargon
                      case "Member Exists":
                          $syncError_trim = strstr($syncError,'Use',true);
                          break;  
                      case "Invalid Resource":  // add another case if the errors array is not present in the result, hence put the details 
                         try {
                            if (array_key_exists("errors",$decResponse)) {
                          // if (!isNullOrUndefined($error)){  //there are instance when the response returns no errors[] 
                                $syncError = $error[0]['message']; 
                                $syncError_trim =  $syncError;
                              }else {
                                $syncError_trim =  $syncError;
                              }
                              break;
                         }
                         catch(Exception $e){
                        error_log($e);
                         }
                          //should handle also the invalid emails
                      case "Resource Not Found":
                          $syncError_trim =  $syncError;
                          break;
                    }
                    
                  }
                  else {
                    $status = 'Success';
                    $email =  $decResponse['email_address'];
                    $firstname = $decResponse['merge_fields']['FNAME'];
                    $lastname = $decResponse['merge_fields']['LNAME'];
                    // $role1 = $decResponse['merge_fields']['MMERGE6'];
                    $syncError_trim = 'No sync errors.';
                    

                  }
                  echo "<td data-title='First Name'>" . ucfirst($firstname) . "</td>";
                  echo  "<td data-title='Last Name'>" . ucfirst($lastname). "</td>";
                  echo  "<td data-title='Email'>" . $email . "</td>";
                  echo  "<td data-title='Role' style='display:none;'>" . ucfirst($role1). "</td>";
                    if ($status == 'Fail') {
                      echo  "<td data-title='Status' style='color:red;'>" . $status . "</td>";
                    }else {
                      echo  "<td data-title='Status'>" . $status . "</td>";
                    }
                  echo  "<td data-title='Sync Error' style='font-size:11px;'>" . $syncError_trim. "</td>";
                  echo "</tr>";
            }
                  catch (Exception $e ) {
                  error_log('caught exception ' . $e);
                  }
            }
              echo  "</tbody>";
              echo "</table>";
              echo "</div>";
              echo "</div>";
              echo "</div>";
              echo "</div>";
?>
              </div>
            </div>

            </div>
          </div>
        </div>
      </div>
    </div>


  <div class="clearfix"></div>

</div>
<div id="cover"></div>

<!-- begin footer -->
<script type="text/javascript"></script>
  
<script type="text/javascript" src="http://bootstrap.arcadier.com/adminportal/js/custom-nicescroll.js"></script>
<script>

//added search functionality
function searchTable() {
    var input, filter, found, table, tr, td, i, j;
    input = document.getElementById("search");
    filter = input.value.toUpperCase();
    table = document.getElementById("no-more-tables1");
    tr = table.getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td");
        for (j = 0; j < td.length; j++) {
            if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                found = true;
            }
        }
        if (found) {
            tr[i].style.display = "";
            found = false;
        } else {
            //tr[i].style.display = "none";
            
            if (tr[i].id != 'trHeader'){tr[i].style.display = "none";} 

        }
    }
}
</script>
<script>

$(document).ready(function() {
 
    $('#no-more-tables1').DataTable(
        {
        // "paging":   false,
        "order": [[ 1, "desc" ]],
        "lengthMenu": [[20], [20]],
        // "ordering": false,
        "info":     false,
        "searching" :false,
        "pagingType": "first_last_numbers",
        "columnDefs": [{ orderable: false, targets: [5] }]
        }
    );

    waitForElement('#no-more-tables1_wrapper',function(){
   var pagediv =  "<div class ='paging' id = 'pagination-insert'> </div>";
   $('#no-more-tables1_wrapper').append(pagediv);
    });


    waitForElement('#no-more-tables1_length',function(){
    $('#no-more-tables1_length').css({ display: "none" });
    });

    waitForElement('#pagination-insert',function(){
    var pagination  = $('#no-more-tables1_paginate');
    $('#pagination-insert').append(pagination);

    });




});
function waitForElement(elementPath, callBack){
	window.setTimeout(function(){
	if($(elementPath).length){
			callBack(elementPath, $(elementPath));
	}else{
			waitForElement(elementPath, callBack);
	}
	},10)
}

</script>

           <script type="text/javascript" src="scripts/jquery.dataTables.js"></script>
  <!-- end footer -->      





























</body>
</html>