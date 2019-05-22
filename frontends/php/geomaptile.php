<?php
$zoom = $_GET['z'];
$column = $_GET['x'];
$row = $_GET['y'];
$db = '/home/miks/Downloads/2017-07-03_new-york_new-york.mbtiles';
try {
	// Open the database
	$conn = new PDO("sqlite:$db");
	
	// Query the tiles view and echo out the returned image
	$result = $conn->query('select tile_data as t from tiles where zoom_level='.$zoom.' and tile_column='.$column.' and tile_row='.$row);
	$data = $result->fetchColumn();

	if (!isset($data) || $data === FALSE) {
		$png = imagecreatetruecolor(256, 256);
		imagesavealpha($png, true);
		$trans_colour = imagecolorallocatealpha($png, 0, 0, 0, 127);
		imagefill($png, 0, 0, $trans_colour);
		header('Content-type: image/png');
		imagepng($png);
		//header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
	} else {
		$result = $conn->query('select value from metadata where name="format"');
		$resultdata = $result->fetchColumn();
		$format = isset($resultdata) && $resultdata !== FALSE ? $resultdata : 'png';
		if( $format == 'jpg' )
			$format = 'jpeg';

		//header('Content-type: image/'.$format);
		header('Content-type: image/png');
		//file_put_contents('/tmp/test', $data);
		print $data;
	}
	exit;
	
	$sql = "SELECT tile_data FROM package_tiles WHERE zoom_level = $zoom AND tile_column = $column AND tile_row = $row";

	$q = $conn->prepare($sql);
	$q->execute();
	//$q->bindColumn(1, $tile_data);
	//$q->bindColumn(2, $tile_column);
	//$q->bindColumn(3, $tile_row);
	$q->bindColumn(1, $tile_data, PDO::PARAM_LOB);

	while ($q->fetch()) {
		if (0) {
			echo '<pre>';
			print_r([
				$q->columnCount(),
				$q->rowCount(),
				$sql,
				//$zoom_level,
				//$tile_column,
				//$tile_row,
				$tile_data
			]);
			echo '</pre>';
			var_dump($tile_data);
			exit;
		}

		header("Content-Type: image/png");
		echo $tile_data;
	}
	
}
catch(PDOException $e) {
  print 'Exception : '.$e->getMessage();
  exit;
}
