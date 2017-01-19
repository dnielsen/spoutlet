<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170122160150 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `session` ADD sess_lifetime MEDIUMINT NOT NULL');
        $this->addSql('ALTER TABLE `session` CHANGE session_value sess_data BLOB NOT NULL');
        $this->addSql('ALTER TABLE `session` MODIFY session_time INT(11) UNSIGNED NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `session` DROP sess_lifetime');
        $this->addSql('ALTER TABLE `session` CHANGE sess_data session_value TEXT');
        $this->addSql('ALTER TABLE `session` MODIFY session_time INT(11) NOT NULL');
    }
}
