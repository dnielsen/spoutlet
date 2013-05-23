<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130328201930 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE pd_youtube_votes (id INT AUTO_INCREMENT NOT NULL, video_id INT DEFAULT NULL, user_id INT DEFAULT NULL, ip_address VARCHAR(20) NOT NULL, vote_type VARCHAR(255) NOT NULL, voted_at DATETIME NOT NULL, INDEX IDX_6758C0F29C1004E (video_id), INDEX IDX_6758C0FA76ED395 (user_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("ALTER TABLE pd_youtube_votes ADD CONSTRAINT FK_6758C0F29C1004E FOREIGN KEY (video_id) REFERENCES pd_videos_youtube(id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE pd_youtube_votes ADD CONSTRAINT FK_6758C0FA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user(id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE pd_videos_youtube ADD last_viewed_at DATETIME DEFAULT NULL, ADD featured TINYINT(1) NOT NULL, ADD featured_at DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE pd_youtube_votes");
        $this->addSql("ALTER TABLE pd_videos_youtube DROP last_viewed_at, DROP featured, DROP featured_at");
    }
}
