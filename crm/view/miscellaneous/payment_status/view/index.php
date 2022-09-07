<?php 
include "../../../../model/model.php";

$misc_id = $_POST['misc_id'];

$sq_booking = mysqli_fetch_assoc(mysqlQuery("select * from miscellaneous_master where misc_id='$misc_id'"));
$date = $sq_booking['created_at'];
$yr = explode("-", $date);
$year = $yr[0];
?>
<div class="modal fade profile_box_modal" id="visa_display_modal" role="dialog" aria-labelledby="myModalLabel" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Booking Information(<?= get_misc_booking_id($misc_id,$year) ?>)</h4>
      </div>
      <div class="modal-body profile_box_padding">
	     <div class="row mg_bt_20">    
		  	<div class="col-xs-12">
		  		<div class="profile_box">
		           	<h3 class="editor_title">Passenger Information</h3>
		            <div class="table-responsive">
                    <table class="table table-bordered no-marg">
	                    <thead>
                       <tr class="table-heading-row">
                       	<th>S_No.</th>
                       	<th>Passenger_Name</th>
                       	<th>Date_Of_Birth</th>
                       	<th>Adol</th>
                       	<th>Passport_Id</th>
                       	<th>Issue_Date</th>
                       	<th>Expiry_Date</th>
                       </tr>
                       </thead>
                       <tbody>
                       <?php 
                   		$sq_entry = mysqlQuery("select * from miscellaneous_master_entries where misc_id='$misc_id'");
                        $bg="";
                   		while($row_entry = mysqli_fetch_assoc($sq_entry)){
                   			if($row_entry['status']=="Cancel"){
                   				$bg="danger";
                   			}
                   			else
                   			{
                   				$bg="#fff";
                   			}
                   			$count++;
                   			?>
							 <tr class="<?php echo $bg; ?>">
							    <td><?php echo $count; ?></td>
							    <td><?php echo $row_entry['first_name']." ".$row_entry['last_name']; ?></td>
							    <td><?= get_date_user($row_entry['birth_date']) ?></td>
								  <td><?php echo $row_entry['adolescence']; ?></td>
							    <td><?php echo $row_entry['passport_id']; ?> </td>
							    <td><?php echo get_date_user($row_entry['issue_date']); ?></td>
							    <td><?php echo get_date_user($row_entry['expiry_date']); ?></td>
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
  
</div>
<script src="<?php echo BASE_URL ?>js/app/footer_scripts.js"></script>
<script>
$('#visa_display_modal').modal('show');
</script>