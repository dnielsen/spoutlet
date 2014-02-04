<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140130193600 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE TABLE sponsor_registry (id INT AUTO_INCREMENT NOT NULL, group_id INT DEFAULT NULL, event_id INT DEFAULT NULL, sponsor_id INT DEFAULT NULL, level VARCHAR(255) NOT NULL, INDEX IDX_553053B8FE54D947 (group_id), INDEX IDX_553053B871F7E88B (event_id), INDEX IDX_553053B812F7FB51 (sponsor_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("CREATE TABLE sponsor (id INT AUTO_INCREMENT NOT NULL, creator_id INT DEFAULT NULL, image_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_818CC9D461220EA6 (creator_id), UNIQUE INDEX UNIQ_818CC9D43DA5256D (image_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("ALTER TABLE sponsor_registry ADD CONSTRAINT FK_553053B8FE54D947 FOREIGN KEY (group_id) REFERENCES pd_groups(id)");
        $this->addSql("ALTER TABLE sponsor_registry ADD CONSTRAINT FK_553053B871F7E88B FOREIGN KEY (event_id) REFERENCES group_event(id)");
        $this->addSql("ALTER TABLE sponsor_registry ADD CONSTRAINT FK_553053B812F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor(id)");
        $this->addSql("ALTER TABLE sponsor ADD CONSTRAINT FK_818CC9D461220EA6 FOREIGN KEY (creator_id) REFERENCES fos_user(id)");
        $this->addSql("ALTER TABLE sponsor ADD CONSTRAINT FK_818CC9D43DA5256D FOREIGN KEY (image_id) REFERENCES pd_media(id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE sponsor_registry DROP FOREIGN KEY FK_553053B812F7FB51");
        $this->addSql("DROP TABLE sponsor_registry");
        $this->addSql("DROP TABLE sponsor");
    }
}
