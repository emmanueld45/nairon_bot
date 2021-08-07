<?php
include 'database.class.php';
include 'users.class.php';
include 'messages.class.php';

define('BOT_TOKEN', '1917951913:AAFqADvnFDHq4FFYAMnRty_idjjOrwmOBEQ');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

function apiRequestWebhook($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }

    $parameters["method"] = $method;

    $payload = json_encode($parameters);
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($payload));
    echo $payload;

    return true;
}

function exec_curl_request($handle)
{
    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error\n");
        curl_close($handle);
        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
        // do not wat to DDOS server if something goes wrong
        sleep(10);
        return false;
    } else if ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }
        return false;
    } else {
        $response = json_decode($response, true);
        if (isset($response['description'])) {
            error_log("Request was successful: {$response['description']}\n");
        }
        $response = $response['result'];
    }

    return $response;
}

function apiRequest($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }

    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }
    $url = API_URL . $method . '?' . http_build_query($parameters);

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);

    return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters)
{
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }

    $parameters["method"] = $method;

    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

    return exec_curl_request($handle);
}



function getUserDetails($user_id)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://owoprofit.com/btcmining_getUserDetails.php?user_id=$user_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $user_details = curl_exec($ch);
    curl_close($ch);
    $user_details = (array) json_decode($user_details);

    return $user_details;
}
function processMessage($message)
{
    global $m;
    global $db;
    global $user;
    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $username = $message['from']['username'];
    $firstname = $message['from']['first_name'];

    if (!$user->userExists($user_id)) {
        $user->createUser($user_id, $chat_id, $username, $firstname);
    }

    // $user_details = getUserDetails($user_id);
    $user_details = $db->setHandlerQuery("SELECT * FROM users WHERE user_id='$user_id';");
    $user_details = $user_details[0];


    if (isset($message['text'])) {
        // incoming text message
        $text = $message['text'];

        if ($user_details['pending_action'] != "empty") {
            $pending_action = $user_details['pending_action'];
            $pending_action_data = (array) json_decode($user_details['pending_action_data']);
            if ($pending_action == "verify email") {

                $pending_action_data['email_verification_time'] = time();
                $user->setUserDetail($user_id, "pending_action", "enter email verification code");

                $verification_code = $user->sendVerificationCodeEmail($text);
                $pending_action_data['email_verification_code'] = $verification_code;
                $pending_action_data['verification_email'] = $text;
                $pending_action_data = json_encode($pending_action_data);
                $user->setUserDetail($user_details['user_id'], "pending_action_data", $pending_action_data);
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'A verification code has been sent to your email. Please enter it below! (You can retry again in 120secs if you didnt receive the code)'));
                apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => "options:", 'reply_markup' => array(
                    'keyboard' => $m->resendEmailVerificationMenu($user_details),
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true
                )));
            } else if ($pending_action == "enter email verification code") {
                if ($text == $pending_action_data['email_verification_code']) {
                    $user->setUserDetail($user_id, "email", $pending_action_data['verification_email']);
                    $user_details = $db->setHandlerQuery("SELECT * FROM users WHERE user_id='$user_id';");
                    $user_details = $user_details[0];
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Your email address has been verified! please proceed to set your password.'));
                    $user->setUserDetail($user_id, "pending_action", "empty");
                    $user->setUserDetail($user_id, "pending_action_data", json_encode(array()));
                    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => "menu", 'reply_markup' => array(
                        'keyboard' => $m->startMenu($user_details),
                        'one_time_keyboard' => true,
                        'resize_keyboard' => true
                    )));
                } else if ($text === "Resend Email Code") {
                    // $user->setUserDetail($user_id, "pending_action", "verify email");
                    $time_diff = time() - $pending_action_data['email_verification_time'];
                    $time_remaining  = $time_diff - 60;
                    if ($time_diff >= 60) {
                        $pending_action_data['email_verification_time'] = time();
                        $user->setUserDetail($user_id, "pending_action", "enter email verification code");
                        $verification_code = $user->sendVerificationCodeEmail($text);
                        $pending_action_data['email_verification_code'] = $verification_code;
                        $pending_action_data = json_encode($pending_action_data);
                        $user->setUserDetail($user_details['user_id'], "pending_action_data", $pending_action_data);
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'A new verification code has been sent to your email. Please enter it below! (You can retry again in 120secs if you didnt receive the code)'));
                    } else {
                        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Sorry, you can only resend code after ' . $time_remaining . ' secs'));
                    }
                } else if ($text == "Change Email") {
                    $user->setUserDetail($user_id, "pending_action", "verify email");
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please enter your email address below: '));
                } else {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Verification code is incorrect!'));
                }
            } else if ($pending_action == "set password") {
                // $pending_action = $user_details['pending_action'];
                $pending_action_data = (array) json_decode($user_details['pending_action_data']);
                $pending_action_data['pending_password'] = $text;
                $pending_action_data = json_encode($pending_action_data);
                $user->setUserDetail($user_details['user_id'], "pending_action_data", $pending_action_data);
                $user->setUserDetail($user_id, "pending_action", "confirm password");
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please re-type password: '));
            } else if ($pending_action == "confirm password") {
                // $pending_action = $user_details['pending_action'];
                $pending_action_data = (array) json_decode($user_details['pending_action_data']);
                if ($text == $pending_action_data['pending_password']) {
                    $password = password_hash($text, PASSWORD_DEFAULT);
                    $user->setUserDetail($user_details['user_id'], "password", $password);
                    $user->setUserDetail($user_id, "pending_action", "empty");
                    $user->setUserDetail($user_id, "pending_action_data", json_encode(array()));
                    $user_details = $db->setHandlerQuery("SELECT * FROM users WHERE user_id='$user_id';");
                    $user_details = $user_details[0];
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Your password has been successfully set!'));
                    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => "options:", 'reply_markup' => array(
                        'keyboard' => $m->startMenu($user_details),
                        'one_time_keyboard' => true,
                        'resize_keyboard' => true
                    )));
                } else {
                    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Passwords do not match!'));
                    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => "options:", 'reply_markup' => array(
                        'keyboard' => $m->changePasswordMenu($user_details),
                        'one_time_keyboard' => true,
                        'resize_keyboard' => true
                    )));
                }
            }
        } else if (strpos($text, "/start") === 0) {
            global $db;

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => $m->startMessage(), 'reply_markup' => array(
                'keyboard' => $m->startMenu($user_details),
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            )));
        } else if ($text === "Verify Email") {
            if ($user_details['email'] == "empty") {
                $user->setUserDetail($user_id, "pending_action", "verify email");
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please enter your email below: '));
            } else {
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Your email has already been verified!'));
            }
        } else if ($text === "Set Password") {
            if ($user_details['password'] == "empty") {
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please enter a secure password below (store this password because it may be needed in the future! and cannot be changed)'));
                $user->setUserDetail($user_id, "pending_action", "set password");
            } else {
                apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Your password is already set!'));
            }
        } else if ($text === "Hello" || $text === "Hi") {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you. please type /start to see our menu options'));
        } else if (strpos($text, "Balance") === 0) {
            // apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'You have not made any deposits yet. your balance is 0.0000000 BTC, available to withdraw: 0.0000000 BTC. Kindly click on the Deposit button to begin investment'));
        } else if (strpos($text, "Deposit") === 0) {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'A unique BTC address has been generated for you: A1KJFH74785787DHFFFKD kindly make a minimum of 0.002 BTC deposit to the address and your balance would automatically be funded. you will receive a 1.25% interest per day which is withdrawable at anytime.'));
        } else if (strpos($text, "/stop") === 0) {
            // stop now
        } else if (strpos($text, "Settings") === 0 or $text === "/settings") {

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Settings', 'reply_markup' => array(
                'keyboard' => $m->settingsKeyboard($user_details),
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            )));
        } else if ($text === "Setup withdrawal address") {
            apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'parse_mode' => 'html', "text" => $m->setupWithdrawalAddressMessage(getUserDetails($user_id)), 'reply_markup' => array(
                'keyboard' => $m->startMenu($user_details),
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            )));
        } else if ($text === "go back") {
            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => "menu", 'reply_markup' => array(
                'keyboard' => $m->startMenu(getUserDetails($user_id)),
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            )));
        } else if (strpos($text, "Withdraw") === 0) {
            $user_details = getUserDetails($user_id);
            if ($user_details['have_started_mining'] === "no") {
                if ($user_details['wallet_address'] != "empty") {
                    if ($user_details['balance'] >= $db->min_withdrawable) {
                        // send withdraw msg
                        apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Withdrawal fees are paid by users to enable us send payments. a unique deposit address will be created for you to make your withdrawal fee and funds would be transferred to your wallet address automatically(YOUR WALLET = ' . $user_details['wallet_address'] . ')'));
                        apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Please wait while we generate a unique bitcoin wallet address for you'));
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://owoprofit.com/btcmining_create_new_address.php");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        $generate_address = curl_exec($ch);
                        curl_close($ch);
                        $generate_address = (array) json_decode($generate_address);
                        if ($generate_address['status'] == '200') {
                            $address = $generate_address['address'];
                            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Your withdrawal fee deposit address is:'));
                            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => $address));
                            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Make a minimum payment of 0.0005 BTC to the address above and your mined bitcoins (' . $user_details['balance'] . ')  will be automatically sent to your wallet address.'));
                        } else {
                            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Sorry, an error occurred while creating address. Please try again'));
                        }
                    } else {
                        // insufficent withdrawable balance
                        apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Sorry, you have not reached the minimum withdrawal limit: ' . $db->min_withdrawable));
                    }
                } else {
                    // wallet address is empty
                    apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'You have not set a withdrawal wallet adddress yet. please select Settings on the menu tab or click here /settings and setup a withdrawal address!'));
                }
            } else {
                apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'You are currently mining. please stop mining to process a withdrawal'));
            }
        } else if (strpos($text, "FAQ") === 0) {

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'parse_mode' => 'html', "text" => $m->faqMessage(getUserDetails($user_id))));
        } else if (strpos($text, "Support") === 0) {

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'parse_mode' => 'html', "text" => $m->supportMessage(getUserDetails($user_id))));
        } else if ($text === "Bitcoin") {
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Balance: 0.00023756 BTC', 'callback_data' => 'Balance'],
                    ],
                    [
                        ['text' => 'Deposit', 'callback_data' => 'Deposit'],
                        ['text' => 'Withdraw', 'callback_data' => 'Withdraw'],
                        ['text' => 'Transactions', 'callback_data' => 'Transactions'],
                    ],
                    [
                        ['text' => 'START MINING', 'callback_data' => 'startmining'],
                    ]
                ]
            ];
            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => $keyboard));
        } else if ($text === "< Check Stats >") {
        } else {
            apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'I dont understand this command yet.'));
            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'menu', 'reply_markup' => array(
                'keyboard' => $m->startMenu(getUserDetails($user_id)),
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            )));
        }
    } else {
        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
    }
}

















function processCallbackQuery($callback_query)
{

    global $db;
    // process incoming message

    $chat_id = $callback_query['message']['chat']['id'];
    $id_callback = $callback_query['id'];
    $user_id = $callback_query['message']['from']['id'];
    // incoming text message
    $data = $callback_query['data'];

    if ($data === "Deposit") {
        apiRequestJson("answerCallbackQuery", array('callback_query_id' => $id_callback, "text" => 'You answered'));
        // $keyboard = [
        //     'inline_keyboard' => [
        //         [
        //             ['text' => 'Via wallet?', 'callback_data' => 'wallet'],
        //             ['text' => 'Via website?', 'callback_data' => 'website'],
        //         ],

        //     ]
        // ];
        // apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'How would you like to deposit?', 'reply_markup' => $keyboard));
    }
}
// define('WEBHOOK_URL', 'https://my-site.example.com/secret-path-for-webhooks/');

// if (php_sapi_name() == 'cli') {
//     // if run from console, set or delete webhook
//     apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
//     exit;
// }


$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // receive wrong update, must not happen
    exit;
}

if (isset($update["message"])) {
    processMessage($update["message"]);
} else if (isset($update['callback_query'])) {
    processCallbackQuery($update['callback_query']);
}
