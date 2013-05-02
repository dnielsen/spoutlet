<?php

include 'config.php';

$processedVideosCsv = "processedVideos.csv";

output(0, "\nAlienware Arena Youtube Uploader v1.0\n");
output(2, 'Getting list of already uploaded videos...', false);

// Get info about already processed videos
if (($handle = fopen($processedVideosCsv, "r")) !== FALSE) {

    $row = 0;
    $processedVideos = array();

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

        if (!isset($data[0]) || !isset($data[1])) {
            continue;
        }

        $processedVideos[$data[0]]['youtubeId'] = $data[1];
        $row ++;
    }

    fclose($handle);
}

output(0, 'done.');

try {
    output(2, 'Connecting to database...', false);
    $dbh = new PDO($dsn, $user, $password);
    output(0, 'done.');

    output(2, 'Getting video information...', false);

    // get list of movies to upload
    $sql = 'SELECT `m`.`id`, `m`.`name`, `m`.`length`, `m`.`description`, `m`.`download_file`, `f`.`serverid`, `f`.`mime`
            FROM `'.$videoDb.'`.`movies` `m`
            LEFT JOIN `'.$videoDb.'`.`files` `f` ON `m`.`download_file` = `f`.`fileid`
            WHERE `m`.`download_file` <> 0
            AND `m`.`download_file` IS NOT NULL
            AND `f`.`deleted` = 0';

    output(0, 'done.');

    foreach ($dbh->query($sql) as $movie) {

        $id = $movie['id'];

        output(4, 'Processing movie id => '.$id.'...', false);

        if (isset($processedVideos[$id])) {
            output(0, 'already processed.');
            continue;
        }

        output(0);

        $name           = str_replace('\'', '\'\\\'\'', $movie['name']);
        $description    = str_replace('\'', '\'\\\'\'', $movie['description']);
        $downloadFile   = $movie['download_file'];
        $serverId       = $movie['serverid'];
        $mime           = $movie['mime'];

        $filePath       = $fileLocations[$serverId].$downloadFile;

        if (file_exists($filePath)) {

            output(6, 'Uploading video to youtube...');

            exec('python upload_video.py --file=\''.$filePath.'\' --mime="'.$mime.'" --title=\''.$name.'\' --description=\''.$description.'\' --category="20" --privacyStatus="public" 2>&1', $output);

            $lastLine = end($output);

            if (false !== strpos($lastLine, 'was successfully uploaded')) {
                preg_match("/video id: (.*?)\)/", $lastLine, $matches);
                $youtubeId = $matches[1];

                output(6, 'Uploaded to youtube with id => '.$youtubeId);

                $csvRow     = $id.',"'.$youtubeId.'"'."\n";
                file_put_contents($processedVideosCsv, $csvRow, FILE_APPEND | LOCK_EX);
            } else {
                output(6, 'Something went wrong with upload. File not added to list of processed videos.');
            }
        }
    }

} catch (PDOException $e) {
    output(0, 'failed.');
    output(4, 'Connection failed: ' . $e->getMessage());
}

output(0);

function output($indentation, $message = null, $newLine = true)
{
    if ($message === null) {
        echo '';
    }

    echo str_repeat(' ', $indentation).$message.($newLine ? "\n" : '');
    file_put_contents('logfile', $message.($newLine ? "\n" : ''), FILE_APPEND | LOCK_EX);
}

?>
