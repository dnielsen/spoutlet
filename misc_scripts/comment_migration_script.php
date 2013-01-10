<?php

/* Connect to an ODBC database using driver invocation */
$dsn = 'mysql:dbname=;host=';
$user = '';
$password = '';

$productionDbName = '';
$stagingDbName = '';

$productionAclDbName = '';
$stagingAclDbName = '';

$baseDir = '/var/www/alienwarearena/deploy/current';
$stagingBaseDir = '/var/www/staging/deploy/current';

try {
    $dbh = new PDO($dsn, $user, $password);

    echo "\nBeginning comment migration...";

// Production comments

    if ($productionDbName !== '') {
        echo "\n - Creating ACL database structure (production)...";
        $classId = setupAclTables($productionAclDbName, $dbh, $baseDir);
        echo "\n - Migrating comments(production)...";
        migrateComments($productionDbName, $productionAclDbName, $dbh, $classId);
    }

// Staging comments

    if ($stagingDbName !== '') {
        echo "\n - Creating ACL database structure (staging)...";
        $classId = setupAclTables($stagingAclDbName, $dbh, $stagingBaseDir);
        echo "\n - Migrating comments(staging)...";
        migrateComments($stagingDbName, $stagingAclDbName, $dbh, $classId);
    }

    echo "\nDone.\n";

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

function setupAclTables($dbName, $dbh, $baseDir)
{
    exec('php '.$baseDir.'/app/console init:acl');

    echo "\n   - Inserting class specific ACL entries for admin access...";

    $class = "Platformd\\SpoutletBundle\\Entity\\Comment";
    $sql = 'INSERT IGNORE INTO `'.$dbName.'`.`acl_classes` (`class_type`) VALUES (:class)';
    $query = $dbh->prepare($sql);
    $query->execute(array(':class'=>$class));

    $sql = 'INSERT IGNORE INTO `'.$dbName.'`.`acl_security_identities` (`identifier`, `username`) VALUES ("ROLE_ADMIN", 0), ("ROLE_SUPER_ADMIN", 0)';
    $query = $dbh->prepare($sql);
    $query->execute();

    $sql = 'SELECT * FROM `'.$dbName.'`.`acl_security_identities` WHERE `identifier` IN ("ROLE_ADMIN", "ROLE_SUPER_ADMIN")';
    foreach ($dbh->query($sql) as $row) {
        if ($row['identifier'] == "ROLE_ADMIN") {
            $roleAdminId = $row['id'];
        } else {
            $roleSuperAdminId = $row['id'];
        }
    }

    $sql = 'SELECT `id` FROM `'.$dbName.'`.`acl_classes` WHERE `class_type`="Platformd\\\\SpoutletBundle\\\\Entity\\\\Comment"';

    $result = $dbh->query($sql);
    foreach ($result as $row) {
        $classId = $row['id'];
    }

    $sql = 'INSERT IGNORE INTO `'.$dbName.'`.`acl_entries` (`class_id`, `security_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`)
        VALUES (:classId, :admin, 0, 64, 1, "all", 0, 0),
        (:classId, :superAdmin, 0, 64, 1, "all", 0, 0)';
    $query = $dbh->prepare($sql);
    $query->execute(array(':classId'=>$classId,
                          ':admin'=>$roleAdminId,
                          ':superAdmin'=>$roleSuperAdminId));

    return $classId;
}

function migrateComments($dbName, $aclDbName, $dbh, $classId)
{
    echo "\n   - Collating author information...";

    $usernames = array();
    $userIdents = array();

    $sql = 'SELECT DISTINCT `fos_user`.`id`, `fos_user`.`username`
            FROM `'.$dbName.'`.`comment`
            LEFT JOIN `'.$dbName.'`.`fos_user` ON `comment`.`author_id` = `fos_user`.`id`';

    $insertSql = 'INSERT IGNORE INTO `'.$aclDbName.'`.`acl_security_identities` (`identifier`, `username`) VALUES (:identifier, 1)';

    foreach ($result = $dbh->query($sql) as $row) {
        $identifier = "Platformd\\UserBundle\\Entity\\User-".$row['username'];
        $query = $dbh->prepare($insertSql);
        $query->execute(array(':identifier'=>$identifier));

        $identId = $dbh->lastInsertId();

        $usernames[$row['id']] = $row['username'];
        $userIdents[$row['id']] = $identId;
    }

    echo "\n   - Migrating comment threads...";

    $sql = 'INSERT INTO `'.$dbName.'`.`commenting_thread` (`id`, `is_commentable`, `last_commented_at`, `comment_count`, `permalink`)
        SELECT `id`, `is_commentable`, `last_comment_at`, `num_comments`, `permalink` FROM `'.$dbName.'`.`Thread`';
    $query = $dbh->prepare($sql);
    $query->execute();

    echo "\n   - Updating permalinks...";

    $selectPermalinkSql = 'SELECT `id`, `permalink` FROM `'.$dbName.'`.`commenting_thread` WHERE `permalink` LIKE "http://%"';
    $updatePermalinkSql = 'UPDATE `'.$dbName.'`.`commenting_thread` SET `permalink` = :permalink WHERE `id` = :id';

    foreach ($dbh->query($selectPermalinkSql) as $thread) {
        $permalink  = $thread['permalink'];
        $parsedLink = parse_url($permalink);

        $newPermalink = str_replace($parsedLink['scheme'].'://'.$parsedLink['host'], '', $permalink)."#comments";

        $query = $dbh->prepare($updatePermalinkSql);
        $query->execute(array(':permalink'=>$newPermalink,
                              ':id'=>$thread['id']));

    }

    echo "\n   - Migrating comment posts...";

    $sql = 'INSERT INTO `'.$dbName.'`.`commenting_comment` (`thread_id`, `parent_id`, `author_id`, `body`, `created_at`, `deleted`)
        SELECT `thread_id`, NULL, `author_id`, `body`, `created_at`, 0 FROM `'.$dbName.'`.`comment`';
    $query = $dbh->prepare($sql);
    $query->execute();

    echo "\n   - Creating ACL object identities for comments...";

    $selectCommentSql = 'SELECT * FROM `'.$dbName.'`.`commenting_comment`';

    $insertIdentSql = 'INSERT IGNORE INTO `'.$aclDbName.'`.`acl_object_identities` (`parent_object_identity_id`, `class_id`, `object_identifier`, `entries_inheriting`)
        VALUES (NULL, :classId, :commentId, 1)';

    $insertAncestorSql = 'INSERT IGNORE INTO `'.$aclDbName.'`.`acl_object_identity_ancestors` (`object_identity_id`, `ancestor_id`) VALUES (:id, :id)';

    $insertEntrySql = 'INSERT IGNORE INTO `'.$aclDbName.'`.`acl_entries` (`class_id`, `object_identity_id`, `security_identity_id`, `ace_order`, `mask`, `granting`, `granting_strategy`, `audit_success`, `audit_failure`)
        VALUES (:classId, :objectIdentId, :securityIdentId, 0, 128, 1, "all", 0, 0)';

    foreach ($dbh->query($selectCommentSql) as $row) {
        $query = $dbh->prepare($insertIdentSql);
        $query->execute(array(':classId'=>$classId,
                              ':commentId'=>$row['id']));

        $objectIdentId = $dbh->lastInsertId();

        $query = $dbh->prepare($insertAncestorSql);
        $query->execute(array(':id'=>$objectIdentId));

        $query = $dbh->prepare($insertEntrySql);
        $query->execute(array(':classId'=>$classId,
                              ':objectIdentId'=>$objectIdentId,
                              ':securityIdentId'=>$userIdents[$row['author_id']]));
    }

}

?>

