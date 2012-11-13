<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121113132439 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_vote DROP FOREIGN KEY FK_2376407B1CD0F0DE");
        $this->addSql("DROP INDEX UNIQ_2376407B1CD0F0DE ON pd_vote");
        $this->addSql("DROP INDEX media_user_idx ON pd_vote");
        $this->addSql("ALTER TABLE pd_vote DROP contest_id");
        $this->addSql("CREATE UNIQUE INDEX media_user_idx ON pd_vote (galleryMedia_id, user_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("DROP INDEX media_user_idx ON pd_vote");
        $this->addSql("ALTER TABLE pd_vote ADD contest_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE pd_vote ADD CONSTRAINT FK_2376407B1CD0F0DE FOREIGN KEY (contest_id) REFERENCES pd_contest(id) ON DELETE CASCADE");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_2376407B1CD0F0DE ON pd_vote (contest_id)");
        $this->addSql("CREATE UNIQUE INDEX media_user_idx ON pd_vote (galleryMedia_id, user_id, contest_id)");
    }
}
