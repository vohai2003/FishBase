<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Tìm kiếm | Bộ - Họ - Giống - Loài</title>
        <meta charset="UTF-8" />
		<style>
table {
border-collapse: collapse;
width: 100%;
color: #588c7e;
font-family: monospace;
font-size: 25px;
text-align: left;
}
th {
background-color: #588c7e;
color: white;
}
tr:nth-child(even) {background-color: #f2f2f2}
</style>
    </head>
    <body>
	        <h1 style="text-align: center;"><strong>Tìm kiếm | Bộ - Họ - Giống - Loài</strong></h1>
	<table>


		<tr>
<th>T&#234n Vi&#7879t</th>
<th>T&#234n khoa h&#7885c</th>
</tr>
		<?php
		ini_set('display_startup_errors',1); 
		ini_set('display_errors',1);
		error_reporting(-1);
		$dbname = $_GET['db'];
		$conn = mysqli_connect('localhost','root','root',$dbname);
		mysqli_set_charset($conn, 'UTF8');
		if ($_GET['bo'] != null) {
			$result = mysqli_query($conn,'select * from hoca where Bo_ID like \''.$_GET['bo'].'\';');
			if ($result -> num_rows >0) {
					while ($row = $result -> fetch_assoc()){
						 echo "<tr><td><a href='timkiem.php?db=".$dbname."&ho=".$row["Ho_ID"]."'>". $row["TenVN"]. "</a></td><td>". $row["TenKH"]. "</td></tr>";
					}
					echo "</table>";
				}
			} else if ($_GET['ho'] != null) {
			$result = mysqli_query($conn,'select * from giong where Ho_ID like \''.$_GET['ho'].'\';');	
			if ($result -> num_rows >0) {
					while ($row = $result -> fetch_assoc()){
						 echo "<tr><td><a href='timkiem.php?db=".$dbname."&giong=".$row["Giong_ID"]."'>". $row["TenVN"]. "</a></td><td>". $row["TenKH"]. "</td></tr>";
					}
					echo "</table>";
				}
			} else if ($_GET['giong'] != null) {
			$result = mysqli_query($conn,'select * from loaica where Giong_ID like \''.$_GET['giong'].'\';');
			if ($result -> num_rows >0) {
					while ($row = $result -> fetch_assoc()){
						 echo "<tr><td><a href='details.php?db=".$dbname."&id=".$row["Loai_ID"]."'>". $row["TenVN"]. "</a></td><td>". $row["TenKH"]. "</td></tr>";
					}
					echo "</table>";
				}
			} else {
				$result = mysqli_query($conn,'select * from boca;');
				if ($result -> num_rows >0) {
					while ($row = $result -> fetch_assoc()){
						 echo "<tr><td><a href='timkiem.php?db=".$dbname."&bo=".$row["Bo_ID"]."'>". $row["TenVN"]. "</a></td><td>". $row["TenKH"]. "</td></tr>";
					}
					echo "</table>";
				}
			}
		?>
    </body>
</html>