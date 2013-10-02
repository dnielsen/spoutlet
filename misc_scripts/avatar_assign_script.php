<?php

if (!isset($argv[1])) {
    echo 'Argument 1 passed to this script must be the path to the avatar map file.'."\n";
    exit;
}

$mapPath = $argv[1];

if (!file_exists($mapPath)) {
    echo 'Path [ '.$mapPath.' ] is not a valid path'."\n";
    exit;
}

$debug = false;

if (isset($argv[2]) && $argv[2] == '--debug') {
    $debug = true;
}

$cmd = new AvatarAssigning();
$cmd->execute($mapPath, $debug);

class AvatarAssigning
{
    private $mapPath;
    private $usersAvatars         = array();
    private $debug                = false;
    private $errors               = array();
    private $begunIterations      = false;
    private $exitAfterCurrentItem = false;

    protected function output($indentationLevel = 0, $message = null, $withNewLine = true) {

        if ($message === null) {
            $message = '';
        }

        echo(str_repeat(' ', $indentationLevel).$message.($withNewLine ? "\n" : ''));
    }

    protected function error($message, $exit = false)
    {
        $this->output(0);
        $this->output(0, $message);
        $this->output(0);

        $dt = new \DateTime();

        if ($exit) {
            $this->outputErrors();
            exit;
        }

        $this->errors[] = $message;
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

    protected function readAvatarMapCsv()
    {
        if (!file_exists($this->mapPath)) {
            return;
        }

        if (($handle = fopen($this->mapPath, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (isset($data[0]) && isset($data[1]) && isset($data[2]) && isset($data[3])) {
                    $this->usersAvatars[$data[1]][] = $data[3];
                } else {
                    $this->error('Avatar CSV row ignored as all values were not present.');
                }
            }

            fclose($handle);
        }
    }

    public function execute($mapPath, $debug = false)
    {
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'signal_handler'));
        pcntl_signal(SIGINT, array($this, 'signal_handler'));

        $this->debug   = $debug;
        $this->mapPath = $mapPath;

        $dsn        = 'mysql:dbname=alienware_production;host=alienwaredb.cix3pdvfa70g.us-east-1.rds.amazonaws.com';
        $dbUser     = 'alienwaremaster';
        $dbPassword = 'f78284q9vL2B5n6';
        $db         = 'alienware_production';

        $updatedCount = 0;
        $skippedCount = 0;

        $this->output(0);
        $this->output(0, 'PlatformD CEVO Avatar Assign Script');
        $this->output(0);

        $this->directory = dirname($mapPath);

        $this->output(0, 'Reading avatar map data.');
        $this->readAvatarMapCsv();

        $this->output(0, 'Setting up database connection.');

        try {
            $dbh = new PDO($dsn, $dbUser, $dbPassword, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        } catch (PDOException $e) {
            $this->error('Connection failed: ' . $e->getMessage(), true);
        }

        $this->output(0, 'Processing users with avatars.');
        $this->output();

        $findUserByUuidSql    = 'SELECT `id`, `uuid` FROM `'.$db.'`.`fos_user` WHERE `uuid` = :uuid';
        $findUserByUuidQuery  = $dbh->prepare($findUserByUuidSql);

        $updateAvatarSql    = 'UPDATE `'.$db.'`.`pd_avatar` SET `user_id`=:userId WHERE `id` = :id';
        $updateAvatarQuery  = $dbh->prepare($updateAvatarSql);

        $iteration = -1;
        $this->begunIterations = true;

        foreach ($this->usersAvatars as $userUuid => $avatars) {

            try {

                if ($this->exitAfterCurrentItem) {
                    $this->outputErrors();
                    exit;
                }

                $iteration++;

                $this->output(0, 'Iteration '.$iteration.' [ Updated '.$updatedCount.', Skipped '.$skippedCount.' ]');

                if (count($avatars) < 1) {
                    $this->output(2, 'No avatars for user - skipping.');
                    $skippedCount++;
                    continue;
                }

                if ($this->debug) {
                    $this->output(2, 'Processing User => { UUID = "'.$userUuid.'", Avatar Count = '.count($avatars).' }');
                }

                $findUserByUuidQuery->execute(array(
                    ':uuid' => $userUuid,
                ));

                $user = $findUserByUuidQuery->fetch();

                if (!$user) {
                    $this->error('User [ UUID = "'.$userUuid.'" ] not found - skipping.');
                    $skippedCount++;
                    continue;
                }

                $userId = $user['id'];

                foreach ($avatars as $avatarId) {
                    if ($this->debug) {
                        $this->output(4, 'Avatar => { Id = '.$avatarId.', User ID => '.$userId.', User UUID = "'.$user['uuid'].'" }');
                    }

                    $updateAvatarQuery->execute(array(':userId' => $userId, ':id' => $avatarId));
                    $updatedCount++;
                }

            } catch (\PDOException $e) {
                $this->error('Connection failed at iteration ['.$iteration.']: ' . $e->getMessage());
                $skippedCount++;
                sleep(5);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                $skippedCount++;
            }
        }

        $this->output();
        $this->output(2, 'No more users.');

        $this->outputErrors();

        $this->output(0);
    }
}
