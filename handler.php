<?php
include 'database.class.php';
include 'users.class.php';



if (isset($_GET['query'])) {
    $result = $db->setQuery($_GET['query']);
    // $row = mysqli_fetch_assoc($result);
    // echo json_encode($result);
    $array = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $array[count($array)] = $row;
    }
    echo  json_encode($array);
}
