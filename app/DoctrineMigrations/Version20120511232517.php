<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120511232517 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE pd_game ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL");
        $this->addSql("ALTER TABLE pd_game_page ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE pd_game DROP created_at, DROP updated_at");
        $this->addSql("ALTER TABLE pd_game_page DROP created_at, DROP updated_at");
    }
}
