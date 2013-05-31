<?php

include '/home/ubuntu/scripts/mu-sigma_automation/config.php';

$ftpInfo        = array($ftpServer, $ftpUser, $ftpPassword);

$week           = isset($argv[1]) ? str_pad($argv[1], 2, '0', STR_PAD_LEFT) : date('W');
$weekDate       = date("Y-m-d H:i:s", strtotime("2013-W".$week."-1 23:59:59"));
$weekDate       = new DateTime($weekDate);

$toDate         = clone $weekDate;
$toDate         = $toDate->modify('-1 day');

$sinceDate      = clone $toDate;
$sinceDate      = $sinceDate->modify('-1 year');

$toDate         = $toDate->format('Y-m-d H:i:s');
$sinceDate      = $sinceDate->format("Y-m-d H:i:s");

$filenameDate   = clone $weekDate;
$filenameDate   = $filenameDate->setTime(15,0,0);
$filenameDate   = $filenameDate->format('YmdHis');

$csvYear        = $weekDate->format('o');

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

    $sql = 'SET NAMES utf8';
    $result = $dbh->prepare($sql)->execute();

// Giveaways

    $output .= "\nProcessing Giveaway Data\n";

    $filename   = 'alienwarearena_giveaway_data_'.$filenameDate.'.csv';

    $sql        = 'SELECT `giveaway_key`.`assigned_at`,`fos_user`.`username`, `fos_user`.`id` as user_id, `fos_user`.`firstname`, `fos_user`.`lastname`, `fos_user`.`email`, `fos_user`.`country`, `event`.`name` as name
                    FROM `'.$awaDb.'`.`fos_user`
                    LEFT JOIN `'.$awaDb.'`.`giveaway_key` ON `fos_user`.`id` = `giveaway_key`.`user`
                    LEFT JOIN `'.$awaDb.'`.`giveaway_pool` ON `giveaway_key`.`pool` = `giveaway_pool`.`id`
                    LEFT JOIN `'.$awaDb.'`.`event` ON `giveaway_pool`.`giveaway_id` = `event`.`id`
                    WHERE `giveaway_key`.`assigned_at` >= "'.$sinceDate.'" AND `giveaway_key`.`assigned_at` <= "'.$toDate.'"';

    $data       = $dbh->query($sql);

    $headers    = array(
        'Key Assigned On'   => 'assigned_at',
        'Username'          => 'username',
        'User ID'           => 'user_id',
        'First Name'        => 'firstname',
        'Last Name'         => 'lastname',
        'Email'             => 'email',
        'Country'           => 'country',
        'Key Giveaway Name' => 'name',
        'Week'              => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient, $csvYear, $week);
    $filesForUpload[] = $filename.'.gz.asc';

// Deals

    $output .= "\nProcessing Deal Data\n";

    $filename = 'alienwarearena_deals_data_'.$filenameDate.'.csv';

    $sql = 'SELECT `deal_code`.`assigned_at`,`fos_user`.`username`, `fos_user`.`id` as user_id, `fos_user`.`firstname`, `fos_user`.`lastname`, `fos_user`.`email`, `fos_user`.`country`, `pd_deal`.`name` as name
                    FROM `'.$awaDb.'`.`fos_user`
                    LEFT JOIN `'.$awaDb.'`.`deal_code` ON `fos_user`.`id` = `deal_code`.`user`
                    LEFT JOIN `'.$awaDb.'`.`deal_pool` ON `deal_code`.`pool` = `deal_pool`.`id`
                    LEFT JOIN `'.$awaDb.'`.`pd_deal` ON `deal_pool`.`deal_id` = `pd_deal`.`id`
                    WHERE `deal_code`.`assigned_at` >= "'.$sinceDate.'" AND `deal_code`.`assigned_at` <= "'.$toDate.'"';

    $data = $dbh->query($sql);

     $headers = array(
        'Key Assigned On' => 'assigned_at',
        'Username'        => 'username',
        'User ID'         => 'user_id',
        'First Name'      => 'firstname',
        'Last Name'       => 'lastname',
        'Email'           => 'email',
        'Country'         => 'country',
        'Deal Name'       => 'name',
        'Week'            => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient, $csvYear, $week);
    $filesForUpload[] = $filename.'.gz.asc';

// Video Comments

    $output .= "\nProcessing Video Comments Data\n";

    $filename   = 'alienwarearena_video_comments_data_'.$filenameDate.'.csv';

    $sql        = 'SELECT `v`.`id`, `v`.`title`, `v`.`views`, REPLACE(`c`.`body`, CHAR(10), "") as `comment`, `c`.`author_id`, `c`.`created_at`
                    FROM `'.$awaDb.'`.`commenting_comment` `c`
                    INNER JOIN `'.$awaDb.'`.`commenting_thread` `t` ON `c`.`thread_id` = `t`.`id`
                    INNER JOIN `'.$awaDb.'`.`pd_videos_youtube` `v` ON `t`.`id` = CONCAT("youtube-", CAST(`v`.`id` AS CHAR))
                    WHERE `c`.`created_at` >= "'.$sinceDate.'" AND `c`.`created_at` <= "'.$toDate.'" AND `v`.`deleted` = 0
                    ORDER BY `v`.`id`';

    $data       = $dbh->query($sql);

    $headers    = array(
        'Video ID'     => 'id',
        'Video Name'   => 'title',
        'Views'        => 'views',
        'Comment'      => 'comment',
        'User ID'      => 'author_id',
        'Commented At' => 'created_at',
        'Week'         => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient, $csvYear, $week);
    $filesForUpload[] = $filename.'.gz.asc';

// Video Summary

    $output .= "\nProcessing Video Summary Data\n";

    $filename   = 'alienwarearena_video_summary_data_'.$filenameDate.'.csv';

    $sql        = 'SELECT DISTINCT `v`.`id` AS movie_id, `v`.`title`, `g`.`name` AS category, `v`.`views` AS total_views, `v`.`created_at`,
                    `v`.`author_id`,
                    (SELECT COUNT(*) FROM `'.$awaDb.'`.`commenting_comment` `c`
                        INNER JOIN `'.$awaDb.'`.`commenting_thread` `t` ON `t`.`id` = `c`.`thread_id`
                        WHERE `t`.`id` = CONCAT("youtube-", CAST(movie_id AS CHAR))) as total_comments,
                    COALESCE((SELECT SUM(IF(`v1`.`vote_type` = "up", 1, -1)) AS rating FROM `'.$awaDb.'`.`pd_youtube_votes` v1
                        WHERE `v1`.`video_id` = movie_id), 0) AS rating,
                    "" AS ip
                    FROM `'.$awaDb.'`.`pd_videos_youtube` `v`
                    INNER JOIN `'.$awaDb.'`.`pd_videos_youtube_galleries` `g2` ON `g2`.`youtubevideo_id` = `v`.`id`
                    INNER JOIN `'.$awaDb.'`.`pd_gallery` `g` ON `g`.`id` = `g2`.`gallery_id`
                    WHERE `v`.`created_at` >= "'.$sinceDate.'" AND `v`.`created_at` <= "'.$toDate.'" AND `v`.`deleted` = 0
                    ORDER BY `v`.`created_at`';


    $data       = $dbh->query($sql);

    $headers    = array(
        'Video ID'           => 'movie_id',
        'Video Name'         => 'title',
        'Category'           => 'category',
        'Views'              => 'total_views',
        'Date Uploaded'      => 'created_at',
        'User ID'            => 'author_id',
        'Number of Comments' => 'total_comments',
        'Rating'             => 'rating',
        'IP Address'         => 'ip',
        'Week'               => null,
    );

    $output .= generateEncryptedGzippedCsv($headers, $data, $filename, $recipient, $csvYear, $week);
    $filesForUpload[] = $filename.'.gz.asc';

} catch (PDOException $e) {
    $output .= 'Connection failed: ' . $e->getMessage();
}

// Upload files to MuSigma

    $output .= uploadFiles($filesForUpload, $ftpInfo);
    unlink('logfile');

    $output .= "\nFinished report uploads\n\n";

    echo $output;

function generateEncryptedGzippedCsv($headers, $data, $filename, $recipient, $csvYear, $week)
{
    $output = " - Generating ".$filename."...\n";

    $csvData        = array();
    $headings       = array();
    $weekString     = 'CY'.$csvYear.'W'.$week;

    foreach ($headers as $heading => $field) {
       $headings[] = $heading;
    }

    $csvData[] = $headings;

    foreach ($data as $row) {
        $rowData = array();

        foreach ($headers as $heading => $field) {
            $rowData[] = $field ? $row[$field] : $weekString;
        }

        $csvData[] = $rowData;
    }

    if (($handle = fopen($filename, 'w')) !== FALSE) {
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
    }

    fclose($handle);

    /*$output .= gzipFile($filename);

    $output .= " - Encrypting ".$filename.".gz...\n";

    exec('gpg -ea --batch --always-trust -r "'.$recipient.'" '.$filename.'.gz');

    if (file_exists($filename.'.gz.asc')) {
        exec('shred -uv '.$filename.'.gz');
    }*/

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
        exec('shred -uv '.$file);
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
