<?php
include 'database.class.php';
include 'users.class.php';

// $db->createUser("1234", "4555", "emmydan", "Emmanuel");

// $db->setUserDetail("1234", "balance", "0.0034567");
// $db->getUserDetail("1234", "balance");

// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, "https://owoprofit.com/btcmining_getUserDetails.php?user_id=1234");
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// $user_details = curl_exec($ch);
// curl_close($ch);
// $user_details = (array) json_decode($user_details);
// print_r($user_details);

// $time = 40;
// function getMyTime($time)
// {
//     if ($time < 60) {
//         return round($time) . " seconds";
//     } else if ($time >= 60 and $time < 3600) {
//         return round($time / 60) . " minutes";
//     } else if ($time >= 3600 and $time < 86400) {
//         return round($time / 3600) . " hours";
//     }
// }
// echo getMyTime($time);

// echo time();

// $time = 1623663993;
// echo abs($time - time());

// $num = 0.3;
// echo round($num, 8);

// function calculateMinedBitcoin($time)
// {
//     $amount_mined = ($time * number_format(0.000005, 8)) / 60;

//     return number_format($amount_mined, 8);
// }

// echo calculateMinedBitcoin(60);

// echo number_format(0.00000000 + 0.00005689, 8);
// $user->createUser("1234", "5678", "emmydan", "Emmanuel");
// if ($user->userExists("1234")) {
//     echo "exists";
// } else {
//     echo "Does not exists";
// }

// $result = $db->setHandlerQuery("SELECT * FROM users WHERE user_id='1234';");
// print_r($result);

// echo $user->getUserDetail("1234", "firstname");
// $user->setUserDetail("1234", "firstname", "Emmanuel");

// $arr = array("name" => "emmy", "age" => 21);
// $arr['color'] = "green";
// // print_r($arr);
// $arr = json_encode($arr);
// print_r($arr);
// $arr = json_decode($arr);
// $arr = (array) $arr;
// echo count($arr);

echo $user->sendVerificationCodeSms("2348162383712");
// $n = 1234;
// echo strval($n);
