<?php

if (!isset($argv[1])) {
    echo 'Argument 1 passed to this script must be the path to the dump .csv file.'."\n";
    exit;
}

$path = $argv[1];

if (!file_exists($path)) {
    echo 'Path [ '.$path.' ] is not a valid path'."\n";
    exit;
}

$debug = false;

if (isset($argv[2]) && $argv[2] == '--debug') {
    $debug = true;
}

$cmd = new CevoFailedUserImportCommand();
$cmd->execute($path, $debug);

class CevoFailedUserImportCommand
{
    private $directory;
    private $importFile;
    private $cevoIdUuidMap        = array();
    private $avatarUserMap        = array();
    private $usersAvatars         = array();
    private $debug                = false;
    private $errors               = array();
    private $begunIterations      = false;
    private $exitAfterCurrentItem = false;
    private $uuids                = array();
    private $failedUsers          = array();
    private $newUsernames         = array();
    private $runTime;
    private $runTimeString;
    private $logDir;

    protected function output($indentationLevel = 0, $message = null, $withNewLine = true) {

        if ($message === null) {
            $message = '';
        }

        echo(str_repeat(' ', $indentationLevel).$message.($withNewLine ? "\n" : ''));
    }

    protected function tick()
    {
        $this->output(0, '✔');
    }

    protected function error($message, $exit = false)
    {
        $this->output(0);
        $this->output(0, $message);
        $this->output(0);

        $dt = new \DateTime();

        file_put_contents($this->logDir.'/failed_user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$message."\n", FILE_APPEND);

        if ($exit) {
            $this->outputErrors();
            exit;
        }

        $this->errors[] = $message;
    }

    protected function dirCheck($path)
    {
        if(!file_exists($path)) {
            mkdir($path, 0775, true);
        }
    }

    protected function loadUuids()
    {
        $filepath = rtrim($this->directory, '/').'/uuid_stash.csv';

        if (!file_exists($filepath)) {
            return;
        }

        if (($handle = fopen($filepath, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data[0])) {
                    continue;
                }

                $this->uuids[] = $data[0];
            }

            fclose($handle);
        }
    }

    protected function writeCsvRow($filePath, $rowData)
    {
        $fp = fopen($filePath, 'a');
        fputcsv($fp, $rowData);
        fclose($fp);
    }

    protected function writeCevoCsvRow(array $rowData) {
        $this->writeCsvRow(rtrim($this->directory, '/').'/cevo_user_id_uuid_map.csv' , $rowData);
        $this->cevoIdUuidMap[$rowData[0]] = $rowData[1];
    }

    protected function writeNonImportedUserCsvRow($reason, array $rowData)
    {
        $rowData[] = $reason;
        $this->writeCsvRow($this->logDir.'/non_imported_failed_users.csv' , $rowData);
    }

    protected function readCevoMapCsv()
    {
        $filepath = rtrim($this->directory, '/').'/cevo_user_id_uuid_map.csv';

        if (!file_exists($filepath)) {
            $this->error('CEVO user map file [ '.$filepath.' ] does not exist.', true);
        }

        if (($handle = fopen($filepath, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (isset($data[0]) && isset($data[1])) {
                    $this->cevoIdUuidMap[$data[0]] = $data[1];
                } else {
                    $this->error('CEVO User CSV row ignored as all values were not present.');
                }
            }

            fclose($handle);
        }
    }

    protected function readAvatarMapCsv()
    {
        $filepath = rtrim($this->directory, '/').'/user_avatar_map.csv';

        if (!file_exists($filepath)) {
            $this->error('User avatar map file [ '.$filepath.' ] does not exist.', true);
        }

        if (($handle = fopen($filepath, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (isset($data[0]) && isset($data[1]) && isset($data[2]) && isset($data[3])) {
                    $this->avatarUserMap[$data[0]] = array(
                        'userUuid'   => $data[1],
                        'avatarUuid' => $data[2],
                        'avatarId'   => $data[3],
                    );

                    $this->usersAvatars[$data[1]][] = $data[3];
                } else {
                    $this->error('Avatar CSV row ignored as all values were not present.');
                }
            }

            fclose($handle);
        }
    }

    protected function writeUserModificationLogCsvRow($rowData)
    {
        $this->writeCsvRow($this->logDir.'/user_modifications.csv' , $rowData);
    }

    protected function readFailedUsersCsv()
    {
        $filepath = $this->importFile;

        if (!file_exists($filepath)) {
            $this->error('Failed users file [ '.$filepath.' ] does not exist.', true);
        }

        if (($handle = fopen($filepath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                $createdDt   = \DateTime::createFromFormat('U', $data[6]);
                $birthdateDt = \DateTime::createFromFormat('Y-m-d', $data[13]);
                $fallbackDt  = new \DateTime();

                $username = ($data[1] != '' ? $data[1] : null);

                if (false !== strpos($username, '?')) {
                    $this->writeUserModificationLogCsvRow(array(
                        $data[0],
                        $username,
                        $data[3],
                        'Username contained ? - set to null',
                    ));

                    $username = null;
                }

                $usernameCanonical = $username ? $this->canonicalize($username) : null;

                $this->failedUsers[] = array(
                    'cevoUserId'         => $data[0],
                    'username'           => $username,
                    'username_canonical' => $usernameCanonical,
                    'password'           => $data[2],
                    'email'              => $data[3],
                    'email_canonical'    => $this->canonicalize($data[3]),
                    'created'            => $createdDt ? $createdDt->format('Y-m-d H:i:s') : $fallbackDt->format('Y-m-d H:i:s'),
                    'ipAddress'          => $data[7] != '' ? $data[7] : null,
                    'origin'             => $data[8],
                    'firstName'          => $data[11],
                    'lastName'           => $data[12],
                    'birthDate'          => $birthdateDt ? $birthdateDt->format('Y-m-d') : null,
                    'phone'              => $data[14] != '' ? $data[14] : null,
                    'country'            => $data[16] != '' ? $data[16] : null,
                    'state'              => $data[17] != '' ? $data[17] : null,
                    'allowContact'       => $data[25] != '' ? $data[25] : 0,
                    'dellOptIn'          => $data[26] != '' ? $data[26] : 0,
                    'avatar'             => $data[35] != '' && $data[35] !== '0' ? $data[35] : null,
                    'aboutMe'            => $data[36] != '' ? $data[36] : null,
                    'manufacturer'       => $data[38] != '' ? $data[38] : null,
                    'os'                 => $data[39] != '' ? $data[39] : null,
                    'cpu'                => $data[40] != '' ? $data[40] : null,
                    'ram'                => $data[41] != '' ? $data[41] : null,
                    'hardDrive'          => $data[42] != '' ? $data[42] : null,
                    'videoCard'          => $data[43] != '' ? $data[43] : null,
                    'soundCard'          => $data[44] != '' ? $data[44] : null,
                    'headphones'         => $data[45] != '' ? $data[45] : null,
                    'monitor'            => $data[46] != '' ? $data[46] : null,
                    'mouse'              => $data[47] != '' ? $data[47] : null,
                    'mousepad'           => $data[48] != '' ? $data[48] : null,
                    'keyboard'           => $data[49] != '' ? $data[49] : null,
                    'reason'             => isset($data[50]) ? $data[50] : null,
                    'dataRow'            => $data,
                );

                if ($data[8] == 'cnjp' && substr($username, -4) == '_new') {
                    $this->newUsernames[] = $username;
                }
            }

            fclose($handle);
        }
    }

    protected function outputUserData($userData)
    {
        $this->output();
        $this->output(2, 'User => { ');
        $this->output(8, 'cevoUserId         = '.$userData['cevoUserId']  );
        $this->output(8, 'username           = '.$userData['username']    );
        $this->output(8, 'username_canonical = '.$userData['username_canonical']);
        $this->output(8, 'password           = '.$userData['password']    );
        $this->output(8, 'email              = '.$userData['email']       );
        $this->output(8, 'email_canonical    = '.$userData['email_canonical']);
        $this->output(8, 'created            = '.$userData['created']);
        $this->output(8, 'ipAddress          = '.$userData['ipAddress']   );
        $this->output(8, 'firstName          = '.$userData['firstName']   );
        $this->output(8, 'lastName           = '.$userData['lastName']    );
        $this->output(8, 'birthDate          = '.$userData['birthDate']);
        $this->output(8, 'phone              = '.$userData['phone']       );
        $this->output(8, 'country            = '.$userData['country']     );
        $this->output(8, 'state              = '.$userData['state']       );
        $this->output(8, 'allowContact       = '.$userData['allowContact']);
        $this->output(8, 'dellOptIn          = '.$userData['dellOptIn']);
        $this->output(8, 'avatar             = '.$userData['avatar']      );
        $this->output(8, 'aboutMe            = '.$userData['aboutMe']     );
        $this->output(8, 'manufacturer       = '.$userData['manufacturer']);
        $this->output(8, 'os                 = '.$userData['os']          );
        $this->output(8, 'cpu                = '.$userData['cpu']         );
        $this->output(8, 'ram                = '.$userData['ram']         );
        $this->output(8, 'hardDrive          = '.$userData['hardDrive']   );
        $this->output(8, 'videoCard          = '.$userData['videoCard']   );
        $this->output(8, 'soundCard          = '.$userData['soundCard']   );
        $this->output(8, 'headphones         = '.$userData['headphones']  );
        $this->output(8, 'monitor            = '.$userData['monitor']     );
        $this->output(8, 'mouse              = '.$userData['mouse']       );
        $this->output(8, 'mousepad           = '.$userData['mousepad']    );
        $this->output(8, 'keyboard           = '.$userData['keyboard']    );
        $this->output(2, ' }');
    }

    protected function outputErrors()
    {
        if (count($this->errors) > 0) {
            $this->output();
            $this->output(0, 'Errors:');
            $this->output();

            foreach ($this->errors as $error) {
                $this->output(2, $error);
            }
        }
    }

    protected function canonicalize($string)
    {
        return mb_convert_case($string, MB_CASE_LOWER, mb_detect_encoding($string));
    }

    public function signal_handler($signal)
    {
        switch($signal) {
            case SIGTERM:
                $signalType = 'SIGTERM';
                break;
            case SIGKILL:
                $signalType = 'SIGKILL';
                break;
            case SIGINT:
                $signalType = 'SIGINT';
                break;
            case SIGHUP:
                $signalType = 'SIGHUP';
                break;
            default:
                $signalType = 'UNKNOWN_SIGNAL';
                break;
        }

        $this->output();
        $this->output(0, 'Caught signal ['.$signalType.']. Finishing processing...');

        if (!$this->begunIterations) {
            exit;
        }

        $this->exitAfterCurrentItem = true;
    }

    public function execute($path, $debug = false)
    {
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'signal_handler'));
        pcntl_signal(SIGINT, array($this, 'signal_handler'));

        $this->debug = $debug;
        $this->importFile = $path;
        $this->directory  = dirname($path);

        $this->runTime       = new \DateTime()
        $this->runTimeString = $this->runTime->format('YmdHis');

        $this->logDir = rtrim($this->directory, '/').'/logs_'.$this->runTimeString;
        $this->dirCheck($this->logDir);

        $dsn        = 'mysql:dbname=alienware_production;host=alienwaredb.cix3pdvfa70g.us-east-1.rds.amazonaws.com';
        $dbUser     = 'alienwaremaster';
        $dbPassword = 'f78284q9vL2B5n6';
        $db         = 'alienware_production';

        $addedCount   = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        $this->output(0);
        $this->output(0, 'PlatformD Failed CEVO User Importer');
        $this->output(0);



        $this->output(0, 'Reading CEVO user UUID map data.');
        $this->readCevoMapCsv();

        $this->output(0, 'Reading avatar map data.');
        $this->readAvatarMapCsv();

        $this->output(0, 'Reading UUID stash into memory.');
        $this->loadUuids();

        $this->output(0, 'Reading failed user details into memory.');
        $this->readFailedUsersCsv();

        $this->output(0, 'Setting up database connection.');

        try {
            $dbh = new PDO($dsn, $dbUser, $dbPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        } catch (PDOException $e) {
            $this->error('Connection failed: ' . $e->getMessage(), true);
        }

        $this->output(0, 'Processing users in [ '.$path.' ].');

        $findUserByCevoUserIdSql       = 'SELECT `id`, `cevoUserId`, `uuid`, `username`, `username_canonical`, `email` FROM `'.$db.'`.`fos_user` WHERE `cevoUserId` = :cevoUserId';
        $findUserByCevoUserIdQuery     = $dbh->prepare($findUserByCevoUserIdSql);

        $findUserByUsernameSql         = 'SELECT `id`, `cevoUserId`, `uuid`, `username`, `username_canonical`, `email` FROM `'.$db.'`.`fos_user` WHERE `username_canonical` = :username_canonical';
        $findUserByUsernameQuery       = $dbh->prepare($findUserByUsernameSql);

        $findUserByEmailSql            = 'SELECT `id`, `cevoUserId`, `uuid`, `username`, `username_canonical`, `email` FROM `'.$db.'`.`fos_user` WHERE `email_canonical` = :email';
        $findUserByEmailQuery          = $dbh->prepare($findUserByEmailSql);

        $updateUsernameByUsernameSql   = 'UPDATE `'.$db.'`.`fos_user` SET `username` = :username, `username_canonical` = :username_canonical WHERE `username_canonical` = :old_username_canonical';
        $updateUsernameByUsernameQuery = $dbh->prepare($updateUsernameByUsernameSql);

        $createUserSql = 'INSERT INTO `'.$db.'`.`fos_user` SET
                        `username`                  = :username,
                        `username_canonical`        = :username_canonical,
                        `cevoUserId`                = :cevoUserId,
                        `password`                  = :password,
                        `uuid`                      = :uuid,
                        `email`                     = :email,
                        `email_canonical`           = :email_canonical,
                        `created`                   = :created,
                        `updated`                   = :updated,
                        `ipAddress`                 = :ipAddress,
                        `firstname`                 = :firstname,
                        `lastname`                  = :lastname,
                        `birthdate`                 = :birthdate,
                        `phone_number`              = :phone_number,
                        `country`                   = :country,
                        `state`                     = :state,
                        `about_me`                  = :about_me,
                        `manufacturer`              = :manufacturer,
                        `operatingSystem`           = :operatingSystem,
                        `cpu`                       = :cpu,
                        `memory`                    = :memory,
                        `videoCard`                 = :videoCard,
                        `soundCard`                 = :soundCard,
                        `hardDrive`                 = :hardDrive,
                        `headphones`                = :headphones,
                        `mouse`                     = :mouse,
                        `mousePad`                  = :mousePad,
                        `keyboard`                  = :keyboard,
                        `monitor`                   = :monitor,
                        `avatar_id`                 = :avatar_id,
                        `subscribed_gaming_news`    = :dell_optin,
                        `subscribedAlienwareEvents` = :allow_contact,
                        `roles`                     = "a:0:{}",
                        `locale`                    = "en",
                        `has_alienware_system`      = 0';

        $updateUserSql = 'UPDATE `'.$db.'`.`fos_user` SET
                        `username`                  = :username,
                        `username_canonical`        = :username_canonical,
                        `password`                  = :password,
                        `uuid`                      = :uuid,
                        `email`                     = :email,
                        `email_canonical`           = :email_canonical,
                        `updated`                   = :updated,
                        `ipAddress`                 = :ipAddress,
                        `firstname`                 = :firstname,
                        `lastname`                  = :lastname,
                        `birthdate`                 = :birthdate,
                        `phone_number`              = :phone_number,
                        `country`                   = :country,
                        `state`                     = :state,
                        `about_me`                  = :about_me,
                        `manufacturer`              = :manufacturer,
                        `operatingSystem`           = :operatingSystem,
                        `cpu`                       = :cpu,
                        `memory`                    = :memory,
                        `videoCard`                 = :videoCard,
                        `soundCard`                 = :soundCard,
                        `hardDrive`                 = :hardDrive,
                        `headphones`                = :headphones,
                        `mouse`                     = :mouse,
                        `mousePad`                  = :mousePad,
                        `keyboard`                  = :keyboard,
                        `monitor`                   = :monitor,
                        `avatar_id`                 = :avatar_id,
                        `subscribed_gaming_news`    = :dell_optin,
                        `subscribedAlienwareEvents` = :allow_contact
                        WHERE `id`=:userId';

        $createUserQuery = $dbh->prepare($createUserSql);
        $updateUserQuery = $dbh->prepare($updateUserSql);

        $iteration             = 0;
        $this->begunIterations = true;

        foreach ($this->failedUsers as $userData) {

            if ($this->exitAfterCurrentItem) {
                exit;
            }

            try {

                $iteration++;

                if (!isset($this->uuids[$iteration])) {
                    $this->error('[ '.$iteration.' ] - '.'NOT ENOUGH UUIDS LOADED. PLEASE ADD MORE TO THE END OF THE UUID FILE AND RESTART.', true);
                }

                $currentUuid = $this->uuids[$iteration];

                if ($this->debug) {
                    $this->output();
                }

                $this->output(2, 'Row '.$iteration.' [ '.$addedCount.' added, '.$updatedCount.' updated, '.$skippedCount.' skipped ] [ User => { Username = "'.$userData['username'].'", Email = "'.$userData['email'].'", Cevo ID = "'.$userData['cevoUserId'].'" } ]');

                if (empty($userData['email'])) {
                    if ($this->debug) {
                        $this->error('[ '.$iteration.' ] - '.'Email blank - skipping.');
                    }

                    $this->writeNonImportedUserCsvRow('Email blank - skipping.', $userData['dataRow']);
                    $skippedCount++;
                    continue;
                }

                $username          = $userData['username'];
                $usernameCanonical = $this->canonicalize($username);

                if ($username && $username != '') {

                    // update original users who were converted to "_new" on cevo import so that we can go ahead and
                    // insert the original CEVO user with the previously conflicting username
                    if (in_array($username.'_new', $this->newUsernames)) {

                        $newUsername          = $username.'_new';
                        $newCanonicalUsername = $this->canonicalize($newUsername);

                        $this->output(6, '[ ACTION ][ '.$iteration.' ] => Renaming DB user "'.$username.'" to "'.$newUsername.'"');

                        $this->writeUserModificationLogCsvRow(array(
                            $userData['cevoUserId'],
                            $userData['username'],
                            $userData['email'],
                            'Username conflicted with CNJP user - renamed to '.$userData['username'].'_new',
                        ));

                        /*$updateUsernameByUsernameQuery->execute(array(
                            'old_username_canonical' => $usernameCanonical,
                            'username'               => $newUsername,
                            'username_canonical'     => $newCanonicalUsername,
                        ));*/
                    }

                    $findUserByUsernameQuery->execute(array(
                        'username_canonical' => $usernameCanonical,
                    ));

                    $dbUserByUsername = $findUserByUsernameQuery->fetch();

                    // fix for diacritic conflicts, e.g. Thorn and Thörn
                    if ($dbUserByUsername && $dbUserByUsername['username_canonical'] != $usernameCanonical) {

                        //$this->output(6, '[ ACTION ][ '.$iteration.' ] => Setting username to null for "'.$username.'" ( conflicts with "'.$dbUserByUsername['username_canonical'].'" )');

                        //$this->error('Diacritical conflict for users "'.$dbUserByUsername['username_canonical'].'" and "'.$userData['username'].'"');
                        $this->writeNonImportedUserCsvRow('Diacritical conflict for users "'.$dbUserByUsername['username_canonical'].'" and "'.$userData['username'].'"', $userData['dataRow']);
                        $skippedCount++;
                        continue;

                        $this->writeUserModificationLogCsvRow(array(
                            $userData['cevoUserId'],
                            $userData['username'],
                            $userData['email'],
                            'Diacritical username conflict - username set to null',
                        ));

                        $userData['username']           = null;
                        $userData['username_canonical'] = null;
                    }

                } else {

                    $findUserByCevoUserIdQuery->execute(array(
                        'cevoUserId' => $userData['cevoUserId'],
                    ));

                    $dbUserByCevoId = $findUserByCevoUserIdQuery->fetch();

                    // if we have a username but CEVO don't, keep ours
                    if ($dbUserByCevoId && $dbUserByCevoId['username']) {

                        $this->output(6, '[ ACTION ][ '.$iteration.' ] => Keeping our username "'.$dbUserByCevoId['username'].'" for CEVO user without one ( CEVO User ID [ '.$userData['cevoUserId'].' ] )');

                        $this->writeUserModificationLogCsvRow(array(
                            $userData['cevoUserId'],
                            $dbUserByCevoId['username'],
                            $userData['email'],
                            'CEVO data contained no username - kept '.$dbUserByCevoId['username'].' from DB record',
                        ));

                        $userData['username']           = $dbUserByCevoId['username'];
                        $userData['username_canonical'] = $this->canonicalize($dbUserByCevoId['username']);
                    }
                }

                if ($this->debug) {
                    $this->outputUserData($userData);
                }

                $findUserByCevoUserIdQuery->execute(array(
                    'cevoUserId' => $userData['cevoUserId'],
                ));

                $user = $findUserByCevoUserIdQuery->fetch();

                if (!$user) {
                    $findUserByEmailQuery->execute(array(
                        ':email' => $userData['email_canonical'],
                    ));

                    $user = $findUserByEmailQuery->fetch();

                    if ($user && $user['cevoUserId'] && $user['cevoUserId'] !== $userData['cevoUserId']) {
                        $this->error('[ '.$iteration.' ] - '.'User => { Username = "'.$userData['username'].'", CEVO User ID = '.$userData['cevoUserId'].', Email = '.$userData['email_canonical'].' } matched an existing user, but with a different CEVO User ID('.$user['cevoUserId'].')');
                        $this->writeNonImportedUserCsvRow('Matched an existing user ('.$user['username'].'), but with a different CEVO User ID ('.$user['cevoUserId'].').', $userData['dataRow']);
                        $skippedCount++;
                        continue;
                    }
                }

                if ($user && !empty($user['uuid'])) {
                    $userUuid = $user['uuid'];
                } elseif (isset($this->cevoIdUuidMap[$userData['cevoUserId']])) {
                    $userUuid = $this->cevoIdUuidMap[$userData['cevoUserId']];
                } else {
                    $userUuid = $currentUuid;
                    //$this->writeCevoCsvRow(array($userData['cevoUserId'], $userUuid));
                }

                $lastUpdated    = new \DateTime();
                $avatarId       = null;

                if ($userData['avatar'] && isset($this->avatarUserMap[$userData['avatar']])) {
                    $avatarId = $this->avatarUserMap[$userData['avatar']]['avatarId'];
                }

                $sharedParams = array(
                    ":username"           => $userData['username'],
                    ":username_canonical" => $userData['username_canonical'],
                    ":password"           => $userData['password'],
                    ":uuid"               => $userUuid,
                    ":email"              => $userData['email'],
                    ":email_canonical"    => $userData['email_canonical'],
                    ":updated"            => $lastUpdated->format('Y-m-d'),
                    ":ipAddress"          => $userData['ipAddress'],
                    ":firstname"          => $userData['firstName'],
                    ":lastname"           => $userData['lastName'],
                    ":birthdate"          => $userData['birthDate'],
                    ":phone_number"       => $userData['phone'],
                    ":country"            => $userData['country'],
                    ":state"              => $userData['state'],
                    ":about_me"           => $userData['aboutMe'],
                    ":manufacturer"       => $userData['manufacturer'],
                    ":operatingSystem"    => $userData['os'],
                    ":cpu"                => $userData['cpu'],
                    ":memory"             => $userData['ram'],
                    ":videoCard"          => $userData['videoCard'],
                    ":soundCard"          => $userData['soundCard'],
                    ":hardDrive"          => $userData['hardDrive'],
                    ":headphones"         => $userData['headphones'],
                    ":mouse"              => $userData['mouse'],
                    ":mousePad"           => $userData['mousepad'],
                    ":keyboard"           => $userData['keyboard'],
                    ":monitor"            => $userData['monitor'],
                    ":avatar_id"          => $avatarId !== null ? (int)$avatarId : null,
                    ":allow_contact"      => $userData['allowContact'],
                    ":dell_optin"         => $userData['dellOptIn'],
                );

                if (!$user) {
                    if ($this->debug) {
                        $this->output();
                        $this->output(2, 'User not found - creating.');
                    }

                    $params = array_merge($sharedParams, array(
                        ":cevoUserId"         => $userData['cevoUserId'],
                        ":created"            => $userData['created'],
                    ));

                    /*$success = $createUserQuery->execute($params);

                    if (!$success) {

                        $errorInfo = $createUserQuery->errorInfo();

                        $dt = new \DateTime();

                        if (!$createUserQuery->errorCode() == "23000" || false === strpos($errorInfo[2], 'Duplicate entry')) {
                            $this->error('[ '.$iteration.' ] - '.'Error importing User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                            file_put_contents($this->logDir.'/failed_user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                            $this->writeNonImportedUserCsvRow($errorInfo[2], $userData['dataRow']);
                            $skippedCount++;
                            continue;
                        } else {
                            $this->error('[ '.$iteration.' ] - '.'Integrity constraint violation for User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                            file_put_contents($this->logDir.'/failed_user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                            $this->writeNonImportedUserCsvRow($errorInfo[2], $userData['dataRow']);
                            $skippedCount++;
                            continue;
                        }
                    }*/

                    $addedCount++;

                } else {

                    if (strtolower($user['username']) !== strtolower($userData['username']))  {

                        $this->output(6, '[ ACTION ][ '.$iteration.' ] => Keeping CEVO username "'.$userData['username'].'" for user with different DB username ( Our username [ '.$user['username'].' ] )');

                        $this->writeUserModificationLogCsvRow(array(
                            $userData['cevoUserId'],
                            $userData['username'],
                            $userData['email'],
                            'CEVO username did not match ours ('.$user['username'].') - kept CEVO username',
                        ));

                        $userData['username']           = $userData['username'];
                        $userData['username_canonical'] = $this->canonicalize($userData['username']);
                    }

                    if ($this->debug) {
                        $this->output();
                        $this->output(2, 'User found - updating records.');
                    }

                    $params = array_merge($sharedParams, array(':userId' => $user['id']));

                    /*$success = $updateUserQuery->execute($params);

                    if (!$success) {
                        $errorInfo = $updateUserQuery->errorInfo();

                        $dt = new \DateTime();

                        if (!$updateUserQuery->errorCode() == "23000" || false === strpos($errorInfo[2], 'Duplicate entry')) {
                            $this->error('[ '.$iteration.' ] - '.'Error importing User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                            file_put_contents($this->logDir.'/failed_user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                            $this->writeNonImportedUserCsvRow($errorInfo[2], $userData['dataRow']);
                            $skippedCount++;
                            continue;
                        } else {
                            $this->error('[ '.$iteration.' ] - '.'Integrity constraint violation for User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                            file_put_contents($this->logDir.'/failed_user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                            $this->writeNonImportedUserCsvRow($errorInfo[2], $userData['dataRow']);
                            $skippedCount++;
                            continue;
                        }
                    }*/

                    $updatedCount++;
                }

            } catch (PDOException $e) {
                $this->error('[ '.$iteration.' ] - '.'Connection failed at iteration ['.$iteration.']: ' . $e->getMessage());
                $skippedCount++;
                sleep(5);
            } catch (Exception $e) {
                $this->error('[ '.$iteration.' ] - '.$e->getMessage());
                $this->writeNonImportedUserCsvRow($e->getMessage(), $userData['dataRow']);
                $skippedCount++;
            }
        }

        $this->output();
        $this->output(2, 'No more users.');

        $this->outputErrors();

        $this->output(0);
    }
}
