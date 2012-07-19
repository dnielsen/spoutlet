<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120516144533 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE pd_game ADD logoThumbnail_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE pd_game ADD CONSTRAINT FK_5A4DF4933AE68AE3 FOREIGN KEY (logoThumbnail_id) REFERENCES pd_media(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_5A4DF4933AE68AE3 ON pd_game (logoThumbnail_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE pd_game DROP FOREIGN KEY FK_5A4DF4933AE68AE3");
        $this->addSql("DROP INDEX IDX_5A4DF4933AE68AE3 ON pd_game");
        $this->addSql("ALTER TABLE pd_game DROP logoThumbnail_id");
    }
}
