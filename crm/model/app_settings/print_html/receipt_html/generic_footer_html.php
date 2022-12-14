<hr class="no-marg">
<section class="print_sec main_block inv_rece_footer_top">
	<div class="row">
		<div class="col-md-12">
			<h3 class="no-marg font_5 font_s_14">In Words : <?php echo $amount_in_word; ?></h3>
		</div>
	</div>
</section>

<hr class="no-marg">

<!-- invoice_receipt_footer -->
<section class="print_sec main_block inv_rece_footer_bottom">
<div class="inv_rece_footer_signature border_block mg_bt_10">
<div class="row mg_bt_20">
	<div class="col-md-7 border_rt">
		<div class="inv_rece_footer_left">
			<h3 class="no-marg font_5 font_s_14">TERMS & CONDITIONS</h3>
			<p class="less_opact mg_bt_30"><?= $sq_terms_cond['terms_and_conditions'] ?></p>
			<div class="mg_tp_30 text-right">
				<p class="no-marg font_s_13">RECEIVER SIGNATURE</p>
			</div>
		</div>
	</div>
	<div class="col-md-5">
		<div class="inv_rece_footer_right">
			<h3 class="no-marg font_5 font_s_14">FOR <?= $app_name ?></h3>
		</div>
	</div>
</div>
<div class="signature_block signature_block_r text-right">
	<p class="no-marg font_s_13">AUTHORIZED SIGNATORY</p>
	<p class="no-marg font_s_13">GENERATED BY : <?= $emp_name ?></p>
</div>
</div>

<!-- invoice_receipt_back_detail -->
<div class="border_block inv_rece_back_detail">
	<div class="row">
		<div class="col-md-4"><p class="border_lt"><span class="font_5">BANK NAME : </span><?=  ($branch_details['bank_name']!= '') ?$branch_details['bank_name'] : $bank_name_setting ?></p></div>
		<div class="col-md-4"><p class="border_lt"><span class="font_5">A/C TYPE : </span><?= ($branch_details['acc_name']!= '') ? $branch_details['acc_name'] : $acc_name ?></p></div>
		<div class="col-md-4"><p class="border_lt"><span class="font_5">BRANCH : </span><?= ($branch_details['bank_branch_name']!='') ? $branch_details['bank_branch_name'] : $bank_branch_name ?></p></div>
		<div class="col-md-4"><p class="border_lt no-marg"><span class="font_5">A/C NO : </span><?=  ($branch_details['bank_acc_no']!='') ? $branch_details['bank_acc_no'] : $bank_acc_no ?></p></div>
		<div class="col-md-4"><p class="border_lt no-marg"><span class="font_5">IFSC : </span><?= ($branch_details['ifsc_code'] !='') ? $branch_details['ifsc_code'] : $bank_ifsc_code ?></p></div>
	</div>
</div>
</section>
<?php
if(mysqli_num_rows(mysqlQuery($values_query)) > 0){
	?>
	<div class="col-xs-12 no-pad"> <div class="table-responsive">	
		<table class="table table-hover" id="tbl_list" style="margin: 20px 0 !important;">
		<tr class="table-heading-row">
			<th>Payment Date</th>
			<th>Mode</th>
			<th>Amount</th>
		</tr>
		<?php 
		$values_fetch = mysqlQuery($values_query);
		while($rows = mysqli_fetch_assoc($values_fetch)){
			
			if($receipt_type == 'Hotel Receipt' || $receipt_type == 'Tour Receipt' || $receipt_type == 'Activity Receipt' || $receipt_type == 'Visa Receipt'){
				$payment_amount1 = currency_conversion($currency,$currency_code,$rows[$amount_key]+$rows[$credit_charges]);
			}else{
				if($receipt_type == 'B2B Sale Receipt'){
					$payment_amount1 = number_format($rows[$amount_key],2);
				}else{
					$payment_amount1 = number_format($rows[$amount_key]+$rows[$credit_charges],2);
				}
			}
			?>
			<tr>
				<td><?= date('d-m-Y', strtotime($rows[$date_key]));?></td>
				<td><?= $rows['payment_mode'] ?></td>
				<td><?= $payment_amount1 ?></td>
			</tr>
		<?php
			}
		?>
		</table>
	</div></div>
<?php } ?>
<!-- Bottom_Note -->
<hr class="no-marg">

<section class="print_sec main_block inv_rece_footer_top">
	<div class="row">
		<div class="col-md-12">
			<h3 class="no-marg font_5 font_s_13 text-center less_opact">This is a Computer generated document and does not require any signature</h3>
		</div>
	</div>
</section>

<hr class="no-marg">
</body>
</html>