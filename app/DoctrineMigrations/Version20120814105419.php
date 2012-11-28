<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120814105419 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_group_news DROP FOREIGN KEY FK_A291EEC5F675F31B");
        $this->addSql("ALTER TABLE pd_group_news DROP FOREIGN KEY FK_A291EEC5FE54D947");
        $this->addSql("ALTER TABLE pd_group_news ADD CONSTRAINT FK_A291EEC5F675F31B FOREIGN KEY (author_id) REFERENCES fos_user(id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE pd_group_news ADD CONSTRAINT FK_A291EEC5FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id) ON DELETE SET NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE pd_group_news DROP FOREIGN KEY FK_A291EEC5FE54D947");
        $this->addSql("ALTER TABLE pd_group_news DROP FOREIGN KEY FK_A291EEC5F675F31B");
        $this->addSql("ALTER TABLE pd_group_news ADD CONSTRAINT FK_A291EEC5FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE pd_group_news ADD CONSTRAINT FK_A291EEC5F675F31B FOREIGN KEY (author_id) REFERENCES fos_user(id) ON DELETE CASCADE");
    }
}
