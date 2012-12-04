<?php

/* Connect to an ODBC database using driver invocation */
$dsn = 'mysql:dbname=;host=';
$user = '';
$password = '';

$db = "alienwarearena";

try {
    $dbh = new PDO($dsn, $user, $password);

    $sqlFormat = 'INSERT IGNORE INTO `'.$db.'`.`fos_user` (`avatar_approved`, `username`, `username_canonical`, `email`, `email_canonical`, `enabled`, `algorithm`, `salt`, `password`, `locked`, `expired`, `roles`, `credentials_expired`, `firstname`, `lastname`, `country`, `subscribedAlienwareEvents`, `created`, `updated`, `cevoUserId`, `locale`)
    VALUES (0, :username, :usernameCanonical, :email, :emailCanonical, 1, "SHA512", "n9lwb7si48gsc4kk0ooo00gsc8w444k", "AUTO_GEN_PASSWORD_UNUSED", 0, 0, "a:0:{}", 0, :firstname, :lastname, :country, :contact, NOW(), NOW(), :cevoId, :locale)';

    $cevoIdArray = array();

    if (($handle = fopen("/home/ubuntu/scripts/users.csv", "r")) !== FALSE) {

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $cevoId             = $data[0];
            $username           = $data[1];
            $usernameCanonical  = strtolower($username);
            $email              = $data[2];
            $emailCanonical     = strtolower($email);
            $firstname          = $data[3];
            $lastname           = $data[4];
            $country            = $data[6];
            $contact            = $data[7] == "YES" ? 1 : 0;
            $locale             = $country == "US" ? "en" : $country == "CN" ? "zh" : $country == "JP" ? "ja" : $country == "UK" ? "en" : null;

            $cevoIdArray[]  = $cevoId;

            $stat = $dbh->prepare($sqlFormat);

            $stat->bindParam(':cevoId', $cevoId);
            $stat->bindParam(':username', $username);
            $stat->bindParam(':usernameCanonical', $usernameCanonical);
            $stat->bindParam(':email', $email);
            $stat->bindParam(':emailCanonical', $emailCanonical);
            $stat->bindParam(':firstname', $firstname);
            $stat->bindParam(':lastname', $lastname);
            $stat->bindParam(':country', $country);
            $stat->bindParam(':contact', $contact);
            $stat->bindParam(':locale', $locale);

            $stat->execute();
            echo "[ $username ] added\n";
            $row++;

        }

        fclose($handle);

        echo "\n$row users added...";

        $cevoIds =  implode(',', $cevoIdArray) ;
        $groupSql = 'INSERT IGNORE INTO `'.$db.'`.`pd_groups_members` SELECT 1, `id` FROM `fos_user` WHERE `cevoUserId` IN ('.$cevoIds.')';
        $stat = $dbh->prepare($groupSql);
        $stat->bindParam(':cevoIds', $cevoIds);
        $stat->execute();
    }

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

?>

