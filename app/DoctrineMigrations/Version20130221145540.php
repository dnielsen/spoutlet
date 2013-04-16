<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130221145540 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("CREATE TABLE login_record (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, country_id INT DEFAULT NULL, site_id INT DEFAULT NULL, ip_address VARCHAR(255) NOT NULL, datetime DATETIME NOT NULL, INDEX IDX_6C50B80BA76ED395 (user_id), INDEX IDX_6C50B80BF92F3E70 (country_id), INDEX IDX_6C50B80BF6BD1646 (site_id), PRIMARY KEY(id)) ENGINE = InnoDB");
        $this->addSql("ALTER TABLE login_record ADD CONSTRAINT FK_6C50B80BA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user(id) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE login_record ADD CONSTRAINT FK_6C50B80BF92F3E70 FOREIGN KEY (country_id) REFERENCES country(id)");
        $this->addSql("ALTER TABLE login_record ADD CONSTRAINT FK_6C50B80BF6BD1646 FOREIGN KEY (site_id) REFERENCES pd_site(id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("DROP TABLE login_record");
    }
}
