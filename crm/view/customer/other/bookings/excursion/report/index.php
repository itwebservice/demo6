<?php
include "../../../../../../model/model.php";

$customer_id = $_SESSION['customer_id'];
?>
    <!-- Filter-panel -->

    <div class="app_panel_content Filter-panel">
		<div class="row">		
			<div class="col-md-3">
				<select name="exc_id_filter" id="exc_id_filter" style="width:100%" onchange="exc_report_list_reflect()">
			        <option value="">Select Booking</option>
			        <?php 
			        $sq_exc = mysqlQuery("select * from excursion_master where customer_id='$customer_id' ");
			        while($row_exc = mysqli_fetch_assoc($sq_exc)){
						$date = $row_exc['created_at'];
						$yr = explode("-", $date);
						$year =$yr[0];
			          $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_exc[customer_id]'"));
			          ?>
			          <option value="<?= $row_exc['exc_id'] ?>"><?= get_exc_booking_id($row_exc['exc_id'],$year).' : '.$sq_customer['first_name'].' '.$sq_customer['last_name'] ?></option>
			          <?php
			        }
			        ?>
			    </select>
			</div>	
		</div>
	</div>

<div id="div_exc_report_list" class="main_block"></div>

<script>
$('#exc_id_filter').select2();
	function exc_report_list_reflect()
	{
		var exc_id = $('#exc_id_filter').val();

		$.post('bookings/excursion/report/exc_report_list_reflect.php', { exc_id : exc_id }, function(data){
			$('#div_exc_report_list').html(data);
		});
	}
	exc_report_list_reflect();
</script>
<script src="<?php echo BASE_URL ?>js/app/footer_scripts.js"></script>