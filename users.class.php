<?php

class Users
{



    public function createUser($user_id, $chat_id, $username, $firstname)
    {

        global $db;

        $time = time();
        $date = date("d/m/y");
        $balance = 0.00000000;
        $phone = "empty";
        $email = "empty";
        $password = "empty";
        $pending_action = "empty";
        $pending_action_data = array();
        $pending_action_data  = json_encode($pending_action_data);
        // $result = $db->setQuery("INSERT INTO users (user_id, chat_id, username, firstname, balance, mining_time, have_started_mining, wallet_address, withdrawal_pin, mining_plan, time, date) VALUES ('$user_id', '$chat_id', '$username', '$firstname', '0.00000000', '0', 'no', 'empty', 'empty', 'empty', '$time', '$date');");
        $result = $db->setHandlerQuery("INSERT INTO users (user_id, chat_id, username, firstname, phone, email, password, balance, pending_action, pending_action_data, time, date) VALUES ('$user_id', '$chat_id', '$username', '$firstname', '$phone', '$email', '$password', '$balance', '$pending_action', '$pending_action_data', '$time', '$date');");
    }

    public function userExists($user_id)
    {
        global $db;

        $result = $db->setHandlerQuery("SELECT * FROM users WHERE user_id='$user_id';");
        $numrows = $db->numrows($result);

        if ($numrows != 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserDetail($user_id, $detail)
    {
        global $db;

        $result = $db->setHandlerQuery("SELECT * FROM users WHERE user_id='$user_id';");
        $row = $result[0];

        return $row[$detail];
    }

    public function setUserDetail($user_id, $detail, $value)
    {
        global $db;

        $result = $db->setHandlerQuery("UPDATE users SET $detail='$value' WHERE user_id='$user_id';");
    }

    public function sendVerificationCodeSms($phone)
    {
        $sender = "Owoprofit";
        $verification_code = RAND(10000, 20000);

        // $code = "1234";
        // $text = 'Hello, your Nairon verification code is ' . $verification_code;
        $text = 'NAIRON: ' . $verification_code;
        $url = 'https://kullsms.com/customer/api/?username=owoprofit@gmail.com&password=Emmanueld45@&message=' . urlencode($text) . '&sender=' . urlencode($sender) . '&mobiles=' . $phone;
        $send = file_get_contents($url);

        return $verification_code;
    }

    public function sendVerificationCodeEmail($email)
    {
        $verification_code = RAND(10000, 20000);

        $title = "NAIRON VERIFICATION";
        $footer = "This email was sent to you because you signed on Nairon Bot | The links in this email will always direct to " . $this->website_url_http . " Learn about email security and online safety. 
   © " . $this->website_name;

        $to =  $email;
        $subject = $title;
        $from = 'nairon exhange@correctleg.com';

        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // Create email headers
        $headers .= 'From: ' . $from . "\r\n" .
            'Reply-To: ' . $from . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        $message = 'Your verification code is: ' . $verification_code;

        // Sending email
        $send = mail($to, $subject, $message, $headers);


        return $verification_code;
    }
}

$user = new Users();
