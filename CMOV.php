<!DOCTYPE html>
<html>
<head>
<title>CMOV</title>
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
tr:nth-of-type(even) {background-color: #f0f0f0}
tr:hover {background-color: #c0c0c0}
</style>
</head>
<body>
<h1 style='text-align:center;'>CMOV:Tìm kiếm</h1>
<form action="" method="post" style='text-align:center;'>
<label style="font-size:200%;">Tìm</label><input style='position:relative; left:2px; width:300px;' type="text"name="search" value=''/>
<form action="" method="post">
  <label for="Timkiem">trong:</label>
  <select name="Timkiem" id="cars">
    <option value="TenVN">Tên Việt</option>
    <option value="TenEnglish">Tên tiếng anh</option>
    <option value="TenKH">Tên khoa học</option>
  </select>
  <input type="submit" value="Submit" style="width=100px">
<br><br>
<a href="timkiem.php?db=CMOV">Tìm kiếm theo bộ - họ - giống - loài</a>
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
$conn1 = mysqli_connect("localhost", "root", "root","CMOV");

if ($conn1->connect_error) {
  die("Connection failed: ".$conn1->connect_error);
 }
mysqli_set_charset($conn1, 'UTF8');
$search = mysqli_real_escape_string($conn1,$_POST['search']);
$option = mysqli_real_escape_string($conn1,$_POST['Timkiem']);
$sql = "select * from loaica where ".$option." like '%".$search."%'";
$result1 = $conn1->query($sql);
echo ("<br>");
if ($search != ''){
echo ("Có ".$result1->num_rows." kết quả");
if ($result1->num_rows > 0) {
  while ($row = $result1->fetch_assoc()) {
       echo "<tr><td><a href='details.php?db=CMOV&id=".$row["Loai_ID"]."'><div>". $row["TenVN"]. "</div></a></td><td>". $row["TenKH"]. "</td><td>". $row["TenEnglish"]. "</td></tr>";
       }
	   echo "</table>";
}
}
else {
	echo("<br>Ô tìm kiếm rỗng");
}
$conn1->close();
?>
</table>
</body>
</html>