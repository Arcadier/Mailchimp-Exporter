
<!-- package css-->
<link href="css/adminstyle.css" rel="stylesheet" type="text/css">
<link href="css/mailchimp.css" rel="stylesheet" type="text/css">
</head>

  <div class="page-content">
    <div class="page-topnav"> <a class="btn-back" href="index.php"><i class="icon icon-arrowleft"></i> Back</a> </div>
      <div class="gutter-wrapper">
        <div class="panel-box">
          <div class="page-content-top">
            <div><i class="icon icon-mailchimp icon-3x"></i></div>
            <div>
              <h3>MailChimp Export Logs</h3>
            </div>
          </div>
          <?php 
          include 'syncLogs.php';
          $batches =  getAllBatches();
         ?>
           <div class="page-mailchimpsync-log">
             <div class="blsl-list-tblsec tbl-mailchimpsync-logs">
               <table id="no-more-tables1">
                  <thead>
                  <tr>
                      <th>Run Number</th>
                      <th data-sorter="false">Export Time</th>
                      <th data-sorter="false">User Role</th>
                      <th data-sorter="false">Total Users</th>
                      <th data-sorter="false">Successful Users</th>
                      <th data-sorter="false">Failed Users</th>
                      <th data-sorter="false">&nbsp;</th>
                  </tr>
                </thead>
            <tbody>
              <?php
             foreach($batches['batches'] as $batch){

              echo  "<tr>";
                   $date = $batch['submitted_at'];
                   $lastrun  = $batch['response_body_url'];  
                   $date2 =  $batch['completed_at'];
                   $batch_id = $batch['id'];
                   $role = $batch['status'];
                   $run_no = $batch['response_body_url'];
 
                  $totalOperation = $batch['total_operations'];
                  $errorOperation = $batch['errored_operations'];
                  $successOperations = $totalOperation - $errorOperation;
              ?>
                 <td data-title="Run Number"><?php echo $run_no; ?> </td> 
                 <td data-title="Sync Time"><?php echo $date; ?></td>
                 <td data-title="User Role"> <?php echo ucfirst($role); ?> </td>
                  <td data-title="Total Users"><?php echo $totalOperation; ?> </td>
                  <td data-title="Successful Users"><?php echo $successOperations; ?> </td>
                 <td data-title="Failed Users"><?php echo $errorOperation; ?> </td>
                  <td><a href="mailchimp_userlog.php?id=<?php echo $batch_id; ?> &date=<?php echo $date; ?>" class="blue-btn">View Users</a></td>
                 </tr>
            <?php
            }
            ?>
            </tbody>
           </table>
            </div>
            </div>
            

          </div>
        </div>
      </div>
    </div>
  </div>

  </div>
</div>
<div class="clearfix"></div>
<div id="cover"></div>

 <!-- begin footer -->
<script type="text/javascript" src="http://bootstrap.arcadier.com/adminportal/js/custom-nicescroll.js">
</script>

<script>

$(document).ready(function() {
 
 $('#no-more-tables1').DataTable(
     {
     // "paging":   false,
     "order": [[ 0, "desc" ]],
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