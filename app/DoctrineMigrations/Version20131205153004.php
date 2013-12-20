<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20131205153004 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE group_event ADD entrySetRegistration_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE group_event ADD CONSTRAINT FK_6B8221C0D0E9DF32 FOREIGN KEY (entrySetRegistration_id) REFERENCES EntrySetRegistry(id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_6B8221C0D0E9DF32 ON group_event (entrySetRegistration_id)");
        $this->addSql("ALTER TABLE global_event ADD entrySetRegistration_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE global_event ADD CONSTRAINT FK_7FAB70E4D0E9DF32 FOREIGN KEY (entrySetRegistration_id) REFERENCES EntrySetRegistry(id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_7FAB70E4D0E9DF32 ON global_event (entrySetRegistration_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE global_event DROP FOREIGN KEY FK_7FAB70E4D0E9DF32");
        $this->addSql("DROP INDEX UNIQ_7FAB70E4D0E9DF32 ON global_event");
        $this->addSql("ALTER TABLE global_event DROP entrySetRegistration_id");
        $this->addSql("ALTER TABLE group_event DROP FOREIGN KEY FK_6B8221C0D0E9DF32");
        $this->addSql("DROP INDEX UNIQ_6B8221C0D0E9DF32 ON group_event");
        $this->addSql("ALTER TABLE group_event DROP entrySetRegistration_id");
    }
}
