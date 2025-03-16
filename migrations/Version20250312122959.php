<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250312122959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE branch ADD name VARCHAR(255) NOT NULL, ADD address VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD branch_id INT DEFAULT NULL, ADD name VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD role VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649DCD6CC49 FOREIGN KEY (branch_id) REFERENCES branch (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649DCD6CC49 ON user (branch_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE branch DROP name, DROP address');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649DCD6CC49');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649DCD6CC49 ON user');
        $this->addSql('ALTER TABLE user DROP branch_id, DROP name, DROP email, DROP role');
    }
}
