<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130115154842 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE BackgroundAdSite (id INT AUTO_INCREMENT NOT NULL, site_id INT NOT NULL, ad_id INT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_3D61D5E7F6BD1646 (site_id), INDEX IDX_3D61D5E74F34D596 (ad_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("CREATE TABLE BackgroundAd (id INT AUTO_INCREMENT NOT NULL, file_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, dateStart DATETIME NOT NULL, dateEnd DATETIME NOT NULL, timezone VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8CE284AB93CB796C (file_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("ALTER TABLE BackgroundAdSite ADD CONSTRAINT FK_3D61D5E7F6BD1646 FOREIGN KEY (site_id) REFERENCES pd_site(id)");
        $this->addSql("ALTER TABLE BackgroundAdSite ADD CONSTRAINT FK_3D61D5E74F34D596 FOREIGN KEY (ad_id) REFERENCES BackgroundAd(id)");
        $this->addSql("ALTER TABLE BackgroundAd ADD CONSTRAINT FK_8CE284AB93CB796C FOREIGN KEY (file_id) REFERENCES pd_media(id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE BackgroundAdSite DROP FOREIGN KEY FK_3D61D5E74F34D596");
        $this->addSql("DROP TABLE BackgroundAdSite");
        $this->addSql("DROP TABLE BackgroundAd");
    }
}
