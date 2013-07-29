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

$cmd = new CevoUserImportCommand();
$cmd->execute($path, $debug);

class CevoUserImportCommand
{
    private $directory;
    private $cevoIdUuidMap        = array();
    private $avatarUserMap        = array();
    private $usersAvatars         = array();
    private $debug                = false;
    private $errors               = array();
    private $begunIterations      = false;
    private $exitAfterCurrentItem = false;
    private $uuids                = array();

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

        file_put_contents(rtrim($this->directory, '/').'/../user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$message."\n", FILE_APPEND);

        if ($exit) {
            $this->outputErrors();
            exit;
        }

        $this->errors[] = $message;
    }

    protected function loadUuids()
    {
        $filepath = rtrim($this->directory, '/').'/../user_map_files/uuid_stash.csv';

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
        $this->writeCsvRow(rtrim($this->directory, '/').'/../user_map_files/cevo_user_id_uuid_map.csv' , $rowData);
        $this->cevoIdUuidMap[$rowData[0]] = $rowData[1];
    }

    protected function writeNonImportedUserCsvRow($reason, array $rowData)
    {
        $rowData[] = $reason;
        $this->writeCsvRow(rtrim($this->directory, '/').'/../non_imported_users.csv' , $rowData);
    }

    protected function readCevoMapCsv()
    {
        $filepath = rtrim($this->directory, '/').'/../user_map_files/cevo_user_id_uuid_map.csv';

        if (!file_exists($filepath)) {
            return;
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
        $filepath = rtrim($this->directory, '/').'/../user_map_files/user_avatar_map.csv';

        if (!file_exists($filepath)) {
            return;
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

        $dsn        = 'mysql:dbname=;host=';
        $dbUser     = '';
        $dbPassword = '';
        $db         = '';

        $addedCount   = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        $this->output(0);
        $this->output(0, 'PlatformD CEVO User Importer');
        $this->output(0);

        $this->directory = dirname($path);

        $this->output(0, 'Reading CEVO user UUID map data.');
        $this->readCevoMapCsv();

        $this->output(0, 'Reading avatar map data.');
        $this->readAvatarMapCsv();

        $this->output(0, 'Reading UUID stash into memory.');
        $this->loadUuids();

        $this->output(0, 'Setting up database connection.');

        try {
            $dbh = new PDO($dsn, $dbUser, $dbPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        } catch (PDOException $e) {
            $this->error('Connection failed: ' . $e->getMessage(), true);
        }

        $this->output(0, 'Processing users in [ '.$path.' ].');

        $findUserByEmailSql    = 'SELECT `id`, `cevoUserId`, `uuid`, `username` FROM `'.$db.'`.`fos_user` WHERE `email_canonical` = :email';
        $findUserByEmailQuery  = $dbh->prepare($findUserByEmailSql);

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

        $iteration = -1;

        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 100000, ",")) !== FALSE && !$this->exitAfterCurrentItem) {

                $this->begunIterations = true;

                try {

                    $iteration++;

                    if (!isset($this->uuids[$iteration])) {
                        $this->error('NOT ENOUGH UUIDS LOADED. PLEASE ADD MORE TO THE END OF THE UUID FILE AND RESTART.', true);
                    }

                    $currentUuid = $this->uuids[$iteration];

                    // bypass header row
                    if ($iteration == 0) {
                        continue;
                    }

                    if ($this->debug) {
                        $this->output();
                    }

                    $this->output(2, 'Row '.$iteration.' [ '.$addedCount.' added, '.$updatedCount.' updated, '.$skippedCount.' skipped ]');

                    $createdDt   = \DateTime::createFromFormat('U', $data[6]);
                    $birthdateDt = \DateTime::createFromFormat('Y-m-d', $data[13]);
                    $fallbackDt  = new \DateTime();

                    $userData['cevoUserId']         = $data[0];
                    $userData['username']           = $data[1] != '' ? $data[1] : null;
                    $userData['password']           = $data[2];
                    $userData['email']              = $data[3];
                    $userData['email_canonical']    = $this->canonicalize($data[3]);
                    $userData['created']            = $createdDt ? $createdDt->format('Y-m-d') : $fallbackDt->format('Y-m-d');
                    $userData['ipAddress']          = $data[7] != '' ? $data[7] : null;
                    $userData['origin']             = $data[8];
                    $userData['firstName']          = $data[11];
                    $userData['lastName']           = $data[12];
                    $userData['birthDate']          = $birthdateDt ? $birthdateDt->format('Y-m-d') : null;
                    $userData['phone']              = $data[14] != '' ? $data[14] : null;
                    $userData['country']            = $data[16] != '' ? $data[16] : null;
                    $userData['state']              = $data[17] != '' ? $data[17] : null;
                    $userData['allowContact']       = $data[25] != '' ? $data[25] : 0;
                    $userData['dellOptIn']          = $data[26] != '' ? $data[26] : 0;
                    $userData['avatar']             = $data[35] != '' && $data[35] !== '0' ? $data[35] : null;
                    $userData['aboutMe']            = $data[36] != '' ? $data[36] : null;
                    $userData['manufacturer']       = $data[38] != '' ? $data[38] : null;
                    $userData['os']                 = $data[39] != '' ? $data[39] : null;
                    $userData['cpu']                = $data[40] != '' ? $data[40] : null;
                    $userData['ram']                = $data[41] != '' ? $data[41] : null;
                    $userData['hardDrive']          = $data[42] != '' ? $data[42] : null;
                    $userData['videoCard']          = $data[43] != '' ? $data[43] : null;
                    $userData['soundCard']          = $data[44] != '' ? $data[44] : null;
                    $userData['headphones']         = $data[45] != '' ? $data[45] : null;
                    $userData['monitor']            = $data[46] != '' ? $data[46] : null;
                    $userData['mouse']              = $data[47] != '' ? $data[47] : null;
                    $userData['mousepad']           = $data[48] != '' ? $data[48] : null;
                    $userData['keyboard']           = $data[49] != '' ? $data[49] : null;

                    if (empty($userData['email'])) {
                        if ($this->debug) {
                            $this->output(4, 'Email blank - skipping.');
                        }

                        $this->writeNonImportedUserCsvRow('Email blank', $data);
                        $skippedCount++;
                        continue;
                    }

                    if (substr($userData['username'], -4) == '_new' && $userData['origin'] == 'cnjp') {
                        $this->error('User has "_new" in username - skipping. User => { Email = "'.$userData['email'].'" }');
                        $this->writeNonImportedUserCsvRow('CNJP _new user', $data);
                        $skippedCount++;
                        continue;
                    }

                    $userData['username_canonical'] = $userData['username'] ? $this->canonicalize($userData['username']) : null;

                    if ($this->debug) {
                        $this->outputUserData($userData);
                    }

                    $findUserByEmailQuery->execute(array(
                        ':email' => $userData['email_canonical'],
                    ));

                    $user = $findUserByEmailQuery->fetch();

                    if ($user && $user['cevoUserId'] && $user['cevoUserId'] !== $userData['cevoUserId']) {
                        $this->error('User => { Email = '.$userData['email_canonical'].' } matched an existing user, but with a different CEVO User ID.');
                        $this->writeNonImportedUserCsvRow('User matched existing with different CEVO User ID', $data);
                        $skippedCount++;
                        continue;
                    }

                    if ($user && !empty($user['uuid'])) {
                        $userUuid = $user['uuid'];
                    } elseif (isset($this->cevoIdUuidMap[$userData['cevoUserId']])) {
                        $userUuid = $this->cevoIdUuidMap[$userData['cevoUserId']];
                    } else {
                        $userUuid = $currentUuid;
                        $this->writeCevoCsvRow(array($userData['cevoUserId'], $userUuid));
                    }

                    $lastUpdated    = new \DateTime();
                    $avatarId       = null;

                    if ($userData['avatar'] && isset($this->avatarUserMap[$userData['avatar']])) {
                        $avatarId = $this->avatarUserMap[$userData['avatar']]['avatarId'];
                    }

                    $sharedParams = array(
                        ":password"        => $userData['password'],
                        ":uuid"            => $userUuid,
                        ":email"           => $userData['email'],
                        ":email_canonical" => $userData['email_canonical'],
                        ":updated"         => $lastUpdated->format('Y-m-d'),
                        ":ipAddress"       => $userData['ipAddress'],
                        ":firstname"       => $userData['firstName'],
                        ":lastname"        => $userData['lastName'],
                        ":birthdate"       => $userData['birthDate'],
                        ":phone_number"    => $userData['phone'],
                        ":country"         => $userData['country'],
                        ":state"           => $userData['state'],
                        ":about_me"        => $userData['aboutMe'],
                        ":manufacturer"    => $userData['manufacturer'],
                        ":operatingSystem" => $userData['os'],
                        ":cpu"             => $userData['cpu'],
                        ":memory"          => $userData['ram'],
                        ":videoCard"       => $userData['videoCard'],
                        ":soundCard"       => $userData['soundCard'],
                        ":hardDrive"       => $userData['hardDrive'],
                        ":headphones"      => $userData['headphones'],
                        ":mouse"           => $userData['mouse'],
                        ":mousePad"        => $userData['mousepad'],
                        ":keyboard"        => $userData['keyboard'],
                        ":monitor"         => $userData['monitor'],
                        ":avatar_id"       => $avatarId !== null ? (int)$avatarId : null,
                        ":allow_contact"   => $userData['allowContact'],
                        ":dell_optin"      => $userData['dellOptIn'],
                    );

                    if (!$user) {
                        if ($this->debug) {
                            $this->output();
                            $this->output(2, 'User not found - creating.');
                        }

                        $params = array_merge($sharedParams, array(
                            ":username"           => $userData['username'],
                            ":username_canonical" => $userData['username_canonical'],
                            ":cevoUserId"         => $userData['cevoUserId'],
                            ":created"            => $userData['created'],
                        ));

                        $success = $createUserQuery->execute($params);

                        if (!$success) {

                            $errorInfo = $createUserQuery->errorInfo();

                            $dt = new \DateTime();

                            if (!$createUserQuery->errorCode() == "23000" || false === strpos($errorInfo[2], 'Duplicate entry')) {
                                $this->error('Error importing User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                                $this->writeNonImportedUserCsvRow('MySQL error [ '.$createUserQuery->errorCode().' ]', $data);
                                file_put_contents(rtrim($this->directory, '/').'/../user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                                $skippedCount++;
                                continue;
                            } else {
                                $this->error('Integrity constraint violation for User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                                $this->writeNonImportedUserCsvRow('MySQL error [ '.$createUserQuery->errorCode().' ]', $data);
                                file_put_contents(rtrim($this->directory, '/').'/../user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                                $skippedCount++;
                                continue;
                            }
                        }

                        $addedCount++;

                    } else {

                        if (strtolower($user['username']) !== strtolower($userData['username']))  {
                            $this->error('User => { Cevo User ID = '.$userData['cevoUserId'].', Username = "'.$userData['username'].'" } matched an existing user, but with a different username [ "'.$user['username'].'" ]');
                            $this->writeNonImportedUserCsvRow('User matched existing with different username ['.$user['username'].']', $data);
                            $skippedCount++;
                            continue;
                        }

                        if ($this->debug) {
                            $this->output();
                            $this->output(2, 'User found - updating records.');
                        }

                        $params = array_merge($sharedParams, array(':userId' => $user['id']));

                        $success = $updateUserQuery->execute($params);

                        if (!$success) {
                            $errorInfo = $updateUserQuery->errorInfo();

                            $dt = new \DateTime();

                            if (!$updateUserQuery->errorCode() == "23000" || false === strpos($errorInfo[2], 'Duplicate entry')) {
                                $this->error('Error importing User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                                $this->writeNonImportedUserCsvRow('MySQL error [ '.$updateUserQuery->errorCode().' ]', $data);
                                file_put_contents(rtrim($this->directory, '/').'/../user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                                $skippedCount++;
                                continue;
                            } else {
                                $this->error('Integrity constraint violation for User => { Username="'.$userData['username'].'", Email = "'.$userData['email'].'" }');
                                $this->writeNonImportedUserCsvRow('MySQL error [ '.$updateUserQuery->errorCode().' ]', $data);
                                file_put_contents(rtrim($this->directory, '/').'/../user_import_errors.log', '[ '.$dt->format('Y-m-d H:i:s').' ] - '.$errorInfo[2]."\n", FILE_APPEND);
                                $skippedCount++;
                                continue;
                            }
                        }

                        $updatedCount++;
                    }

                    if (isset($this->usersAvatars[$userUuid])) {

                        if ($this->debug) {
                            $this->output();
                            $this->output(2, 'Setting user avatars.');
                        }

                        $ids = '';

                        foreach ($this->usersAvatars[$userUuid] as $id) {
                            $ids .= ','.$id;
                        }

                        if (!empty($ids)) {
                            $ids = ltrim($ids, ',');

                            $updateAvatarSql = 'UPDATE `'.$db.'`.`pd_avatar` SET `user_id`=:userId WHERE `id` IN ('.$ids.')';

                            $query = $dbh->prepare($updateAvatarSql);
                            $query->execute(array(':userId' => $user['id']));
                        }
                    }

                } catch (PDOException $e) {
                    $this->error('Connection failed at iteration ['.$iteration.']: ' . $e->getMessage());
                    $this->writeNonImportedUserCsvRow('PDOException caught', $data);
                    $skippedCount++;
                    sleep(5);
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                    $this->writeNonImportedUserCsvRow($e->getMessage(), $data);
                    $skippedCount++;
                }
            }
        }

        $this->output();
        $this->output(2, 'No more users.');

        $this->outputErrors();

        $this->output(0);
    }
}
