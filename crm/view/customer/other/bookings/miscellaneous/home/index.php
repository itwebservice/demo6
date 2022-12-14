<?php 
include "../../../../../../model/model.php";

$customer_id = $_SESSION['customer_id'];
?>
    <!-- Filter-panel -->

      <div class="app_panel_content Filter-panel">
		<div class="row">
			<div class="col-md-3">
				<select name="misc_id_filter" id="misc_id_filter" class="form-control" onchange="misc_customer_list_reflect()">
			        <option value="">Select Booking</option>
			        <?php 
			        $sq_misc = mysqlQuery("select * from miscellaneous_master where customer_id='$customer_id'");
			        while($row_misc = mysqli_fetch_assoc($sq_misc)){
							$date = $row_misc['created_at'];
							$yr = explode("-", $date);
							$year =$yr[0];
						$sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_misc[customer_id]'"));
			          ?>
			          <option value="<?= $row_misc['misc_id'] ?>"><?= get_misc_booking_id($row_misc['misc_id'],$year).' : '.$sq_customer['first_name'].' '.$sq_customer['last_name'] ?></option>
			          <?php
			        }
			        ?>
			    </select>
			</div>
		</div>
	  </div>

<div id="div_misc_customer_list_reflect" class="main_block"></div>
<div id="div_misc_content_display" class="main_block"></div>
<script>
$('#misc_id_filter').select2();
	function misc_customer_list_reflect()
	{
		var misc_id = $('#misc_id_filter').val()
		$.post('bookings/miscellaneous/home/misc_list_reflect.php', { misc_id : misc_id }, function(data){
			$('#div_misc_customer_list_reflect').html(data);
		});
	}
	misc_customer_list_reflect();
	
	function misc_display_modal(misc_id)
	{
		$.post('bookings/miscellaneous/home/view/index.php', { misc_id : misc_id }, function(data){
			$('#div_misc_content_display').html(data);
		});
	}
</script>
<script src="<?php echo BASE_URL ?>js/app/footer_scripts.js"></script>