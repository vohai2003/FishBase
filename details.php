<html>
<head>
<style type="text/css">
@font-face {
 font-family:Vni-Times;
 <!--src:url('../Font/VTIMESB.TTF') format('truetype'),
 url('../Font/VTIMESBI.TTF') format('truetype'),
 url('../Font/VTIMESI.TTF') format('truetype'),-->
 src:url('../Font/VTIMESN.TTF') format('truetype');
 font-weight:normal;
 font-style:normal;
}
.column {
  float: left;
  width: 50%;
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}
</style>
</head>
<body>
<h1 style="text-align: center;">Th&#244ng tin lo&#224i: </h1>
<?php
// Function that checks whether the data are the on-screen text.
// It works in the following way:
// an array arrfailAt stores the control words for the current state of the stack, which show that
// input data are something else than plain text.
// For example, there may be a description of font or color palette etc.
function rtf_isPlainText($s) {
    $arrfailAt = array("*", "fonttbl", "colortbl", "datastore", "themedata");
    for ($i = 0; $i < count($arrfailAt); $i++)
        if (!empty($s[$arrfailAt[$i]])) return false;
    return true;
}

function rtf2text($filename) {
    // Read the data from the input file.
    $text = file_get_contents($filename);
    if (!strlen($text))
        return "";

    // Create empty stack array.
    $document = "";
    $stack = array();
    $j = -1;
    // Read the data character-by- character…
    for ($i = 0, $len = strlen($text); $i < $len; $i++) {
        $c = $text[$i];

        // Depending on current character select the further actions.
        switch ($c) {
            // the most important key word backslash
            case "\\":
                // read next character
                $nc = $text[$i + 1];

                // If it is another backslash or nonbreaking space or hyphen,
                // then the character is plain text and add it to the output stream.
                if ($nc == '\\' && rtf_isPlainText($stack[$j])) $document .= '\\';
                elseif ($nc == '~' && rtf_isPlainText($stack[$j])) $document .= ' ';
                elseif ($nc == '_' && rtf_isPlainText($stack[$j])) $document .= '-';
                // If it is an asterisk mark, add it to the stack.
                elseif ($nc == '*') $stack[$j]["*"] = true;
                // If it is a single quote, read next two characters that are the hexadecimal notation
                // of a character we should add to the output stream.
                elseif ($nc == "'") {
                    $hex = substr($text, $i + 2, 2);
                    if (rtf_isPlainText($stack[$j]))
                        $document .= html_entity_decode("&#".hexdec($hex).";");
                    //Shift the pointer.
                    $i += 2;
                // Since, we’ve found the alphabetic character, the next characters are control word
                // and, possibly, some digit parameter.
                } elseif ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
                    $word = "";
                    $param = null;

                    // Start reading characters after the backslash.
                    for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) {
                        $nc = $text[$k];
                        // If the current character is a letter and there were no digits before it,
                        // then we’re still reading the control word. If there were digits, we should stop
                        // since we reach the end of the control word.
                        if ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
                            if (empty($param))
                                $word .= $nc;
                            else
                                break;
                        // If it is a digit, store the parameter.
                        } elseif ($nc >= '0' && $nc <= '9')
                            $param .= $nc;
                        // Since minus sign may occur only before a digit parameter, check whether
                        // $param is empty. Otherwise, we reach the end of the control word.
                        elseif ($nc == '-') {
                            if (empty($param))
                                $param .= $nc;
                            else
                                break;
                        } else
                            break;
                    }
                    // Shift the pointer on the number of read characters.
                    $i += $m - 1;

                    // Start analyzing what we’ve read. We are interested mostly in control words.
                    $toText = "";
                    switch (strtolower($word)) {
                        // If the control word is "u", then its parameter is the decimal notation of the
                        // Unicode character that should be added to the output stream.
                        // We need to check whether the stack contains \ucN control word. If it does,
                        // we should remove the N characters from the output stream.
                        case "u":
                            $toText .= html_entity_decode("&#x".dechex($param).";");
                            $ucDelta = @$stack[$j]["uc"];
                            if ($ucDelta > 0)
                                $i += $ucDelta;
                        break;
                        // Select line feeds, spaces and tabs.
                        case "par": case "page": case "column": case "line": case "lbr":
                            $toText .= "<br>";
                        break;
                        case "emspace": case "enspace": case "qmspace":
                            $toText .= " ";
                        break;
                        case "tab": $toText .= "\t"; break;
                        // Add current date and time instead of corresponding labels.
                        case "chdate": $toText .= date("m.d.Y"); break;
                        case "chdpl": $toText .= date("l, j F Y"); break;
                        case "chdpa": $toText .= date("D, j M Y"); break;
                        case "chtime": $toText .= date("H:i:s"); break;
                        // Replace some reserved characters to their html analogs.
                        case "emdash": $toText .= html_entity_decode("&mdash;"); break;
                        case "endash": $toText .= html_entity_decode("&ndash;"); break;
                        case "bullet": $toText .= html_entity_decode("&#149;"); break;
                        case "lquote": $toText .= html_entity_decode("&lsquo;"); break;
                        case "rquote": $toText .= html_entity_decode("&rsquo;"); break;
                        case "ldblquote": $toText .= html_entity_decode("&laquo;"); break;
                        case "rdblquote": $toText .= html_entity_decode("&raquo;"); break;
                        // Add all other to the control words stack. If a control word
                        // does not include parameters, set &param to true.
                        default:
                            $stack[$j][strtolower($word)] = empty($param) ? true : $param;
                        break;
                    }
                    // Add data to the output stream if required.
                    if (rtf_isPlainText($stack[$j]))
                        $document .= $toText;
                }

                $i++;
            break;
            // If we read the opening brace {, then new subgroup starts and we add
            // new array stack element and write the data from previous stack element to it.
            case "{":
                array_push($stack, $stack[$j++]);
            break;
            // If we read the closing brace }, then we reach the end of subgroup and should remove
            // the last stack element.
            case "}":
                array_pop($stack);
                $j--;
            break;
            // Skip “trash”.
            case '\0': case '\r': case '\f': case '\n': break;
            // Add other data to the output stream if required.
            default:
                if (rtf_isPlainText($stack[$j]))
                    $document .= $c;
            break;
        }
    }
	// $n = strpos('Minh Minh',$document);
	//$n + 8;
	//for ($j = $n; $j>=1; $j--) {
	//	$document[$j] = ' ';
	//}
	for ($j = 1; $j <=4; $j++){
	$arr = explode("\n", $document);
	array_shift($arr);
	$document = implode("\n", $arr);
	}
    // Return result.
    return $document;
}
?>
<?php
require './include/u-convert/autoload.php';
use Anhskohbo\UConvert\UConvert;
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
$loai_id = $_GET['id'];
$db = $_GET['db'];
$conn1 = mysqli_connect("localhost","root","root",$db);
if ($conn1->connect_error) {
  die("Connection failed: ".$conn1->connect_error);
 }
mysqli_set_charset($conn1, 'UTF8');
$temp1 = $conn1->query("select Giong_ID from loaica where Loai_ID like '".$loai_id."'")->fetch_assoc();
$giong_id = implode('',$temp1);
$temp2 = $conn1->query("select Ho_ID from giong where Giong_ID like '".$giong_id."'")->fetch_assoc();
$ho_id = implode('',$temp2);
$temp3 = $conn1->query("select Bo_ID from hoca where Ho_ID like '".$ho_id."'")->fetch_assoc();
$bo_id = implode('',$temp3);
$temp4 = $conn1->query("select TenKH from loaica where Loai_ID like '".$loai_id."'")->fetch_assoc();
$temp5 = $conn1->query("select TenKH from giong where Giong_ID like '".$giong_id."'")->fetch_assoc();
$temp6 = $conn1->query("select TenKH from boca where Bo_ID like '".$bo_id."'")->fetch_assoc();
$temp7 = $conn1->query("select TenKH from hoca where Ho_ID like '".$ho_id."'")->fetch_assoc();
$temp8 = $conn1->query("select TenVN from loaica where Loai_ID like '".$loai_id."'")->fetch_assoc();
$temp9 = $conn1->query("select Anh from loaica where Loai_ID like '".$loai_id."'")->fetch_assoc();
$anh = $db."/".implode('',$temp9);
$loai = implode('',$temp4);
$giong = implode('',$temp5);
$bo = implode('',$temp6);
$ho = implode('',$temp7);
$TenVN = implode('',$temp8);
$TenEng = implode('',$conn1->query("select TenEnglish from loaica where Loai_ID like '".$loai_id."'")->fetch_assoc());
$temp9 = $conn1->query("select Anh from loaica where Loai_ID like '".$loai_id."'")->fetch_assoc();
$anh = $db."/".implode('',$temp9);
$textfile = pathinfo($db.'/'.$anh, PATHINFO_FILENAME);
$textfile = strval((int)$textfile);
echo ("<div class='row'>");
echo ("<div class='column'>");
if ($db == "MFOV" or $db == "FFOV"){
$textvar = rtf2text("document/".$db."/".$textfile.".RTF");
$textvar = UConvert::toUnicode($textvar, UConvert::VNI);
echo $textvar;
} else {
	$textvar =  rtf2text("document/".$db."/".$textfile.".rtf");
	$textvar = UConvert::toUnicode($textvar, UConvert::VNI);
	echo $textvar;
}
echo ("</div><div class='column'>");
echo "<p>Ph&#226n lo&#7841i:</p>";
echo "<p>&#8226 B&#7897: ".$bo."</p>";
echo "<p>&#8226 H&#7885: ".$ho."</p>";
echo "<p>&#8226 Gi&#7889ng: ".$giong."</p>";
echo "<p>&#8226 Lo&#224i: <i>".$loai."</i></p>";
echo ("<img style=\"max-width:450px; max-height:300px; \" src=".$anh." /><br><br>");
echo ("</div>");

?>
</body>
</html>