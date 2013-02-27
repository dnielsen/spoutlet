<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130227234030 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7FE54D947");
        $this->addSql("DROP INDEX UNIQ_3BAE0AA7FE54D947 ON event");
        $this->addSql("ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_3BAE0AA7FE54D947 ON event (group_id)");
        $this->addSql("ALTER TABLE pd_deal DROP FOREIGN KEY FK_9A980409FE54D947");
        $this->addSql("DROP INDEX UNIQ_9A980409FE54D947 ON pd_deal");
        $this->addSql("ALTER TABLE pd_deal ADD CONSTRAINT FK_9A980409FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_9A980409FE54D947 ON pd_deal (group_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7FE54D947");
        $this->addSql("DROP INDEX IDX_3BAE0AA7FE54D947 ON event");
        $this->addSql("ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_3BAE0AA7FE54D947 ON event (group_id)");
        $this->addSql("ALTER TABLE pd_deal DROP FOREIGN KEY FK_9A980409FE54D947");
        $this->addSql("DROP INDEX IDX_9A980409FE54D947 ON pd_deal");
        $this->addSql("ALTER TABLE pd_deal ADD CONSTRAINT FK_9A980409FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_9A980409FE54D947 ON pd_deal (group_id)");
    }
}
