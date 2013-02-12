<?php


	// Kick 'em out if they aren't acutally looking for a specific room
	if(empty($_GET) || !array_key_exists("r",$_GET)){
		header("Location: chart.html");
		return;
	}
	$room = htmlspecialchars($_GET["r"]);

	$span = "y";

	if(array_key_exists("s",$_GET)){
		$span=htmlspecialchars($_GET["s"]);
	}


	require_once("utils.php");
	require_once("conn.php");

	// Default values (current week)
	
	// Starting on the most recent Sunday
	$startString = "last sunday";
	// Ending "right now"
	$endString = "this sunday";
	if(date("w",time()) == 0){
		$startString = "today";
		$endString = "next sunday";
	}
	$format = "Y-m-d H:00";

	// Previous week (sunday to saturday)
	if($span == "w"){
		$endString = $startString;
		$startString = $startString . ' - 7 day';
	}
	// 4 weeks
	else if($span == "m"){
		$endString = $startString;
		$startString = $startString . ' - 4 week';
		$format = "Y-m-d";
	} 
	// Quarter (3 months)
	else if($span == "q") {
		$endString = $startString;
		$startString = $startString . ' - 3 month';
		$format = "Y M";
	} 
	// Year
	else if($span == "y"){
		$endString = $startString;
		$startString = $startString . ' - 1 year';
		$format = "M Y";
	}
	$end = 1000 * strtotime($endString);
	$start = 1000 * strtotime($startString);
	$owlData = retrieveFromOwl(OWL_URL."range?",$room, $start, $end);
	$startDate = DateTime::createFromFormat("U",$start/1000);
	$startDate->setTimeZone(new DateTimeZone(date_default_timezone_get()));
	$endDate = DateTime::createFromFormat("U",$end/1000);
	$endDate->setTimeZone(new DateTimeZone(date_default_timezone_get()));
	$dataTable = buildRoomDataTable($owlData,$format,$startDate,$endDate);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Details for Room <?php echo $room; ?></title>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
    
      // Load the Visualization API and the piechart package.
      google.load('visualization', '1.0', {'packages':['corechart']});
      
      // Set a callback to run when the Google Visualization API is loaded.
      google.setOnLoadCallback(drawChart);


      // Callback that creates and populates a data table, 
      // instantiates the pie chart, passes in the data and
      // draws it.
      function drawChart(data) {


				// Set chart options
				var options = {title:'Door Usage by Date',
											 width:1160,
											 height:600,
											 pointSize:4,
<?php 
$startDate = DateTime::createFromFormat("U",$start/1000);
$endDate = DateTime::createFromFormat("U",$end/1000);
echo "hAxis: {viewWindow: {min:";
if($span == "w" || $span == "c" || $span == "m") {
	echo " new Date(".$startDate->format("Y").",".($startDate->format("m")-1).",".$startDate->format("d")."), max:";
  echo " new Date(".$endDate->format("Y").",".($endDate->format("m")-1).",".$endDate->format("d").")}},";
}else {
	echo " new Date(".$startDate->format("Y").",".($startDate->format("m")-1)."), max:";
  echo " new Date(".$endDate->format("Y").",".($endDate->format("m")-1).")}},";
}	

?>
				};
				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
				var dataTable = new google.visualization.DataTable(<?php echo $dataTable; ?>)
				chart.draw(dataTable, options);
			}
	
		</script>
  </head>

  <body>
<!--Div that will hold the pie chart-->
    <div id="chart_div" style="width:1200; height:640"></div>
  </body>
</html>
