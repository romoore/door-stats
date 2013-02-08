<?php

date_default_timezone_set('America/New_York');

/*
 * Determines in what "class" a date belongs, based on the past offset
 * from the "$until" value.
 *
 * $dateAsString - some date, represented as a String
 * $until - a String containing a valid date according to the input to 
 *   strtotime()
 */
function getDateClass($dateAsString, $until) {

	$today=1000 * strtotime($until);
	$yearAgo=1000 * strtotime("$until - 365 day" );
	$weekAgo = 1000 * strtotime( "$until - 7 day" );
	$monthAgo = 1000 * strtotime( "$until - 4 week" );
	$quarterAgo = 1000 * strtotime( "$until - 3 month" );
	
	$date = new DateTime($dateAsString);
	$date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
	$asDate = $date->getTimestamp()*1000;

	if($asDate < $yearAgo) {
		return "older";
	}
	if($asDate < $quarterAgo) {
		return "1year";
	}
	if($asDate < $monthAgo) {
		return "3month";
	}
	if($asDate < $weekAgo) {
		return "4week";
	}
	if($asDate > $today) {
		return "current";
	}
	return "week";
}

function retrieveFromOwl($owlUrl, $roomRegEx, $fromDate, $toDate){
	$rawJson = file_get_contents( "$owlUrl"."q=$roomRegEx&st=$fromDate&et=$toDate" );
	$response = json_decode( $rawJson, true );
	return $response;
}


function buildMainDataTable($owlJsonArray, $useLinks=false){

	$roomCount = array();
	$roomIds = array();

	//echo print_r($response);
	// Iterate over each identifier array
	foreach($owlJsonArray as $entry){
		$attributes = $entry["attributes"];
	//	echo print_r($entry);

		$roomName = "";
		$opened = array( "current" => 0, "week" => 0, "4week" => 0, "3month" => 0, "1year" => 0 , "older" => 0);
		$closed = array( "current" => 0, "week" => 0, "4week" => 0, "3month" => 0, "1year" => 0 , "older" => 0);

		$LATEST_DAY = strtotime('last saturday');

		foreach($attributes as $attr){
			if($attr['attributeName'] == "displayName"){
				$roomName = $attr["data"];
			}else if($attr['attributeName'] == "closed"){
				$dateKey = getDateClass($attr['creationDate'], $LATEST_DAY);
				if($attr["data"] == "true"){
					$closed["$dateKey"] = $closed["$dateKey"] + 1;
				}else {
					$opened["$dateKey"] = $opened["$dateKey"] + 1;
				}
			}
		}

		if($roomName != ""){
			$roomCountYear["$roomName"] = array(
					"current" => $opened["current"],
					"week" => $opened["week"],
					"4week" => $opened["4week"],
					"3month" => $opened["3month"],
					"1year" => $opened["1year"]
				);
			$roomIds[$roomName]=$entry["identifier"];
		}
	}

	//print_r($roomCountYear);

	$latestDayString = date("M j, Y", strtotime($LATEST_DAY));

	$returnString = 
								"{"
									."cols:["
										."{id:'room',label:'Room',type:'string'},"
										."{id:'week',label:'Current Week',type:'number'},"
										."{id:'week',label:'Week Ending $latestDayString',type:'number'},"
										."{id:'4week',label:'4 Weeks Ending $latestDayString', type:'number'},"
										."{id:'3month',label:'3 Months Ending $latestDayString', type:'number'},"
										."{id:'12month',label:'1 Year Ending $latestDayString',type:'number'}],"
									."rows:[";
	foreach($roomCountYear as $room => $count){
		if($useLinks) {
			$returnString .= "{c:[{v:'$room',f:'<a href=\"detail.php?r=$roomIds[$room]\">$room</a>'},";
		}
		else{
			$returnString .= "{c:[{v:'$room',f:'$room'},";
		}
		$returnString .=  "{v:".$count['current'].",f:'".$count['current']."'},"
										. "{v:".$count['week'].",f:'".$count['week']."'},"
										. "{v:".$count['4week'].",f:'".$count['4week']."'},"
										. "{v:".$count['3month'].",f:'".$count['3month']."'},"
										. "{v:".$count['1year'].",f:'".$count['1year']."'}]},";

	}
	$returnString = substr($returnString,0,-1) . "]}";
	return $returnString;
}

function buildRoomDataTable($owlJsonArray, $dateFormat="Y-m-d") {
	$countByDate = array();
	foreach($owlJsonArray as $entry){
		$attributes = $entry["attributes"];
	//	echo print_r($entry);

		$roomName = "";

		foreach($attributes as $attr){
			if($attr['attributeName'] == "displayName"){
				$roomName = $attr["data"];
			}else if($attr['attributeName'] == "closed"){
				if($attr["data"] == "false"){
					$date = new DateTime($attr['creationDate']);
					$date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
					$dateString = $date->format($dateFormat);
					if(!array_key_exists($dateString, $countByDate)){
						$countByDate[$dateString] = 0;
					}
					$countByDate[$dateString] += 1;
				}
			}
		}

	}

	$returnString = "[['Date','Times Open'],";
	if(empty($countByDate)){
		$returnString .= "['N/A',0]";
	} else {
		ksort($countByDate);
		foreach($countByDate as $date => $count){
			$returnString .= "['$date', $count],";
		}
	}

	$returnString .= ']';

	return $returnString;

}
?>
