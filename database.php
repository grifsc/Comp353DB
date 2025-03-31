<?php

    $db_server = "kqc353.encs.concordia.ca";
    $db_user = "kqc353_4";
    $db_pass = "Dinosaur";
    $db_name ="kqc353_4";
    $conn = "";

    $conn = mysqli_connect($db_server,
                            $db_user,
                            $db_pass,
                            $db_name);

    if($conn) {
        echo "Connection Successful";
    }
    else{
        echo "Connection Failed";
    } 
?>