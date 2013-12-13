<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20131213115251 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE `campsite`.`EntrySetRegistry` RENAME TO  `campsite`.`entry_set_registry` ;");
        $this->addSql("ALTER TABLE `campsite`.`JudgeIdeaMap` RENAME TO  `campsite`.`judge_idea_map` ;");
        $this->addSql("ALTER TABLE `campsite`.`TagIdeaMap` RENAME TO  `campsite`.`tag_idea_map` ;");
        $this->addSql("ALTER TABLE `campsite`.`VoteCriteria` RENAME TO  `campsite`.`vote_criteria` ;");
        $this->addSql("ALTER TABLE `campsite`.`Vote` RENAME TO  `campsite`.`vote` ;");
        $this->addSql("ALTER TABLE `campsite`.`Document` RENAME TO  `campsite`.`document` ;");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE `campsite`.`entry_set_registry` RENAME TO  `campsite`.`EntrySetRegistry` ;");
        $this->addSql("ALTER TABLE `campsite`.`judge_idea_map` RENAME TO  `campsite`.`JudgeIdeaMap` ;");
        $this->addSql("ALTER TABLE `campsite`.`tag_idea_map` RENAME TO  `campsite`.`TagIdeaMap` ;");
        $this->addSql("ALTER TABLE `campsite`.`vote_criteria` RENAME TO  `campsite`.`VoteCriteria` ;");
        $this->addSql("ALTER TABLE `campsite`.`vote` RENAME TO  `campsite`.`Vote` ;");
        $this->addSql("ALTER TABLE `campsite`.`document` RENAME TO  `campsite`.`Document` ;");
    }
}
