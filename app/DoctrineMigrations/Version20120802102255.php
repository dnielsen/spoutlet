<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120802102255 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("
            ALTER TABLE  `spoutlet`.`giveaway_key`
            ADD INDEX  `pool_ip_idx` (  `pool` ,  `ip_address` ),
            ADD INDEX  `user_pool_idx` (  `user` ,  `pool` )
        ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP INDEX pool_ip_idx ON giveaway_key");
        $this->addSql("DROP INDEX user_pool_idx ON giveaway_key");
    }
}
