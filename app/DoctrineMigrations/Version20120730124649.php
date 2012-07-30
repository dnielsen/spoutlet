<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120730124649 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_groups ADD owner_id INT DEFAULT NULL, ADD deleted TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE pd_groups ADD CONSTRAINT FK_D63978B77E3C61F9 FOREIGN KEY (owner_id) REFERENCES fos_user(id) ON DELETE CASCADE");
        $this->addSql("CREATE INDEX IDX_D63978B77E3C61F9 ON pd_groups (owner_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_groups DROP FOREIGN KEY FK_D63978B77E3C61F9");
        $this->addSql("DROP INDEX IDX_D63978B77E3C61F9 ON pd_groups");
        $this->addSql("ALTER TABLE pd_groups DROP owner_id, DROP deleted");
    }
}
