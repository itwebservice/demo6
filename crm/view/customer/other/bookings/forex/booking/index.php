<?php
include "../../../../../../model/model.php";

$customer_id = $_SESSION['customer_id'];
?>
    <!-- Filter-panel -->

    <div class="app_panel_content Filter-panel">
		<div class="row">
			<div class="col-md-3">
				<select name="booking_id_filterf" id="booking_id_filterf" style="width:100%" onchange="list_reflect()">
			        <option value="">Select Booking</option>
			        <?php 
			        $sq_booking = mysqlQuery("select * from forex_booking_master where customer_id='$customer_id'");
			        while($row_booking = mysqli_fetch_assoc($sq_booking)){
						$date = $row_booking['created_at'];
						$yr = explode("-", $date);
						$year =$yr[0];
			          $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
			          ?>
			          <option value="<?= $row_booking['booking_id'] ?>"><?= get_forex_booking_id($row_booking['booking_id'],$year).' : '.$sq_customer['first_name'].' '.$sq_customer['last_name'] ?></option>
			          <?php
			        }
			        ?>
			    </select>
			</div>	
		</div>
	</div>

<div id="div_content" class="main_block"></div>

<script>
$('#booking_id_filterf').select2();
function list_reflect()
{
	var booking_id = $('#booking_id_filterf').val();
	$.post('bookings/forex/booking/list_reflect.php', { booking_id : booking_id }, function(data){
		$('#div_content').html(data);
	});
}
list_reflect();
</script>
<script src="<?php echo BASE_URL ?>js/app/footer_scripts.js"></script>