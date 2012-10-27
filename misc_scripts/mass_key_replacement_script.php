<?php

/* Connect to an ODBC database using driver invocation */
$dsn = 'mysql:dbname=;host=';
$user = '';
$password = '';

try {
    $dbh = new PDO($dsn, $user, $password);

    $keyArray = array();

    $sql = 'SELECT `id` FROM `alienware_production`.`giveaway_key` where `pool` = 40 and `user` is not null LIMIT 1001';
    foreach ($dbh->query($sql) as $row) {
        $keyArray[] = $row['id'];
    }

    echo count($keyArray)." rows found.\n\n";

    $sqlFormat = 'UPDATE `alienware_production`.`giveaway_key` SET  `value` = :value WHERE `id` = :id';

    if (($handle = fopen("/home/ubuntu/scripts/china_tournament_user_key_mass_update/keys.csv", "r")) !== FALSE) {

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $newKey = $data[0];

            if (!$newKey || !isset($keyArray[$row])) {
                continue;
            }

            $id = $keyArray[$row];

            $row++;

            $stat = $dbh->prepare($sqlFormat);

            $stat->bindParam(':value', $newKey);
            $stat->bindParam(':id', $id);

            $stat->execute();
            //echo "$id => $newKey\n";
        }

        echo ($row). " rows updated\n\n";

        fclose($handle);
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

?>

