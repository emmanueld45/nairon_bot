<?php


class Messages
{

    public $amount_per_minute = 0.000005;
    public $min_withdrawable = 0.00000000; //0.01;
    // would take 48 complete hours to reach min withdrawable balance

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


    public function startMessage()
    {
        $message = "Hello, Nairon was built by Emmanuel Dan-jumbo";

        return $message;
    }

    public function startKeyboard($user_details)
    {


        $keyboard = [
            ["Balance: " . $user_details['balance'] . " NRN \xE2\x9C\x85"],
            ["< Check Stats >"],
            ["Send \xE2\x86\x97", "Receive \xE2\x86\x99"],
            ["Settings \xE2\x86\xA9", "Transactions \xF0\x9F\x95\x99"],

            ["FAQ \xF0\x9F\x8F\x81", "Support \xF0\x9F\x8C\xBC"],
        ];

        return $keyboard;
    }


    public function verifyemailKeyboard($user_details)
    {


        $keyboard = [
            ["Verify Email"],

        ];

        return $keyboard;
    }

    public function setPasswordKeyboard($user_details)
    {


        $keyboard = [
            ["Set Password"],

        ];

        return $keyboard;
    }



    public function resendEmailVerificationMenu($user_details)
    {
        $keyboard = [
            ["Resend Email Code"],
            ["Change Email"],
        ];

        return $keyboard;
    }

    public function changePasswordMenu($user_details)
    {
        $keyboard = [
            ["Change Password"],
        ];

        return $keyboard;
    }


    public function startMenu($user_details)
    {
        global $user;

        if ($user_details['email'] == "empty") {
            // $user->setUserDetail($user_details['user_id'], "pending_action", "verify email");
            return $this->verifyEmailKeyboard($user_details);
        } else if ($user_details['password'] == "empty") {
            // $user->setUserDetail($user_details['user_id'], "pending_action", "set password");
            return $this->setPasswordKeyboard($user_details);
        } else {
            return $this->startKeyboard($user_details);
        }
    }


    public function settingsKeyboard($user_details)
    {
        $keyboard = [
            ["Setup withdrawal address"],
            ["go back"],
        ];

        return $keyboard;
    }

    public function setupWithdrawalAddressMessage($user_details)
    {
        $message = "To withdraw your mined bitcoins you need to setup your withdrawal wallet address. please send a text in this format: \n\n wallet = <b>YOUR WALLET ADDRESS</b>";
        return $message;
    }

    public function faqMessage($user_details)
    {
        $message = "
         <b>What is Btcmining?</b> \n
         Btcmining is a mining bot that connects to the bitcoin network enabling users to mine from their device anywhere.\n\n

         <b>How can i start mining</b> \n
         You can start mining by clicking the START MINING menu button. you earn 0.000005 BTC per minute. \n\n

         <b>How long can i mine?</b> \n
         Each mining session you start lasts for 2 hours. this is to prevent misuse of resources, you can always start the session 
         again by clicking the START MINING button

        ";
        return $message;
    }

    public function supportMessage($user_details)
    {
        $message = "you can message our support at https://t.me/btcminingp2p_bot";
        return $message;
    }
}

$m = new Messages();
