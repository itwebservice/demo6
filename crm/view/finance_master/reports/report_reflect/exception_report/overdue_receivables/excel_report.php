<?php

include "../../../../../../model/model.php";

/** Error reporting */

error_reporting(E_ALL);

ini_set('display_errors', TRUE);

ini_set('display_startup_errors', TRUE);

date_default_timezone_set('Europe/London');



if (PHP_SAPI == 'cli')

  die('This example should only be run from a Web Browser');



/** Include PHPExcel */

require_once '../../../../../../classes/PHPExcel-1.8/Classes/PHPExcel.php';



//This function generates the background color

function cellColor($cells,$color){

    global $objPHPExcel;



    $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(

        'type' => PHPExcel_Style_Fill::FILL_SOLID,

        'startcolor' => array(

             'rgb' => $color

        )

    ));

}



//This array sets the font atrributes

$header_style_Array = array(

    'font'  => array(

        'bold'  => true,

        'color' => array('rgb' => '000000'),

        'size'  => 12,

        'name'  => 'Verdana'

    ));

$table_header_style_Array = array(

    'font'  => array(

        'bold'  => false,

        'color' => array('rgb' => '000000'),

        'size'  => 11,

        'name'  => 'Verdana'

    ));

$content_style_Array = array(

    'font'  => array(

        'bold'  => false,

        'color' => array('rgb' => '000000'),

        'size'  => 9,

        'name'  => 'Verdana'

    ));



//This is border array

$borderArray = array(

          'borders' => array(

              'allborders' => array(

                  'style' => PHPExcel_Style_Border::BORDER_THIN

              )

          )

      );



// Create new PHPExcel object

$objPHPExcel = new PHPExcel();

// Set document properties

$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")

                             ->setLastModifiedBy("Maarten Balliauw")

                             ->setTitle("Office 2007 XLSX Test Document")

                             ->setSubject("Office 2007 XLSX Test Document")

                             ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")

                             ->setKeywords("office 2007 openxml php")

                             ->setCategory("Test result file");



//////////////////////////****************Content start**************////////////////////////////////



$customer_id = $_GET['party_name'];
$branch_admin_id = $_GET['branch_admin_id'];
$branch_status = $_GET['branch_status'];
$role = $_GET['role'];
if($customer_id != ''){
  $sq_cust = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$customer_id'"));
  $customer_name = ($sq_cust['type'] == 'Corporate') ? $sq_cust['company_name'] : $sq_cust['first_name'].' '.$sq_cust['last_name'];
}

// Add some data

$objPHPExcel->setActiveSheetIndex(0)

           ->setCellValue('B2', 'Report Name')

            ->setCellValue('C2', 'Overdue Receivables')

            ->setCellValue('B3', 'Customer Name')

            ->setCellValue('C3', $customer_name);


$objPHPExcel->getActiveSheet()->getStyle('B2:C2')->applyFromArray($header_style_Array);
$objPHPExcel->getActiveSheet()->getStyle('B2:C2')->applyFromArray($borderArray);    

$objPHPExcel->getActiveSheet()->getStyle('B3:C3')->applyFromArray($header_style_Array);
$objPHPExcel->getActiveSheet()->getStyle('B3:C3')->applyFromArray($borderArray);    

$count = 0;
$row_count = 5;   

$objPHPExcel->setActiveSheetIndex(0)

        ->setCellValue('B'.$row_count, "Sr.No")

        ->setCellValue('C'.$row_count, "Customer Name")
        ->setCellValue('D'.$row_count, "Booking Type")

        ->setCellValue('E'.$row_count, "Overdue Amount")
        ->setCellValue('F'.$row_count, "Overdue From");



$objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($header_style_Array);
$objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);    

$row_count++;
$total_footer_due = 0;
$till_date = date('Y-m-d');
$till_date1 = get_date_user($till_date);
$count = 1;
//FIT
$query = "select * from package_tour_booking_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();
  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from package_tour_booking_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0;
    $booking_amt=$row_package['net_total'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(amount) as sum from package_payment_master where booking_id='$row_package[booking_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est=mysqli_fetch_assoc(mysqlQuery("select * from package_refund_traveler_estimate where booking_id='$row_package[booking_id]'"));
		$cancel_est=$cancel_est['cancel_amount'];
		if($cancel_est != ''){	
			if($cancel_est <= $total_paid){
				$pending_amt = 0;
			}
			else{
				$pending_amt =  $cancel_est - $total_paid;
			}
		}
		else{
			$cancel_est = ($cancel_est == '') ? '0' : $cancel_est;
			$pending_amt=$booking_amt-$total_paid;
		}
    if($pending_amt>'0'){
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);
      }
    }
  }

  if($total_due>'0'){

        sort($due_date_arr);
        $total_footer_due += $total_due;
        $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Package Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);  
  $row_count++;
  }
}

//Visa
$query = "select * from visa_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from visa_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['visa_total_cost'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from visa_payment_master where visa_id='$row_package[visa_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }
    if($pending_amt>'0'){   
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
    ->setCellValue('B'.$row_count, $count++)
    ->setCellValue('C'.$row_count, $customer_name)
    ->setCellValue('D'.$row_count, "Visa Booking")
    ->setCellValue('E'.$row_count, number_format($total_due,2))
    ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

$objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
$objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
    
//Flight Ticket
$query = "select * from ticket_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from ticket_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['ticket_total_cost'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from ticket_payment_master where ticket_id='$row_package[ticket_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }

    if($pending_amt>'0'){   
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Flight Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray); 
  $row_count++;
  }
}
//Train
$query = "select * from train_ticket_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from train_ticket_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['net_total'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from train_ticket_payment_master where train_ticket_id='$row_package[train_ticket_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }

    if($pending_amt>'0'){   
      $due_date = ($row_package['payment_due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Train Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Hotel
$query = "select * from hotel_booking_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from hotel_booking_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['total_fee'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from hotel_booking_payment where booking_id='$row_package[booking_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }

    if($pending_amt>'0'){   
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Hotel Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Bus
$query = "select * from bus_booking_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from bus_booking_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['net_total'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from bus_booking_payment_master where booking_id='$row_package[booking_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }

    if($pending_amt>'0'){   
      $due_date = ($row_package['created_at']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Bus Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Car Rental
$query = "select * from car_rental_booking where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from car_rental_booking where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['total_fees'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from car_rental_payment where booking_id='$row_package[booking_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }


    if($pending_amt>'0'){   
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Car Rental Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}

//Forex
$query = "select * from forex_booking_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from forex_booking_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['net_total'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from forex_booking_payment_master where booking_id='$row_package[booking_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $pending_amt=$booking_amt-$total_paid;
    

    if($pending_amt>'0'){
      $due_date = ($row_package['created_at']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;   
    $objPHPExcel->setActiveSheetIndex(0)
    ->setCellValue('B'.$row_count, $count++)
    ->setCellValue('C'.$row_count, $customer_name)
    ->setCellValue('D'.$row_count, "Forex Booking")
    ->setCellValue('E'.$row_count, number_format($total_due,2))
    ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

$objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
$objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Group
$query = "select * from tourwise_traveler_details where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from tourwise_traveler_details where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_fee =0; $pending_amt=0; $total_paid = 0; $cancel_amount2 = 0; $cancel_amount = 0; $cancel_amount1 = 0; $total_outstanding = 0;
    $booking_fee=$row_package['net_total'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(amount) as sum from payment_master where tourwise_traveler_id='$row_package[id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    if($row_package['tour_group_status'] == 'Cancel'){
			//Group Tour cancel
			$cancel_tour_count2=mysqli_num_rows(mysqlQuery("SELECT * from refund_tour_estimate where tourwise_traveler_id='$row_package[id]'"));
			if($cancel_tour_count2 >= '1'){
				$cancel_tour=mysqli_fetch_assoc(mysqlQuery("SELECT * from refund_tour_estimate where tourwise_traveler_id='$row_package[id]'"));
				$cancel_amount2 = $cancel_tour['cancel_amount'];
			}
			else{ $cancel_amount2 = 0; }
		
			if($cancel_esti_count1 >= '1'){
				$cancel_amount = $cancel_amount1;
			}else{
				$cancel_amount = $cancel_amount2;
			}	
		}
		else{
			// Group booking cancel
			$cancel_esti_count1=mysqli_num_rows(mysqlQuery("SELECT * from refund_traveler_estimate where tourwise_traveler_id='$row_package[id]'"));
			if($cancel_esti_count1 >= '1'){
				$cancel_esti1=mysqli_fetch_assoc(mysqlQuery("SELECT * from refund_traveler_estimate where tourwise_traveler_id='$row_package[id]'"));
				$cancel_amount = $cancel_esti1['cancel_amount'];
			}
			else{ $cancel_amount = 0; }
		}
		$cancel_amount = ($cancel_amount == '') ? '0' :$cancel_amount;
		if($row_package['tour_group_status'] == 'Cancel'){
			if($cancel_amount > $total_paid){
				$pending_amt = $cancel_amount - $total_paid;
			}
			else{
				$pending_amt = 0;
			}
		}
		else{
			if($cancel_esti_count1 >= '1'){
				if($cancel_amount > $total_paid){
					$pending_amt = $cancel_amount - $total_paid;
				}
				else{
					$pending_amt = 0;
				}
			}
			else{
				$pending_amt = $booking_fee - $total_paid;
			}
		}
    if($pending_amt>'0'){   
      $due_date = ($row_package['balance_due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Group Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Passport
$query = "select * from passport_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from passport_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['passport_total_cost'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from passport_payment_master where passport_id='$row_package[passport_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }


    if($pending_amt>'0'){  
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due; 
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Passport Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Excursion
$query = "select * from excursion_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from excursion_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['exc_total_cost'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from exc_payment_master where exc_id='$row_package[exc_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }


    if($pending_amt>'0'){  
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due; 
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Activity Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
//Miscellaneous
$query = "select * from miscellaneous_master where 1 ";
if($customer_id!=''){
  $query .= " and customer_id='$customer_id'";
}
if($branch_status=='yes'){
  if($role=='Branch Admin'){
  $query .= " and branch_admin_id = '$branch_admin_id'";
  } 
  elseif($role!='Admin' && $role!='Branch Admin'){
    $query .= " and emp_id='$emp_id'";
    }
}   
$query .= ' group by customer_id';
$sq_booking = mysqlQuery($query);
while($row_booking = mysqli_fetch_assoc($sq_booking))
{ 
  $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_due = 0;
  $due_date_arr = array();

  $sq_customer = mysqli_fetch_assoc(mysqlQuery("select * from customer_master where customer_id='$row_booking[customer_id]'"));
  if($sq_customer['type'] == 'Corporate'||$sq_customer['type'] == 'B2B'){
    $customer_name = $sq_customer['company_name'];
  }
  else{
    $customer_name = $sq_customer['first_name'].' '.$sq_customer['last_name'];  
  }
  $sq_pacakge = mysqlQuery("select * from miscellaneous_master where customer_id='$row_booking[customer_id]' ");
  while($row_package = mysqli_fetch_assoc($sq_pacakge))
  {
    $booking_amt =0; $pending_amt=0; $total_paid = 0; $cancel_est = 0; $total_outstanding = 0;
    $booking_amt=$row_package['misc_total_cost'];
    $total_pay=mysqli_fetch_assoc(mysqlQuery("select sum(payment_amount) as sum from miscellaneous_payment_master where misc_id='$row_package[misc_id]' and clearance_status!='Pending' AND clearance_status!='Cancelled'"));
    $total_paid = $total_pay['sum'];
    $cancel_est = $row_package['cancel_amount'];

    //Consider sale cancel amount
    if($cancel_est != '0'){       
      if($cancel_est <= $total_paid){
        $pending_amt  = 0;
      }
      else{
        $pending_amt =  $cancel_est - $total_paid;
      }
    }
    else{
      $pending_amt=$booking_amt-$total_paid;
    }


    if($pending_amt>'0'){   
      $due_date = ($row_package['due_date']);
      if(strtotime($till_date1) > strtotime($due_date)) {
        $total_due += $pending_amt;
        array_push($due_date_arr, $due_date);

      }
    }
  }

  if($total_due>'0'){
    sort($due_date_arr);
        $total_footer_due += $total_due;
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count, $count++)
        ->setCellValue('C'.$row_count, $customer_name)
        ->setCellValue('D'.$row_count, "Miscellaneous Booking")
        ->setCellValue('E'.$row_count, number_format($total_due,2))
        ->setCellValue('F'.$row_count, get_date_user($due_date_arr[0]));

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($content_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);
  $row_count++;
  }
}
$objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('B'.$row_count,'')
        ->setCellValue('C'.$row_count, '')
        ->setCellValue('D'.$row_count, "Total Amount")
        ->setCellValue('E'.$row_count, number_format($total_footer_due,2))
        ->setCellValue('F'.$row_count, '');

  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($header_style_Array);
  $objPHPExcel->getActiveSheet()->getStyle('B'.$row_count.':F'.$row_count)->applyFromArray($borderArray);



//////////////////////////****************Content End**************////////////////////////////////
// Rename worksheet

$objPHPExcel->getActiveSheet()->setTitle('Simple');
for($col = 'A'; $col !== 'N'; $col++) {
    $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}
// Set active sheet index to the first sheet, so Excel opens this as the first sheet

$objPHPExcel->setActiveSheetIndex(0);





// Redirect output to a client’s web browser (Excel5)

header('Content-Type: application/vnd.ms-excel');

header('Content-Disposition: attachment;filename="Overdue Receivables Report('.date('d-m-Y H:i').').xls"');

header('Cache-Control: max-age=0');

// If you're serving to IE 9, then the following may be needed

header('Cache-Control: max-age=1');



// If you're serving to IE over SSL, then the following may be needed

header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

header ('Last-Modified: '.gmdate('D, d M Y H:i').' GMT'); // always modified

header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1

header ('Pragma: public'); // HTTP/1.0



$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

$objWriter->save('php://output');

exit;

