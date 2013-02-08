<?php
// Enable error reporting
 ini_set('display_errors',1);
 ini_set('display_startup_errors',1);
 error_reporting(-1);


header('Content-type: application/json');

if(empty($_GET)) {
	$responseString = "{status:'error',"
			."errors:[{reason:'invalid_request',"
			."message:'Missing reqId in request.'}]}";
	echo $responseString;
	return;
}

require_once("utils.php");

$OWL_HOST="http://localhost";
$OWL_REST_PATH="/grailrest/";
$OWL_RANGE_PATH="range";

$LATEST_DAY = "last saturday";

$roomRegEx = ".*";

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

$rawJson = file_get_contents( "$OWL_HOST$OWL_REST_PATH$OWL_RANGE_PATH?q=$roomRegEx&st=$yearAgo&et=$today" );
$response = json_decode( $rawJson, true );



$roomCount = array();
$roomIds = array();

//echo print_r($response);
// Iterate over each identifier array
foreach($response as $entry){
	$attributes = $entry["attributes"];
//	echo print_r($entry);

	$roomName = "";
	$opened = array( "current" => 0, "week" => 0, "4week" => 0, "3month" => 0, "1year" => 0 , "older" => 0);
	$closed = array( "current" => 0, "week" => 0, "4week" => 0, "3month" => 0, "1year" => 0 , "older" => 0);

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
						"{reqId:$reqId,"
							."status:ok,"
							."table:{"
								."cols:["
									."{id:'room',label:'Room',type:'string'},"
									."{id:'week',label:'Current Week',type:'number'},"
									."{id:'week',label:'Week Ending $latestDayString',type:'number'},"
									."{id:'4week',label:'4 Weeks Ending $latestDayString', type:'number'},"
									."{id:'3month',label:'3 Months Ending $latestDayString', type:'number'},"
									."{id:'12month',label:'1 Year Ending $latestDayString',type:'number'}],"
								."rows:[";
foreach($roomCountYear as $room => $count){
	$returnString = $returnString . "{c:[{v:'$room',f:'$room'},"
																. "{v:".$count['current'].",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=c\">".$count['current']."</a>'},"
																. "{v:".$count['week'].",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=w\">".$count['week']."</a>'},"
																. "{v:".$count['4week'].",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=m\">".$count['4week']."</a>'},"
																. "{v:".$count['3month'].",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=q\">".$count['3month']."</a>'},"
																. "{v:".$count['1year'].",f:'<a href=\"detail.php?r=".$roomIds[$room]."&s=y\">".$count['1year']."</a>'}]},";
}
/*
									."{c:[{v:'Hill 270',f:'Hill 270'},{v:1.0,f:'1'},{v:5.1,f:'5.1'}]},"
									."{c:[{v:'Hill 272',f:'Hill 272'},{v:3.0,f:'3'},{v:7.3,f:'7.3'}]},"
									."{c:[{v:'Hill 274',f:'Hill 274'},{v:2.0,f:'2'},{v:6.6,f:'6.6'}]}"
 */
$returnString = substr($returnString,0,-1) . "]}}";

echo $returnString;
?>
