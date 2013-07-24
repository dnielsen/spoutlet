<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130724144851 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE fos_user DROP avatar, DROP avatar_approved");
        $this->addSql("CREATE INDEX cevo_user_id_idx ON fos_user (cevoUserId)");
        $this->addSql("ALTER TABLE pd_groups_members DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE pd_groups_members ADD PRIMARY KEY (user_id, group_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("DROP INDEX cevo_user_id_idx ON fos_user");
        $this->addSql("ALTER TABLE fos_user ADD avatar VARCHAR(255) DEFAULT NULL, ADD avatar_approved TINYINT(1) NOT NULL");
        $this->addSql("ALTER TABLE pd_groups_members DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE pd_groups_members ADD PRIMARY KEY (group_id, user_id)");
    }
}
