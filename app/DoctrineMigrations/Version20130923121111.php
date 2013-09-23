<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130923121111 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_sweepstakes_entry ADD optional_checkbox_answer TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE pd_sweepstakes ADD has_optional_checkbox TINYINT(1) NOT NULL, ADD optional_checkbox_label VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_sweepstakes DROP has_optional_checkbox, DROP optional_checkbox_label");
        $this->addSql("ALTER TABLE pd_sweepstakes_entry DROP optional_checkbox_answer");
    }
}
