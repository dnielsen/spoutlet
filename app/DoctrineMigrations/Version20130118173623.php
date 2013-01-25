<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130118173623 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_contest ADD test_only TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE pd_deal ADD test_only TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE event ADD test_only TINYINT(1) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE event DROP test_only");
        $this->addSql("ALTER TABLE pd_contest DROP test_only");
        $this->addSql("ALTER TABLE pd_deal DROP test_only");
    }
}
