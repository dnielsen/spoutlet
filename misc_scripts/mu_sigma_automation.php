<?php

include '/home/ubuntu/scripts/mu-sigma_automation/config.php';

$ftpInfo        = array($ftpServer, $ftpUser, $ftpPassword);

$sinceDate      = new DateTime('yesterday 23:59:59');
$sinceDate      = $sinceDate->modify('-1 year');
$sinceDate      = $sinceDate->format("Y-m-d H:i:s");

$filenameDate   = new DateTime('now');
$filenameDate   = $filenameDate->format('YmdHis');

$filesForUpload = array();

$output         = "";

$output .= "\n---------------------------------------------\n";
$output .= "Alienware Arena Mu-Sigma Weekly Report Upload\n";
$output .= "---------------------------------------------\n";

// $output .= "\nCreating SSH Tunnel to AWA Server...\n" ;

// Not needed for production use - used when performing local testing on dev machine to create tunnel to AWA server for MySQL access.
// shell_exec('ssh ubuntu@ec2-107-22-29-29.compute-1.amazonaws.com -f -L 33336:alienwaredb.cix3pdvfa70g.us-east-1.rds.amazonaws.com:3306 sleep 30 >> logfile');

try {
    $dbh            = new PDO($dsn, $user, $password);

// Giveaways

    $output .= "\nProcessing Giveaway Data\n";

    $filename   = 'alienwarearena_giveaway_data_'.$filenameDate.'.csv';

    $sql        = 'SELECT `giveaway_key`.`assigned_at`,`fos_user`.`username`, `fos_user`.`id` as user_id, `fos_user`.`firstname`, `fos_user`.`lastname`, `fos_user`.`email`, `fos_user`.`country`, `event`.`name` as name
                    FROM `'.$awaDb.'`.`fos_user`
                    LEFT JOIN `'.$awaDb.'`.`giveaway_key` ON `fos_user`.`id` = `giveaway_key`.`user`
                    LEFT JOIN `'.$awaDb.'`.`giveaway_pool` ON `giveaway_key`.`pool` = `giveaway_pool`.`id`
                    LEFT JOIN `'.$awaDb.'`.`event` ON `giveaway_pool`.`giveaway_id` = `event`.`id`
                    WHERE `giveaway_key`.`assigned_at` >= "'.$sinceDate.'"';

    $data       = $dbh->query($sql);

    $headers    = array(
        'Key Assigned On' => 'assigned_at',
        'Username' => 'username',
        'User ID' => 'user_id',
        'First Name' => 'firstname',
        'Last Name' => 'lastname',
        'Email' => 'email',
        'Country' => 'country',
        'Key Giveaway Name' => 'name',
        'Week' => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient);
    $filesForUpload[] = $filename.'.gz.asc';

// Deals

    $output .= "\nProcessing Deal Data\n";

    $filename = 'alienwarearena_deals_data_'.$filenameDate.'.csv';

    $sql = 'SELECT `deal_code`.`assigned_at`,`fos_user`.`username`, `fos_user`.`id` as user_id, `fos_user`.`firstname`, `fos_user`.`lastname`, `fos_user`.`email`, `fos_user`.`country`, `pd_deal`.`name` as name
                    FROM `'.$awaDb.'`.`fos_user`
                    LEFT JOIN `'.$awaDb.'`.`deal_code` ON `fos_user`.`id` = `deal_code`.`user`
                    LEFT JOIN `'.$awaDb.'`.`deal_pool` ON `deal_code`.`pool` = `deal_pool`.`id`
                    LEFT JOIN `'.$awaDb.'`.`pd_deal` ON `deal_pool`.`deal_id` = `pd_deal`.`id`
                    WHERE `deal_code`.`assigned_at` >= "'.$sinceDate.'"';

    $data = $dbh->query($sql);

     $headers = array(
        'Key Assigned On' => 'assigned_at',
        'Username' => 'username',
        'User ID' => 'user_id',
        'First Name' => 'firstname',
        'Last Name' => 'lastname',
        'Email' => 'email',
        'Country' => 'country',
        'Deal Name' => 'name',
        'Week' => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient);
    $filesForUpload[] = $filename.'.gz.asc';

} catch (PDOException $e) {
    $output .= 'Connection failed: ' . $e->getMessage();
}



$dbh = null;

$output .= "\nCreating SSH Tunnel to PLDX Server...\n" ;

// create ssh tunnel to pldx db server for establishing MySQL connection
shell_exec('ssh root@173.193.20.145 -f -L 33337:localhost:3306 sleep 30 >> logfile');

try {
    $dbh            = new PDO($dsnPldx, $userPldx, $passwordPldx);

// Video Comments

    $output .= "\nProcessing Video Comments Data\n";

    $filename   = 'alienwarearena_video_comments_data_'.$filenameDate.'.csv';

    $sql        = 'SELECT `movies`.`id`,  `movies`.`name`, `movies`.`views`, REPLACE(`movie_comments`.`comment`, CHAR(10), "") as `comment`, `movie_comments`.`authorid`, `movie_comments`.`posted`
                    FROM `'.$videoDb.'`.`movie_comments`
                    INNER JOIN `'.$videoDb.'`.`movies` ON `movies`.`id` = `movie_comments`.`movieid`
                    WHERE `movies`.`added` >= "'.$sinceDate.'" AND `movies`.`status` = 1
                    ORDER BY `movies`.`id`';

    $data       = $dbh->query($sql);

    $headers    = array(
        'Video ID' => 'id',
        'Video Name' => 'name',
        'Views' => 'views',
        'Comment' => 'comment',
        'User ID' => 'authorid',
        'Commented At' => 'posted',
        'Week' => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient);
    $filesForUpload[] = $filename.'.gz.asc';

// Video Summary

    $output .= "\nProcessing Video Summary Data\n";

    $filename   = 'alienwarearena_video_summary_data_'.$filenameDate.'.csv';

    $sql        = 'SELECT DISTINCT `movies`.`id` as movie_id, `movies`.`name` AS title,  `movie_category`.`name` AS category, `movies`.`views` AS total_views,  `movies`.`added` AS date_uploaded, `movies`.`submitterid`,
                    (SELECT COUNT(*) FROM `movie_comments` where `movie_comments`.`movieid` = movie_id) as total_comments,
                    (SELECT AVG(`rating`) FROM `movie_ratings` WHERE `movieid` = movie_id) as rating, `files`.`ip`
                    FROM `'.$videoDb.'`.`movies`
                    INNER JOIN `'.$videoDb.'`.`movie_category` ON `movie_category`.`id` = `movies`.`categoryid`
                    INNER JOIN `'.$videoDb.'`.`files` ON `files`.`movieid` = `movies`.`id`
                    WHERE  `movies`.`added` > "'.$sinceDate.'" AND `movies`.`status` = 1 AND `files`.`ip` IS NOT NULL
                    ORDER BY `movies`.`added`';

    $data       = $dbh->query($sql);

    $headers    = array(
        'Video ID' => 'movie_id',
        'Video Name' => 'title',
        'Category' => 'category',
        'Views' => 'total_views',
        'Date Uploaded' => 'date_uploaded',
        'User ID' => 'submitterid',
        'Number of Comments' => 'total_comments',
        'Rating' => 'rating',
        'IP Address' => 'ip',
        'Week' => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient);
    $filesForUpload[] = $filename.'.gz.asc';

} catch (PDOException $e) {
    $output .= 'Connection failed: ' . $e->getMessage();
}

// Upload files to MuSigma

    $output .= uploadFiles($filesForUpload, $ftpInfo);
    unlink('logfile');

    $output .= "\nFinished report uploads\n\n";

    echo $output;

function generateEncryptedGzippedCsv($headers, $data, $filename, $recipient)
{
    $output = " - Generating ".$filename."...\n";

    $csvData        = array();
    $headings       = array();
    $weekString     = 'CY'.date('o').'W'.date('W');

    foreach ($headers as $heading => $field) {
       $headings[] = $heading;
    }

    $csvData[] = $headings;

    foreach ($data as $row) {
        $rowData = array();

        foreach ($headers as $heading => $field) {
            if ($field) {
                $rowData[] = $row[$field];
            } else {
                $rowData[] = $weekString;
            }
        }

        $csvData[] = $rowData;
    }

    if (($handle = fopen($filename, 'w')) !== FALSE) {
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
    }

    fclose($handle);

    $output .= gzipFile($filename);

    $output .= " - Encrypting ".$filename.".gz...\n";

    exec('gpg -ea --batch --always-trust -r "'.$recipient.'" '.$filename.'.gz');

    if (file_exists($filename.'.gz.asc')) {
        unlink($filename.'.gz');
    }

    return $output;
}

function gzipFile($file)
{
    $gzFile = $file.".gz";

    $output = " - Generating ".$gzFile."...\n";

    // Open the gz file (w9 is the highest compression)
    if (($handle = gzopen($gzFile, 'w9')) !== FALSE) {

        // Write and close gzip file
        gzwrite ($handle, file_get_contents($file));
        gzclose($handle);

        // Remove original file
        unlink($file);
    }

    return $output;
}

function uploadFiles($filesArray, $ftpInfo)
{
    $output = "\nUploading to FTP Server\n";

    $remotePath = '[DO NOT DELETE]awa_automated_weekly_reports';

    foreach ($filesArray as $file) {

        $output .= " - Uploading ".$file."...\n";

        exec('ftp-upload -h '.$ftpInfo[0].' -u '.$ftpInfo[1].' --password '.$ftpInfo[2].' --passive -d "'.$remotePath.'" '.$file);
        unlink($file);
    }

    return $output;
}

?>
