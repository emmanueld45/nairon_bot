<?php

class Database
{

    /*
    public $dbservername = 'localhost';

    public $dbusername = 'root';

    public $dbpassword = '';

    public $dbname = 'btcmining_bot';

    */
    public $dbservername = 'localhost';

    public $dbusername = 'root';

    public $dbpassword = '';

    public $dbname = 'nairon_bot';


    public $conn;

    public $sql;

    public $project_root_endpoint = "https://harvoxxtest.com.ng/nairon_bot";

    public function __construct()
    {
        $this->conn = mysqli_connect($this->dbservername, $this->dbusername, $this->dbpassword, $this->dbname);
    }

    public function setQuery($query)
    {
        $this->sql = $query;
        $result = mysqli_query($this->conn, $this->sql);
        return $result;
    }

    public function setHandlerQuery($query)
    {
        $data_array = [];
        $query = urlencode($query);

        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $this->project_root_endpoint . "/handler.php?query=$query");
        curl_setopt($ch, CURLOPT_URL, "http://localhost/nairon_bot/handler.php?query=$query");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = (array) json_decode($data);
        foreach ($data as $item) {
            $item = (array) $item;
            $data_array[count($data_array)] = $item;
        }
        return $data_array;
    }


    public function numrows($data)
    {
        $x = 0;
        foreach ($data as $item) {
            $x++;
        }
        return $x;
    }

    public function getMiningSessionTime($time)
    {
        if ($time < 60) {
            return round($time) . " seconds";
        } else if ($time >= 60 and $time < 3600) {
            return round($time / 60) . " minutes";
        } else if ($time >= 3600 and $time < 86400) {
            return round($time / 3600) . " hours";
        }
    }
}

$db = new Database();
$website_name = "Nairon Bot";
