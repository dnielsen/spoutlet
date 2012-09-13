<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120913202144 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE TABLE pd_sent_emails (id INT AUTO_INCREMENT NOT NULL, recipient VARCHAR(255) NOT NULL, fromFull VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, sesMessageId VARCHAR(255) NOT NULL, sendStatus TINYINT(1) NOT NULL, siteEmailSentFrom VARCHAR(255) NOT NULL, emailType VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("DROP TABLE pd_sent_emails");
    }
}
