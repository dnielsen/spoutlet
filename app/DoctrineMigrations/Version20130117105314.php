<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130117105314 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_group_discussion ADD last_post_id INT DEFAULT NULL, ADD lastUpdatedBy_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE pd_group_discussion ADD CONSTRAINT FK_39FF6BBB63450CE6 FOREIGN KEY (lastUpdatedBy_id) REFERENCES fos_user(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_39FF6BBB63450CE6 ON pd_group_discussion (lastUpdatedBy_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_group_discussion DROP FOREIGN KEY FK_39FF6BBB63450CE6");
        $this->addSql("DROP INDEX IDX_39FF6BBB63450CE6 ON pd_group_discussion");
        $this->addSql("ALTER TABLE pd_group_discussion DROP last_post_id, DROP lastUpdatedBy_id");
    }
}
