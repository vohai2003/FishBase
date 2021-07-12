<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>MFOV</title>
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
<form action="" method="post">
<label>Tìm</label><input type="text"name="search" value=''/>
<form action="" method="post">
  <label for="Timkiem">trong:</label>
  <select name="Timkiem" id="cars">
    <option value="TenVN">Tên Việt</option>
    <option value="TenEnglish">Tên tiếng anh</option>
    <option value="TenKH">Tên khoa học</option>
  </select>
  <input type="submit" value="Submit">
<br><br>
<a href='timkiem.php?db=MFOV'>Tìm kiếm theo bộ - họ - giống - loài</a>
<table>
<tr>
<th>T&#234n Vi&#7879t</th>
<th>T&#234n khoa h&#7885c</th>
<th>T&#234n ti&#7871ng Anh</th>
</tr>
<?php
ini_set('display_startup_errors',1); 
ini_set('display_errors',1);
error_reporting(-1);
$conn1 = mysqli_connect("localhost", "root", "root","MFOV");

if ($conn1->connect_error) {
  die("Connection failed: ".$conn1->connect_error);
 }
mysqli_set_charset($conn1, 'UTF8');
$search = $_POST['search'];
$option = $_POST['Timkiem'];
$sql = "select * from loaica where ".$option." like '%".$search."%'";
$result1 = $conn1->query($sql);
if ($result1->num_rows > 0) {
  while ($row = $result1->fetch_assoc()) {
       echo "<tr><td><a href='details.php?db=MFOV&id=".$row["Loai_ID"]."'>". $row["TenVN"]. "</a></td><td>". $row["TenKH"]. "</td><td>". $row["TenEnglish"]. "</td></tr>";
       }
	   echo "</table>";
}
$conn1->close();
?>
</table>
</body>
</html>