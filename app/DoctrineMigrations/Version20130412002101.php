<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130412002101 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE pd_content_report ADD youtubeVideo_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE pd_content_report ADD CONSTRAINT FK_E798A78E425D381 FOREIGN KEY (youtubeVideo_id) REFERENCES pd_videos_youtube(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_E798A78E425D381 ON pd_content_report (youtubeVideo_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE pd_content_report DROP FOREIGN KEY FK_E798A78E425D381");
        $this->addSql("DROP INDEX IDX_E798A78E425D381 ON pd_content_report");
        $this->addSql("ALTER TABLE pd_content_report DROP youtubeVideo_id");
    }
}
