<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140528165503 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE watched_events DROP FOREIGN KEY FK_D4BB68B26B8221C0");
        $this->addSql("ALTER TABLE watched_events DROP FOREIGN KEY FK_D4BB68B27FAB70E4");
        $this->addSql("DROP INDEX IDX_D4BB68B27FAB70E4 ON watched_events");
        $this->addSql("DROP INDEX IDX_D4BB68B26B8221C0 ON watched_events");
        $this->addSql("ALTER TABLE watched_events ADD global_event_id INT DEFAULT NULL, ADD group_event_id INT DEFAULT NULL, DROP group_event, DROP global_event");
        $this->addSql("ALTER TABLE watched_events ADD CONSTRAINT FK_D4BB68B2A691BEA5 FOREIGN KEY (global_event_id) REFERENCES global_event(id)");
        $this->addSql("ALTER TABLE watched_events ADD CONSTRAINT FK_D4BB68B278C7A4F4 FOREIGN KEY (group_event_id) REFERENCES group_event(id)");
        $this->addSql("CREATE INDEX IDX_D4BB68B2A691BEA5 ON watched_events (global_event_id)");
        $this->addSql("CREATE INDEX IDX_D4BB68B278C7A4F4 ON watched_events (group_event_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE watched_events DROP FOREIGN KEY FK_D4BB68B2A691BEA5");
        $this->addSql("ALTER TABLE watched_events DROP FOREIGN KEY FK_D4BB68B278C7A4F4");
        $this->addSql("DROP INDEX IDX_D4BB68B2A691BEA5 ON watched_events");
        $this->addSql("DROP INDEX IDX_D4BB68B278C7A4F4 ON watched_events");
        $this->addSql("ALTER TABLE watched_events ADD group_event INT DEFAULT NULL, ADD global_event INT DEFAULT NULL, DROP global_event_id, DROP group_event_id");
        $this->addSql("ALTER TABLE watched_events ADD CONSTRAINT FK_D4BB68B26B8221C0 FOREIGN KEY (group_event) REFERENCES group_event(id)");
        $this->addSql("ALTER TABLE watched_events ADD CONSTRAINT FK_D4BB68B27FAB70E4 FOREIGN KEY (global_event) REFERENCES global_event(id)");
        $this->addSql("CREATE INDEX IDX_D4BB68B27FAB70E4 ON watched_events (global_event)");
        $this->addSql("CREATE INDEX IDX_D4BB68B26B8221C0 ON watched_events (group_event)");
    }
}
