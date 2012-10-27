<?php

/* Connect to an ODBC database using driver invocation */
$dsn = 'mysql:dbname=;host=';
$user = '';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);

    $keyArray = array();

    $sql = 'SELECT `id` FROM `alienware_production`.`giveaway_key` where `pool` = 41 and `user` is null limit 1000';
    foreach ($dbh->query($sql) as $row) {
        $keyArray[] = $row['id'];
    }

    $sqlFormat = 'UPDATE `alienware_production`.`giveaway_key` SET `user` = (SELECT `id` FROM `alienware_production`.`fos_user` WHERE `cevoUserId` = :cevoUserId LIMIT 1), `assigned_at` = NOW(), `ip_address` = "1.1.1.1", `assigned_site` = "zh" where id = :keyId;';

    if (($handle = fopen("/home/ubuntu/scripts/china_tournament_user_key_mass_assign/users.csv", "r")) !== FALSE) {

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $userId = (int) $data[1];

            if (!$userId || $userId == 0) {
                continue;
            }

            $keyId = $keyArray[$row];

            $row++;

            $stat = $dbh->prepare($sqlFormat);

            $stat->bindParam(':cevoUserId', $userId);
            $stat->bindParam(':keyId', $keyId);

            $stat->execute();
            echo "$userId => $keyId\n";
        }

        fclose($handle);
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

?>

