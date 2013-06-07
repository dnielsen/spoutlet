<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130515155411 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE sp_news ADD thumbnail_id INT DEFAULT NULL, ADD type VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE sp_news ADD CONSTRAINT FK_CD1A065FFDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES pd_media(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_CD1A065FFDFF2E92 ON sp_news (thumbnail_id)");
        $this->addSql("ALTER TABLE sp_news DROP sitified_at");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE sp_news DROP FOREIGN KEY FK_CD1A065FFDFF2E92");
        $this->addSql("DROP INDEX IDX_CD1A065FFDFF2E92 ON sp_news");
        $this->addSql("ALTER TABLE sp_news DROP thumbnail_id, DROP type");
        $this->addSql("ALTER TABLE sp_news ADD sitified_at DATETIME DEFAULT NULL");
    }
}
