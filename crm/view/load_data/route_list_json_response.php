<?php
include_once('../../model/model.php');

$bank_arr = array();
$sq_banks = mysqlQuery("select route from car_rental_tariff_entries");
while($row_banks = mysqli_fetch_assoc($sq_banks)){
	array_push($bank_arr, $row_banks['route']);
}

echo json_encode($bank_arr);
?>