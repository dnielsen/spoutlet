<?php

include 'config.php';

$processedVideosCsv = "processedVideos.csv";

output(0, "\nAlienware Arena Youtube Uploader v1.0\n");

$processedVideos = array();

if (file_exists($processedVideosCsv)) {

    output(2, 'Getting list of already uploaded videos...', false);

    // Get info about already processed videos
    if (($handle = fopen($processedVideosCsv, "r")) !== FALSE) {

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            if (!isset($data[0]) || !isset($data[1])) {
                continue;
            }

            $processedVideos[$data[0]] = $data[1];
            $row ++;
        }

        fclose($handle);
    }

    output(0, 'done.');
}

try {
    output(2, 'Connecting to database...', false);
    $dbh = new PDO($dsn, $user, $password);
    output(0, 'done.');

    foreach ($queries as $output => $sql) {

        output(2, 'Getting '.$output.' information...', false);

        $result = $dbh->query($sql);
        output(0, 'done.');
        output(4, $result->rowCount().' videos to process.');

        foreach ($result as $movie) {

            $id = $movie['id'];

            output(6, 'Processing movie id => '.$id.'...', false);

            if (isset($processedVideos[$id])) {
                output(0, 'already processed.');
                continue;
            }

            output(0);

            $name           = str_replace('\'', '\'\\\'\'', $movie['name']);
            $description    = str_replace('\'', '\'\\\'\'', $movie['description']);
            $downloadFile   = $movie['download_file'];
            $streamFile     = $movie['stream_file'];
            $serverId       = $movie['serverid'];
            $mime           = $movie['mime'];
            $category       = $movie['category'];

            $filePath       = $fileLocations[$serverId].$downloadFile;
            $backupFilePath = $fileLocations[$serverId].$streamFile;

            $fileExists         = file_exists($filePath);
            $backupFileExists   = file_exists($backupFilePath);

            if ($fileExists || $backupFileExists) {

                output(8, 'Uploading video to youtube...');

                $file = $fileExists ? $filePath : $backupFilePath;

                exec('python upload_video.py --file=\''.$file.'\' --mime="'.$mime.'" --title=\''.$name.'\' --description=\''.$description.'\' --category="20" --privacyStatus="public" 2>&1', $output);

                $lastLine = end($output);

                logMessage('Exit line from upload script - '.$lastLine);

                if (false !== strpos($lastLine, 'was successfully uploaded')) {
                    preg_match("/video id: (.*?)\)/", $lastLine, $matches);
                    $youtubeId = $matches[1];

                    output(8, 'Uploaded to youtube with id => '.$youtubeId);

                    $csvRow     = $id.',"'.$youtubeId.'","'.$category.'"'."\n";
                    file_put_contents($processedVideosCsv, $csvRow, FILE_APPEND | LOCK_EX);
                    $processedVideos[$id] = $youtubeId;
                } else {
                    output(8, 'Something went wrong with upload. File not added to list of processed videos.');
                }
            } else {
                output(8, 'File does not exist at [ '.$filePath.' ]');
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
    if ($message) {
        logMessage($message, $newLine);
    }
}

function logMessage($message, $newLine = true)
{
    date_default_timezone_set('UTC');
    $timestamp = new \DateTime();
    file_put_contents('logfile', '[ '.$timestamp->format('Y-m-d H:i:s').' ] - '.$message."\n", FILE_APPEND | LOCK_EX);
}

?>
