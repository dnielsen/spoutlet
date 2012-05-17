<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120517094051 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_game_page ADD youtubeIdTrailer1Headline VARCHAR(255) DEFAULT NULL, ADD youtubeIdTrailer2Headline VARCHAR(255) DEFAULT NULL, ADD youtubeIdTrailer3Headline VARCHAR(255) DEFAULT NULL, ADD youtubeIdTrailer4Headline VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_game_page DROP youtubeIdTrailer1Headline, DROP youtubeIdTrailer2Headline, DROP youtubeIdTrailer3Headline, DROP youtubeIdTrailer4Headline");
    }
}
