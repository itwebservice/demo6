<?php 
include_once('../../../model/model.php');

$group_id = $_POST['group_id'];
$hotel_info_arr = array();

$sq_group = mysqli_fetch_assoc(mysqlQuery("select * from tour_groups where group_id='$group_id'"));

$query = "select * from group_tour_hotel_entries where tour_id='$sq_group[tour_id]'";

$sq_hotel = mysqlQuery($query);

	while($row_hotel = mysqli_fetch_assoc($sq_hotel)){
        $city_names = mysqli_fetch_assoc(mysqlQuery("select city_name from city_master where city_id = ".$row_hotel['city_id']));
        $hotel_names = mysqli_fetch_assoc(mysqlQuery("select hotel_name from hotel_master where hotel_id = ".$row_hotel['hotel_id']));
        $hotel_type = $row_hotel['hotel_type'];
		$total_nights = $row_hotel['total_nights'];
		$arr = array(
			'city_names' => $city_names['city_name'],
			'hotel_names' => $hotel_names['hotel_name'],
			'hotel_type' => $row_hotel['hotel_type'],			
			'total_nights' => $row_hotel['total_nights'],
		);
	 array_push($hotel_info_arr, $arr);
	}


echo json_encode($hotel_info_arr);
?>