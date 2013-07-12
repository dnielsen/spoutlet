<?php

    $dsn = 'mysql:dbname=alienware_production;host=alienwaredb.cix3pdvfa70g.us-east-1.rds.amazonaws.com';
    $user = 'alienwaremaster';
    $password = 'f78284q9vL2B5n6';

    try {
        $dbh        = new PDO($dsn, $user, $password);
        $timestamp  = strtotime('-4 hours');

        $sqlFormat  = 'DELETE FROM `alienware_production`.`session` WHERE `session_time` < :time';

        $stat = $dbh->prepare($sqlFormat);
        $stat->bindParam(':time', $timestamp);
        $stat->execute();

    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }

?>
