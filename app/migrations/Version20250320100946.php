<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250320100946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE music ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE music ADD CONSTRAINT FK_CD52224A12469DE2 FOREIGN KEY (category_id) REFERENCES music (id)');
        $this->addSql('CREATE INDEX IDX_CD52224A12469DE2 ON music (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('ALTER TABLE music DROP FOREIGN KEY FK_CD52224A12469DE2');
        $this->addSql('DROP INDEX IDX_CD52224A12469DE2 ON music');
        $this->addSql('ALTER TABLE music DROP category_id');
    }
}
