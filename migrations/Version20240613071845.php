<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240613071845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_7DC4A99C5E3B6456');
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_7DC4A99C9E5E7277');
        $this->addSql('ALTER TABLE order_product ADD quantity INT NOT NULL, CHANGE product_id product_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_7dc4a99c5e3b6456 ON order_product');
        $this->addSql('CREATE INDEX IDX_2530ADE64584665A ON order_product (product_id)');
        $this->addSql('DROP INDEX idx_7dc4a99c9e5e7277 ON order_product');
        $this->addSql('CREATE INDEX IDX_2530ADE612854AC3 ON order_product (order_reference_id)');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_7DC4A99C5E3B6456 FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_7DC4A99C9E5E7277 FOREIGN KEY (order_reference_id) REFERENCES `order` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE64584665A');
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE612854AC3');
        $this->addSql('ALTER TABLE order_product DROP quantity, CHANGE product_id product_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_2530ade612854ac3 ON order_product');
        $this->addSql('CREATE INDEX IDX_7DC4A99C9E5E7277 ON order_product (order_reference_id)');
        $this->addSql('DROP INDEX idx_2530ade64584665a ON order_product');
        $this->addSql('CREATE INDEX IDX_7DC4A99C5E3B6456 ON order_product (product_id)');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE64584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE612854AC3 FOREIGN KEY (order_reference_id) REFERENCES `order` (id)');
    }
}
