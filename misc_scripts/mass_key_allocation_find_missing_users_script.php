<?php

/* Connect to an ODBC database using driver invocation */
$dsn = 'mysql:dbname=;host=';
$user = '';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);

    $missingIdArray = array();

    $sqlFormat = 'SELECT `id` FROM `alienware_production`.`fos_user` WHERE `cevoUserId` = %d LIMIT 1';

    if (($handle = fopen("/home/ubuntu/scripts/china_tournament_user_key_mass_assign/users.csv", "r")) !== FALSE) {

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $userId = (int) $data[1];

            if (!$userId || $userId == 0) {
                continue;
            }

            $row++;

            $sql = sprintf($sqlFormat, $userId);

            $id = $dbh->query($sql)->fetchColumn();

            if (!$id) {
                echo $data[0].",".$data[1].",".$data[2]."\n";
            }
        }

        fclose($handle);
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

?>

