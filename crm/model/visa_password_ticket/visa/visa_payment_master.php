<?php
$flag = true;
class visa_payment_master
{

	public function visa_payment_master_save()
	{
		$visa_id = $_POST['visa_id'];
		$payment_date = $_POST['payment_date'];
		$payment_amount = $_POST['payment_amount'];
		$payment_mode = $_POST['payment_mode'];
		$bank_name = $_POST['bank_name'];
		$transaction_id = $_POST['transaction_id'];
		$bank_id = $_POST['bank_id'];
		$branch_admin_id = $_POST['branch_admin_id'];
		$credit_charges = $_POST['credit_charges'];
		$credit_card_details = $_POST['credit_card_details'];

		$payment_date = date('Y-m-d', strtotime($payment_date));

		if ($payment_mode == 'Cheque' || $payment_mode == 'Credit Card') {
			$clearance_status = "Pending";
		} else {
			$clearance_status = "";
		}

		$financial_year_id = $_SESSION['financial_year_id'];

		begin_t();

		$sq_max = mysqli_fetch_assoc(mysqlQuery("select max(payment_id) as max from visa_payment_master"));
		$payment_id = $sq_max['max'] + 1;

		$sq_payment = mysqlQuery("insert into visa_payment_master (payment_id, visa_id, branch_admin_id, financial_year_id, payment_date, payment_amount, payment_mode, bank_name, transaction_id, bank_id, clearance_status,credit_charges,credit_card_details) values ('$payment_id', '$visa_id', '$branch_admin_id', '$financial_year_id', '$payment_date', '$payment_amount', '$payment_mode', '$bank_name', '$transaction_id', '$bank_id', '$clearance_status','$credit_charges','$credit_card_details') ");
		if (!$sq_payment) {
			rollback_t();
			echo "error--Sorry, Payment not saved!";
			exit;
		} else {

			//Finance save
			$this->finance_save($payment_id, $branch_admin_id);

			//Bank and Cash Book Save
			$this->bank_cash_book_save($payment_id, $branch_admin_id);

			if ($GLOBALS['flag']) {
				commit_t();

				//Payment email notification
				$this->payment_email_notification_send($visa_id, $payment_amount, $payment_mode, $payment_date);

				//Payment sms notification
				if ($payment_amount != 0) {
					$this->payment_sms_notification_send($visa_id, $payment_amount, $payment_mode, $credit_charges);
				}

				echo "Visa Payment has been successfully saved.";
				exit;
			} else {
				rollback_t();
				exit;
			}
		}
	}

	public function finance_save($payment_id, $branch_admin_id)
	{
		global $transaction_master;
		$row_spec = 'sales';
		$visa_id = $_POST['visa_id'];
		$payment_date = $_POST['payment_date'];
		$payment_amount1 = $_POST['payment_amount'];
		$payment_mode = $_POST['payment_mode'];
		$bank_name = $_POST['bank_name'];
		$transaction_id1 = $_POST['transaction_id'];
		$bank_id1 = $_POST['bank_id'];
		$credit_charges = $_POST['credit_charges'];
		$credit_card_details = $_POST['credit_card_details'];

		$payment_date = date('Y-m-d', strtotime($payment_date));
		$year1 = explode("-", $payment_date);
		$yr1 = $year1[0];
		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select * from visa_master where visa_id='$visa_id'"));
		$customer_id = $sq_visa_info['customer_id'];

		//Getting cash/Bank Ledger
		if ($payment_mode == 'Cash') {
			$pay_gl = 20;
			$type = 'CASH RECEIPT';
		} else {
			$sq_bank = mysqli_fetch_assoc(mysqlQuery("select * from ledger_master where customer_id='$bank_id1' and user_type='bank'"));
			$pay_gl = $sq_bank['ledger_id'];
			$type = 'BANK RECEIPT';
		}

		//Getting customer Ledger
		$sq_cust = mysqli_fetch_assoc(mysqlQuery("select * from ledger_master where customer_id='$customer_id' and user_type='customer'"));
		$cust_gl = $sq_cust['ledger_id'];


		$payment_amount1 = floatval($payment_amount1) + floatval($credit_charges);
		if ($payment_mode != 'Credit Note') {
			//////////Payment Amount///////////
			if ($payment_mode == 'Credit Card') {

				//////Customer Credit charges///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $credit_charges;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $credit_charges, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id1, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = $cust_gl;
				$payment_side = "Debit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);

				//////Credit charges ledger///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $credit_charges;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $credit_charges, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id1, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = 224;
				$payment_side = "Credit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);

				//////Get Credit card company Ledger///////
				$credit_card_details = explode('-', $credit_card_details);
				$entry_id = $credit_card_details[0];
				$sq_cust1 = mysqli_fetch_assoc(mysqlQuery("select * from ledger_master where customer_id='$entry_id' and user_type='credit company'"));
				$company_gl = $sq_cust1['ledger_id'];
				//////Get Credit card company Charges///////
				$sq_credit_charges = mysqli_fetch_assoc(mysqlQuery("select * from credit_card_company where entry_id='$entry_id'"));
				//////company's credit card charges
				$company_card_charges = ($sq_credit_charges['charges_in'] == 'Flat') ? $sq_credit_charges['credit_card_charges'] : ($payment_amount1 * ($sq_credit_charges['credit_card_charges'] / 100));
				//////company's tax on credit card charges
				$tax_charges = ($sq_credit_charges['tax_charges_in'] == 'Flat') ? $sq_credit_charges['tax_on_credit_card_charges'] : ($company_card_charges * ($sq_credit_charges['tax_on_credit_card_charges'] / 100));
				$finance_charges = floatval($company_card_charges) + floatval($tax_charges);
				$finance_charges = number_format($finance_charges, 2);
				$credit_company_amount = floatval($payment_amount1) - floatval($finance_charges);

				//////Finance charges ledger///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $finance_charges;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $finance_charges, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id1, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = 231;
				$payment_side = "Debit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
				//////Credit company amount///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $credit_company_amount;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $credit_company_amount, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id1, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = $company_gl;
				$payment_side = "Debit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
			} else {

				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $payment_amount1;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $payment_amount1, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id1, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = $pay_gl;
				$payment_side = "Debit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == 'Credit Card') ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
			}

			////////Customer Amount//////
			$module_name = "Visa Booking";
			$module_entry_id = $visa_id;
			$transaction_id = "";
			$payment_amount = $payment_amount1;
			$payment_date = $payment_date;
			$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $payment_amount1, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id1, $transaction_id1);
			$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
			$gl_id = $cust_gl;
			$payment_side = "Credit";
			$clearance_status = "";
			$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
		}
	}

	public function bank_cash_book_save($payment_id, $branch_admin_id)
	{
		global $bank_cash_book_master;

		$visa_id = $_POST['visa_id'];
		$payment_date = $_POST['payment_date'];
		$payment_amount = $_POST['payment_amount'] ?: 0;
		$payment_mode = $_POST['payment_mode'];
		$bank_name = $_POST['bank_name'];
		$transaction_id = $_POST['transaction_id'];
		$bank_id = $_POST['bank_id'];
		$credit_charges = $_POST['credit_charges'] ?: 0;
		$credit_card_details = $_POST['credit_card_details'];
		$payment_date = date('Y-m-d', strtotime($payment_date));
		$year1 = explode("-", $payment_date);
		$yr1 = $year1[0];

		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select customer_id from visa_master where visa_id='$visa_id'"));
		$booking_date = $sq_visa_info['created_at'];
		$year2 = explode("-", $booking_date);
		$yr2 = $year1[0];

		if ($payment_mode == 'Credit Card') {

			$payment_amount = floatval($payment_amount) + floatval($credit_charges);
			$credit_card_details = explode('-', $credit_card_details);
			$entry_id = $credit_card_details[0];
			$sq_credit_charges = mysqli_fetch_assoc(mysqlQuery("select bank_id from credit_card_company where entry_id ='$entry_id'"));
			$bank_id = $sq_credit_charges['bank_id'];
		}
		$module_name = "Visa Booking";
		$module_entry_id = $payment_id;
		$payment_date = $payment_date;
		$payment_amount = $payment_amount;
		$payment_mode = $payment_mode;
		$bank_name = $bank_name;
		$transaction_id = $transaction_id;
		$bank_id = $bank_id;
		$particular = get_sales_paid_particular(get_visa_booking_payment_id($payment_id, $yr1), $payment_date, $payment_amount, $sq_visa_info['customer_id'], $payment_mode, get_visa_booking_id($visa_id, $yr2), $bank_id, $transaction_id);
		$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
		$payment_side = "Debit";
		$payment_type = ($payment_mode == "Cash") ? "Cash" : "Bank";

		$bank_cash_book_master->bank_cash_book_master_save($module_name, $module_entry_id, $payment_date, $payment_amount, $payment_mode, $bank_name, $transaction_id, $bank_id, $particular, $clearance_status, $payment_side, $payment_type, $branch_admin_id);
	}

	public function visa_payment_master_update()
	{
		$payment_id = $_POST['payment_id'];
		$visa_id = $_POST['visa_id'];
		$payment_date = $_POST['payment_date'];
		$payment_amount = $_POST['payment_amount'];
		$payment_mode = $_POST['payment_mode'];
		$bank_name = $_POST['bank_name'];
		$transaction_id = $_POST['transaction_id'];
		$bank_id = $_POST['bank_id'];
		$payment_old_value = $_POST['payment_old_value'];
		$payment_old_mode =  $_POST['payment_old_mode'];
		$credit_charges = $_POST['credit_charges'];
		$credit_card_details = $_POST['credit_card_details'];
		$credit_charges_old = $_POST['credit_charges_old'];

		$financial_year_id = $_SESSION['financial_year_id'];

		$payment_date = date('Y-m-d', strtotime($payment_date));


		$sq_payment_info = mysqli_fetch_assoc(mysqlQuery("select * from visa_payment_master where payment_id='$payment_id'"));

		$clearance_status = ($sq_payment_info['payment_mode'] == 'Cash' && $payment_mode != "Cash") ? "Pending" : $sq_payment_info['clearance_status'];
		if ($payment_mode == "Cash") {
			$clearance_status = "";
		}

		begin_t();
		$sq_payment = mysqlQuery("update visa_payment_master set visa_id='$visa_id', financial_year_id='$financial_year_id', payment_date='$payment_date', payment_amount='$payment_amount', payment_mode='$payment_mode', bank_name='$bank_name', transaction_id='$transaction_id', bank_id='$bank_id', clearance_status='$clearance_status',credit_charges='$credit_charges' where payment_id='$payment_id' ");
		if (!$sq_payment) {
			rollback_t();
			echo "error--Sorry, Payment not update!";
			exit;
		} else {

			//Finance update
			$this->finance_update($sq_payment_info, $clearance_status);

			//Bank/Cashbook update
			$this->bank_cash_book_update($clearance_status);

			if ($GLOBALS['flag']) {
				commit_t();
				//Payment email notification
				$this->payment_update_email_notification_send($payment_id);
				echo "Visa Payment has been successfully updated.";
				exit;
			} else {
				rollback_t();
				exit;
			}
		}
	}

	public function finance_update($sq_payment_info, $clearance_status1)
	{
		$row_spec = 'sales';
		$payment_id = $_POST['payment_id'];
		$visa_id = $_POST['visa_id'];
		$payment_date = $_POST['payment_date'];
		$payment_amount1 = $_POST['payment_amount'];
		$payment_mode = $_POST['payment_mode'];
		$bank_name = $_POST['bank_name'];
		$transaction_id1 = $_POST['transaction_id'];
		$bank_id = $_POST['bank_id'];
		$payment_old_value = $_POST['payment_old_value'];
		$payment_old_mode =  $_POST['payment_old_mode'];
		$credit_charges = $_POST['credit_charges'];
		$credit_card_details = $_POST['credit_card_details'];
		$credit_charges_old = $_POST['credit_charges_old'];
		$branch_admin_id = $_POST['branch_admin_id'];

		$payment_date = date('Y-m-d', strtotime($payment_date));
		$year1 = explode("-", $payment_date);
		$yr1 = $year1[0];

		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select customer_id from visa_master where visa_id='$visa_id'"));
		$customer_id = $sq_visa_info['customer_id'];
		global $transaction_master;

		//Getting cash/Bank Ledger
		if ($payment_mode == 'Cash') {
			$pay_gl = 20;
			$type = 'CASH RECEIPT';
		} else {
			$sq_bank = mysqli_fetch_assoc(mysqlQuery("select * from ledger_master where customer_id='$bank_id' and user_type='bank'"));
			$pay_gl = $sq_bank['ledger_id'];
			$type = 'BANK RECEIPT';
		}

		//Getting customer Ledger
		$sq_cust = mysqli_fetch_assoc(mysqlQuery("select * from ledger_master where customer_id='$customer_id' and user_type='customer'"));
		$cust_gl = $sq_cust['ledger_id'];


		if ($payment_amount1 != $payment_old_value) {
			//////////Payment Amount///////////
			if ($payment_mode == 'Credit Card') {

				$payment_old_value = floatval($payment_old_value) + floatval($credit_charges_old);
				//////Customer Credit charges///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $credit_charges_old;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $credit_charges_old, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id, $transaction_id1, $bank_id, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = $cust_gl;
				$payment_side = "Credit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);

				//////Credit charges ledger///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $credit_charges_old;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $credit_charges_old, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id, $transaction_id1, $bank_id, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = 224;
				$payment_side = "Debit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);

				//////Get Credit card company Ledger///////
				$credit_card_details = explode('-', $credit_card_details);
				$entry_id = $credit_card_details[0];
				$sq_cust1 = mysqli_fetch_assoc(mysqlQuery("select * from ledger_master where customer_id='$entry_id' and user_type='credit company'"));
				$company_gl = $sq_cust1['ledger_id'];
				//////Get Credit card company Charges///////
				$sq_credit_charges = mysqli_fetch_assoc(mysqlQuery("select * from credit_card_company where entry_id='$entry_id'"));
				//////company's credit card charges
				$company_card_charges = ($sq_credit_charges['charges_in'] == 'Flat') ? $sq_credit_charges['credit_card_charges'] : ($payment_old_value * ($sq_credit_charges['credit_card_charges'] / 100));
				//////company's tax on credit card charges
				$tax_charges = ($sq_credit_charges['tax_charges_in'] == 'Flat') ? $sq_credit_charges['tax_on_credit_card_charges'] : ($company_card_charges * ($sq_credit_charges['tax_on_credit_card_charges'] / 100));
				$finance_charges = floatval($company_card_charges) + floatval($tax_charges);
				$finance_charges = number_format($finance_charges, 2);
				$credit_company_amount = floatval($payment_old_value) - floatval($finance_charges);

				//////Finance charges ledger///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $finance_charges;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $finance_charges, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id, $transaction_id1, $bank_id, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = 231;
				$payment_side = "Credit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);

				//////Credit company amount///////
				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $credit_company_amount;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $credit_company_amount, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id, $transaction_id1, $bank_id, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = $company_gl;
				$payment_side = "Credit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == "Credit Card") ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
			} else {

				$module_name = "Visa Booking";
				$module_entry_id = $visa_id;
				$transaction_id = $transaction_id1;
				$payment_amount = $payment_old_value;
				$payment_date = $payment_date;
				$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $payment_old_value, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id, $transaction_id1, $bank_id, $transaction_id1);
				$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
				$gl_id = $pay_gl;
				$payment_side = "Credit";
				$clearance_status = ($payment_mode == "Cheque" || $payment_mode == 'Credit Card') ? "Pending" : "";
				$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
			}

			$module_name = "Visa Booking";
			$module_entry_id = $visa_id;
			$transaction_id = $transaction_id1;
			$payment_amount = $payment_old_value;
			$payment_date = $payment_date;
			$payment_particular = get_sales_paid_particular(get_visa_booking_payment_id($visa_id, $yr1), $payment_date, $payment_old_value, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr1), $bank_id, $transaction_id1, $bank_id, $transaction_id1);
			$ledger_particular = get_ledger_particular('By', 'Cash/Bank');
			$gl_id = $cust_gl;
			$payment_side = "Debit";
			$clearance_status = "";
			$transaction_master->transaction_save($module_name, $module_entry_id, $transaction_id, $payment_amount, $payment_date, $payment_particular, $gl_id, '', $payment_side, $clearance_status, $row_spec, $branch_admin_id, $ledger_particular, $type);
		}
	}

	public function bank_cash_book_update($clearance_status)
	{
		global $bank_cash_book_master;

		$payment_id = $_POST['payment_id'];
		$visa_id = $_POST['visa_id'];
		$payment_date = $_POST['payment_date'];
		$payment_amount = $_POST['payment_amount'];
		$payment_mode = $_POST['payment_mode'];
		$bank_name = $_POST['bank_name'];
		$transaction_id = $_POST['transaction_id'];
		$bank_id = $_POST['bank_id'];
		$credit_charges = $_POST['credit_charges'];
		$credit_card_details = $_POST['credit_card_details'];

		$payment_date = date('Y-m-d', strtotime($payment_date));
		$year1 = explode("-", $payment_date);
		$yr1 = $year1[0];

		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select created_at,customer_id from visa_master where visa_id='$visa_id'"));
		$customer_id = $sq_visa_info['customer_id'];
		$booking_date = date('Y-m-d', strtotime($sq_visa_info['created_at']));
		$year2 = explode("-", $booking_date);
		$yr2 = $year2[0];

		if ($payment_mode == 'Credit Card') {

			$payment_amount = floatval($payment_amount) + floatval($credit_charges);
			$credit_card_details = explode('-', $credit_card_details);
			$entry_id = $credit_card_details[0];
			$sq_credit_charges = mysqli_fetch_assoc(mysqlQuery("select bank_id from credit_card_company where entry_id ='$entry_id'"));
			$bank_id = $sq_credit_charges['bank_id'];
		}
		$module_name = "Visa Booking";
		$module_entry_id = $payment_id;
		$payment_date = $payment_date;
		$payment_amount = $payment_amount;
		$payment_mode = $payment_mode;
		$bank_name = $bank_name;
		$transaction_id = $transaction_id;
		$bank_id = $bank_id;
		$particular = get_sales_paid_particular(get_visa_booking_payment_id($payment_id, $yr1), $payment_date, $payment_amount, $customer_id, $payment_mode, get_visa_booking_id($visa_id, $yr2), $bank_id, $transaction_id);
		$clearance_status = $clearance_status;
		$payment_side = "Debit";
		$payment_type = ($payment_mode == "Cash") ? "Cash" : "Bank";

		$bank_cash_book_master->bank_cash_book_master_update($module_name, $module_entry_id, $payment_date, $payment_amount, $payment_mode, $bank_name, $transaction_id, $bank_id, $particular, $clearance_status, $payment_side, $payment_type);
	}

	//////////////////////////////////**Payment email notification send start**/////////////////////////////////////
	public function payment_email_notification_send($visa_id, $payment_amount = 0, $payment_mode, $payment_date)
	{
		global $encrypt_decrypt, $secret_key;
		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select * from visa_master where visa_id='$visa_id'"));

		$date = $sq_visa_info['created_at'];
		$yr = explode("-", $date);
		$year = $yr[0];
		$sq_customer_info = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$sq_visa_info[customer_id]'"));
		$email_id = $encrypt_decrypt->fnDecrypt($sq_customer_info['email_id'], $secret_key);
		$customer_name = ($sq_customer_info['type'] == 'Corporate' || $sq_customer_info['type'] == 'B2B') ? $sq_customer_info['company_name'] : $sq_customer_info['first_name'].' '.$sq_customer_info['last_name'];

		$due_date = ($sq_visa_info['due_date'] == '1970-01-01') ? '' : $sq_visa_info['due_date'];
		$sq_total_amount = mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum,sum(`credit_charges`) as sumc from visa_payment_master where visa_id='$visa_id' and clearance_status!='Cancelled'"));

		$credit_card_amount = $sq_total_amount['sumc'];
		$total_amount = floatval($sq_visa_info['visa_total_cost']) + floatval($credit_card_amount);
		$total_pay_amt = floatval($sq_total_amount['sum']) + floatval($credit_card_amount);
		$outstanding =  floatval($total_amount) - floatval($total_pay_amt);

		$subject = 'Payment Acknowledgement (Booking ID : ' . get_visa_booking_id($visa_id, $year) . ' )';
		global $model;
		$model->generic_payment_mail('47', $payment_amount, $payment_mode, $total_amount, $total_pay_amt, $payment_date, $due_date, $email_id, $subject, $customer_name,$sq_visa_info['currency_code']);
	}
	//////////////////////////////////**Payment email notification send end**/////////////////////////////////////


	//////////////////////////////////**Payment update email notification send start**/////////////////////////////////////
	public function payment_update_email_notification_send($payment_id)
	{
		global $encrypt_decrypt, $secret_key;
		$sq_payment_info = mysqli_fetch_assoc(mysqlQuery("select * from visa_payment_master where payment_id='$payment_id' and clearance_status!='Cancelled'"));
		$visa_id = $sq_payment_info['visa_id'];
		$payment_amount = $sq_payment_info['payment_amount'];
		$payment_mode = $sq_payment_info['payment_mode'];
		$payment_date = $sq_payment_info['payment_date'];

		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select * from visa_master where visa_id='$visa_id'"));
		$date = $sq_visa_info['created_at'];
		$yr = explode("-", $date);
		$year = $yr[0];
		$sq_total_amount = mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum,sum(`credit_charges`) as sumc from visa_payment_master where visa_id='$visa_id' and clearance_status!='Cancelled'"));
		$paid_amount = $sq_total_amount['sum'] + $sq_total_amount['sumc'];
		$due_date = ($sq_visa_info['due_date'] == '1970-01-01') ? '' : $sq_visa_info['due_date'];
		
		$total_amount = $sq_visa_info['visa_total_cost'] + $sq_total_amount['sumc'];

		$sq_customer_info = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$sq_visa_info[customer_id]'"));
		$email_id = $encrypt_decrypt->fnDecrypt($sq_customer_info['email_id'], $secret_key);
		$customer_name = ($sq_customer_info['type'] == 'Corporate' || $sq_customer_info['type'] == 'B2B') ? $sq_customer_info['company_name'] : $sq_customer_info['first_name'].' '.$sq_customer_info['last_name'];
		$subject = 'Visa Booking Payment Correction(Booking ID : ' . get_visa_booking_id($visa_id, $year) . ' )';
		$payment_id = get_visa_booking_payment_id($payment_id, $year);

		global $model;
		$model->generic_payment_mail('57', $payment_amount, $payment_mode, $total_amount, $paid_amount, $payment_date, $due_date, $email_id, $subject, $customer_name,$sq_visa_info['currency_code']);
	}
	//////////////////////////////////**Payment update email notification send end**/////////////////////////////////////

	//////////////////////////////////**Payment sms notification send start**/////////////////////////////////////
	public function payment_sms_notification_send($visa_id, $payment_amount, $payment_mode, $credit_charges)
	{
		global $encrypt_decrypt, $secret_key, $currency;

		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select customer_id from visa_master where visa_id='$visa_id'"));
		$customer_id = $sq_visa_info['customer_id'];

		$sq_customer_info = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$customer_id'"));
		$mobile_no = $encrypt_decrypt->fnDecrypt($sq_customer_info['contact_no'], $secret_key);
		$sq_currency = mysqli_fetch_assoc(mysqlQuery("select * from currency_name_master where id='$currency'"));
		$currency_code = $sq_currency['currency_code'];

		$total_paid_amt = floatval($payment_amount) + floatval($credit_charges);
		$message = "Dear " . $sq_customer_info['first_name'] . " " . $sq_customer_info['last_name'] . " Acknowledge your payment of " . $total_paid_amt . " " . $currency_code . ", which we received for Visa services installment.";
		global $model;
		$model->send_message($mobile_no, $message);
	}
	//////////////////////////////////**Payment sms notification send end**/////////////////////////////////////
	public function whatsapp_send()
	{
		global $app_contact_no, $session_emp_id, $encrypt_decrypt, $secret_key,$currency;

		$sq_visa_info = mysqli_fetch_assoc(mysqlQuery("select * from visa_master where visa_id=" . $_POST['booking_id']));


		$sq_pay = mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum,sum(`credit_charges`) as sumc from visa_payment_master where clearance_status!='Cancelled' and visa_id=" . $_POST['booking_id']));
		$credit_card_amount = $sq_pay['sumc'];
		$total_amount = floatval($sq_visa_info['visa_total_cost']) + floatval($credit_card_amount);
		$total_pay_amt = floatval($sq_pay['sum']) + floatval($credit_card_amount);
		$outstanding =  floatval($total_amount) - floatval($total_pay_amt);

		$sq_emp_info = mysqli_fetch_assoc(mysqlQuery("select * from emp_master where emp_id= '$session_emp_id'"));
		if ($session_emp_id == 0) {
			$contact = $app_contact_no;
		} else {
			$contact = $sq_emp_info['mobile_no'];
		}
		$sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id=" . $sq_visa_info['customer_id']));
		$contact_no = $encrypt_decrypt->fnDecrypt($sq_customer['contact_no'], $secret_key);

		$total_amount1 = currency_conversion($currency,$sq_visa_info['currency_code'],$total_amount);
		$total_pay_amt1 = currency_conversion($currency,$sq_visa_info['currency_code'],$total_pay_amt);
		$outstanding1 = currency_conversion($currency,$sq_visa_info['currency_code'],$outstanding);

		$whatsapp_msg = rawurlencode('Hello Dear ' . $sq_customer['first_name'] . ',
Hope you are doing great. This is to inform you that we have received your payment. We look forward to provide you a great experience.
*Total Amount* : ' . $total_amount1 . '
*Paid Amount* : ' . $total_pay_amt1 . '
*Balance Amount* : ' . $outstanding1 . '

Please do not hesitate to call us on ' . $contact . ' if you have any concern. 
Thank you. ');
		$link = 'https://web.whatsapp.com/send?phone=' . $contact_no . '&text=' . $whatsapp_msg;
		echo $link;
	}
}
