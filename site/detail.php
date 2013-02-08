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

	$end = 1000 * strtotime('last saturday');
	$start = 1000 * strtotime('one year ago');
	$format = "Y M";
	if($span == "c"){
		$format = "Y-m-d A";
		$start = 1000 * strtotime('last saturday');
		$end = 1000 * time();
	}
	if($span == "w"){
		$format = "Y-m-d A";
		$start = 1000 * strtotime('last saturday - 6 day');
	}else if($span == "m"){
		$start = 1000 * strtotime('last saturday - 4 week');
		$format = "Y-m-d";
	} else if($span == "q") {
		$start = 1000 * strtotime('last saturday - 3 month');
		$format = "Y M";
	}
	$owlData = retrieveFromOwl($OWL_URL."range?",$room, $start, $end);
	$dataTable = buildRoomDataTable($owlData,$format);
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
				var options = {'title':'Door Usage by Date',
											 'width':1160,
											 'height':600};
				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
				var dataTable = google.visualization.arrayToDataTable(<?php echo $dataTable; ?>)
				chart.draw(dataTable, options);
			}
	
		</script>
  </head>

  <body>
<!--Div that will hold the pie chart-->
    <div id="chart_div" style="width:1200; height:640"></div>
  </body>
</html>
