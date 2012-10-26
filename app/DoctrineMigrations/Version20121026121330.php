<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121026121330 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_gallery_media DROP FOREIGN KEY FK_BC5900A3DA5256D");
        $this->addSql("DROP TABLE pd_gallery_image");
        $this->addSql("DROP TABLE pd_gallery_video");
        $this->addSql("ALTER TABLE pd_gallery_media ADD author_id INT DEFAULT NULL, ADD title VARCHAR(255) NOT NULL, ADD description VARCHAR(512) NOT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, ADD deleted TINYINT(1) NOT NULL, ADD deletedReason VARCHAR(255) DEFAULT NULL, ADD youtubeId VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE pd_gallery_media ADD CONSTRAINT FK_BC5900AF675F31B FOREIGN KEY (author_id) REFERENCES fos_user(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_BC5900AF675F31B ON pd_gallery_media (author_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE TABLE pd_gallery_image (id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted TINYINT(1) NOT NULL, deletedReason VARCHAR(255) DEFAULT NULL, description VARCHAR(512) NOT NULL, INDEX IDX_A4D435593DA5256D (image_id), INDEX IDX_A4D43559F675F31B (author_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("CREATE TABLE pd_gallery_video (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, youtubeId VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted TINYINT(1) NOT NULL, deletedReason VARCHAR(255) DEFAULT NULL, INDEX IDX_1D2EEB2AF675F31B (author_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("ALTER TABLE pd_gallery_image ADD CONSTRAINT FK_A4D435593DA5256D FOREIGN KEY (image_id) REFERENCES pd_media(id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE pd_gallery_image ADD CONSTRAINT FK_A4D43559F675F31B FOREIGN KEY (author_id) REFERENCES fos_user(id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE pd_gallery_video ADD CONSTRAINT FK_1D2EEB2AF675F31B FOREIGN KEY (author_id) REFERENCES fos_user(id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE pd_gallery_media DROP FOREIGN KEY FK_BC5900AF675F31B");
        $this->addSql("DROP INDEX IDX_BC5900AF675F31B ON pd_gallery_media");
        $this->addSql("ALTER TABLE pd_gallery_media DROP author_id, DROP title, DROP description, DROP created_at, DROP updated_at, DROP deleted, DROP deletedReason, DROP youtubeId");
    }
}
