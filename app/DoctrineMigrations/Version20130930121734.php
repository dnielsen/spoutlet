<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130930121734 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE comment DROP FOREIGN KEY FK_9474526CE2904019");
        $this->addSql("DROP TABLE Thread");
        $this->addSql("DROP TABLE comment");
        $this->addSql("ALTER TABLE pd_group_email ADD sent_to_all TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE global_event_email ADD sent_to_all TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE group_event_email ADD sent_to_all TINYINT(1) NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE TABLE Thread (id VARCHAR(255) NOT NULL, permalink VARCHAR(255) NOT NULL, is_commentable TINYINT(1) NOT NULL, num_comments INT NOT NULL, last_comment_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, thread_id VARCHAR(255) DEFAULT NULL, author_id INT DEFAULT NULL, body LONGTEXT NOT NULL, ancestors VARCHAR(1024) NOT NULL, depth INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_9474526CE2904019 (thread_id), INDEX IDX_9474526CF675F31B (author_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("ALTER TABLE comment ADD CONSTRAINT FK_9474526CE2904019 FOREIGN KEY (thread_id) REFERENCES Thread(id)");
        $this->addSql("ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES fos_user(id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE global_event_email DROP sent_to_all");
        $this->addSql("ALTER TABLE group_event_email DROP sent_to_all");
        $this->addSql("ALTER TABLE pd_group_email DROP sent_to_all");
    }
}
