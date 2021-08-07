<?php
include 'database.class.php';

if (isset($_GET['create_user'])) {
  $db->createUser($_GET['user_id'], $_GET['chat_id'], $_GET['username'], $_GET['firstname']);
}
