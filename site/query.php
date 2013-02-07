<?php
// Enable error reporting
 ini_set('display_errors',1);
 ini_set('display_startup_errors',1);
 error_reporting(-1);


/*
 * Google Chart Tools Datasource Handler for Owl Platform
 *
 * Takes a Google Chart request and accesses an Owl Platform REST
 * interface to retrieve the requested chart data.
 */

$reqId = 1;
$roomId = "hill.room.270";
$roomName = "Hill 270";
$currentDate = microtime();

// These defaults are required for the data source to handle.
$tqx = "reqId:1;version:0.6;out:json;responseHandler:callback";

if(empty($_GET)) {
	// Use defaults (above)
}
else {
 $tqx = htmlspecialchars($_GET["tqx"]);
}

$reqParams = explode(";",$tqx);

foreach ($reqParams as $pair) {
	$keyVal = explode(":",$pair);
	if($keyVal[0] == "reqId"){
		$reqId = $keyVal[1];
	}
}


// Fake it to just test the chart

$isJsonP = false;
if(array_key_exists("HTTP_X_DATASOURCE_AUTH",$_SERVER)){
	$isJsonP = true;
}



$returnString = 
						"{reqId:$reqId,"
							."status:ok,"
							."table:{"
								."cols:["
									."{id:'room',label:'Room',type:'string'},"
									."{id:'col1',label:'Week of Feb 3 - Feb 9, 2013',type:'number'},"
									."{id:'col2',label:'4 Weeks Ending Feb 9, 2013', type:'number'}],"
								."rows:["
									."{c:[{v:'Hill 270',f:'Hill 270'},{v:1.0,f:'1'},{v:5.1,f:'5.1'}]},"
									."{c:[{v:'Hill 272',f:'Hill 272'},{v:3.0,f:'3'},{v:7.3,f:'7.3'}]},"
									."{c:[{v:'Hill 274',f:'Hill 274'},{v:2.0,f:'2'},{v:6.6,f:'6.6'}]}"
									."]"
								."}"
							."}";

header('Content-type: application/json');


echo $returnString;
?>
