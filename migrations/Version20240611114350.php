<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240611114350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content VARCHAR(400) NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_9474526C4584665A (product_id), INDEX IDX_9474526CF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        // $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, status VARCHAR(25) NOT NULL, total_price INT NOT NULL, payment_method VARCHAR(25) NOT NULL, customer_id INT DEFAULT NULL, INDEX IDX_F52993989395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        // $this->addSql('CREATE TABLE order_product (id INT AUTO_INCREMENT NOT NULL, order_reference_id INT DEFAULT NULL, product_id INT DEFAULT NULL, INDEX IDX_2530ADE612854AC3 (order_reference_id), INDEX IDX_2530ADE64584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        // $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, price INT DEFAULT NULL, active TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL, stripe_product_id VARCHAR(255) DEFAULT NULL, stripe_price_id VARCHAR(255) DEFAULT NULL, category LONGTEXT DEFAULT NULL, repas LONGTEXT DEFAULT NULL, repas_type LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        // $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        // $this->addSql('CREATE TABLE user_profile (id INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, date_de_naissance DATE DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, code_postal INT DEFAULT NULL, photo_de_profil VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_D95AB405A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        // $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        // $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        // $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        // $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE612854AC3 FOREIGN KEY (order_reference_id) REFERENCES `order` (id)');
        // $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE64584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        // $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4584665A');
        // $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF675F31B');
        // $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        // $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE612854AC3');
        // $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE64584665A');
        // $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
        // $this->addSql('DROP TABLE comment');
        // $this->addSql('DROP TABLE `order`');
        // $this->addSql('DROP TABLE order_product');
        // $this->addSql('DROP TABLE product');
        // $this->addSql('DROP TABLE user');
        // $this->addSql('DROP TABLE user_profile');
    }
}
