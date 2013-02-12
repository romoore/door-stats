<?php

/*
 * Door Usage App for Owl Platform
 * Copyright (C) 2013 Robert Moore and Rutgers University
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *  
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *  
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

header('Content-type: application/json');

if(empty($_GET)) {
	$responseString = "{status:'error',"
			."errors:[{reason:'invalid_request',"
			."message:'Missing reqId in request.'}]}";
	echo $responseString;
	return;
}

require_once("utils.php");
require_once("conn.php");

$OWL_RANGE_PATH="range";


$LATEST_DAY = "last sunday";
if(date('w',time()) == 0){
	$LATEST_DAY = "today";
}

$roomRegEx = "(hill|core|cbim).*";

if(array_key_exists("tqx",$_GET)){
	$tqx = htmlspecialchars($_GET["tqx"]);
}else {
	$responseString = "{status:'error',"
			."errors:[{reason:'invalid_request',"
			."message:'Missing tqx request.'}]}";
	echo $responseString;
	return;

}

if(array_key_exists("r",$_GET)) {
	$roomRegEx = htmlspecialchars($_GET["r"]);
}

$reqParams = explode(";",$tqx);
$reqId = "";

foreach ($reqParams as $pair) {
	$keyVal = explode(":",$pair);
	if($keyVal[0] == "reqId"){
		$reqId = $keyVal[1];
	}
}

if($reqId == ""){
	$responseString = "{status:'error',"
			."errors:[{reason:'invalid_request',"
			."message:'Missing reqId in request.'}]}";
	echo $responseString;
	return;
}

// Fake it to just test the chart

$isJsonP = false;
if(array_key_exists("HTTP_X_DATASOURCE_AUTH",$_SERVER)){
	$isJsonP = true;
}

$today=1000 * time();
$yearAgo=1000 * strtotime( date( "Y-m-d", time() ) . " - 365 day" );
$roomRegEx = urlencode($roomRegEx);
$rawJson = file_get_contents(OWL_URL.$OWL_RANGE_PATH."?q=$roomRegEx&st=$yearAgo&et=$today");
$response = json_decode( $rawJson, true );



$roomCountYear = array();
$roomIds = array();
$studentMap = getRoomToStudentsMap();

//echo print_r($response);
// Iterate over each identifier array
foreach($response as $entry){
	$attributes = $entry["attributes"];
//	echo print_r($entry);

	$roomName = "";
	$opened = array( CURRENT_WEEK => 0, WEEK => 0, MONTH => 0, QUARTER => 0, YEAR => 0 , OLDER => 0);
	$closed = array( CURRENT_WEEK => 0, WEEK => 0, MONTH => 0, QUARTER => 0, YEAR => 0 , OLDER => 0);

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
			  CURRENT_WEEK => $opened[CURRENT_WEEK],
				WEEK => $opened[WEEK],
				MONTH => $opened[MONTH],
				QUARTER => $opened[QUARTER],
				YEAR => $opened[YEAR]
			);
		$roomIds[$roomName]=$entry["identifier"];
	}
}

//print_r($roomCountYear);

$latestDayString = date("M j, Y", strtotime($LATEST_DAY.' - 1 day'));

$returnString = 
						"{reqId:$reqId,"
							."status:ok,"
							."table:{"
								."cols:["
									."{id:'room',label:'Room',type:'string'},"
									."{id:'week',label:'Current Week',type:'number'},"
									."{id:'week',label:'Week Ending $latestDayString',type:'number'},"
									."{id:'4week',label:'4 Weeks Ending $latestDayString', type:'number'},"
									."{id:'3month',label:'3 Months Ending $latestDayString', type:'number'},"
									."{id:'12month',label:'1 Year Ending $latestDayString',type:'number'},"
									."{id:'occupants',label:'Students',type: 'string'}],"
								."rows:[";
foreach($roomCountYear as $room => $count){
	$sumToYear = 0;
	$returnString = $returnString . "{c:[{v:'$room',f:'$room'},";
	$sumToYear = $count[CURRENT_WEEK];
	$returnString .= "{v:".$sumToYear
									.",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=c\">".$sumToYear."</a>'},";
	$sumToYear = $count[WEEK];
	$returnString .= "{v:".$sumToYear
									.",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=w\">".$sumToYear."</a>'},";
	$sumToYear += $count[MONTH];
	$returnString .= "{v:".$sumToYear
									.",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=m\">".$sumToYear."</a>'},";
	$sumToYear += $count[QUARTER];
	$returnString .= "{v:".$sumToYear
									.",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=q\">".$sumToYear."</a>'},";
	$sumToYear += $count[YEAR];
	$returnString .= "{v:".$sumToYear
									.",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=y\">".$sumToYear."</a>'},";
	$students = implode(',', $studentMap[$room]);
//	$studentsUL = implode('<br />', $studentMap[$room]);
	$studentsUL = "";
	foreach($studentMap[$room] as $student) {
		$studentsUL .= str_replace(" ","&nbsp;",$student)."<br />";
	}

 	$returnString .= "{v:'".$students."', f:'".$studentsUL."'}]},";
}
/*
									."{c:[{v:'Hill 270',f:'Hill 270'},{v:1.0,f:'1'},{v:5.1,f:'5.1'}]},"
									."{c:[{v:'Hill 272',f:'Hill 272'},{v:3.0,f:'3'},{v:7.3,f:'7.3'}]},"
									."{c:[{v:'Hill 274',f:'Hill 274'},{v:2.0,f:'2'},{v:6.6,f:'6.6'}]}"
 */
$returnString = substr($returnString,0,-1) . "]}}";

echo $returnString;
?>
