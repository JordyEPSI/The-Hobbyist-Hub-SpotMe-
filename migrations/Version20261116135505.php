<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116135505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alignement sur le MCD : UTILISER en 1,1, identifiants composés de CIBLER et NOTER, colonne mot_de_passe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercice_materiel DROP FOREIGN KEY `FK_B048D4B216880AAF`');
        $this->addSql('ALTER TABLE exercice_materiel DROP FOREIGN KEY `FK_B048D4B289D40298`');
        $this->addSql('DROP TABLE exercice_materiel');
        $this->addSql('ALTER TABLE cibler MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE cibler DROP id, DROP PRIMARY KEY, ADD PRIMARY KEY (exercice_id, groupe_musculaire_id)');
        $this->addSql('ALTER TABLE exercice ADD materiel_id INT NOT NULL');
        $this->addSql('ALTER TABLE exercice ADD CONSTRAINT FK_E418C74D16880AAF FOREIGN KEY (materiel_id) REFERENCES materiel (id)');
        $this->addSql('CREATE INDEX IDX_E418C74D16880AAF ON exercice (materiel_id)');
        $this->addSql('ALTER TABLE noter MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE noter DROP id, DROP PRIMARY KEY, ADD PRIMARY KEY (utilisateur_id, programme_id)');
        $this->addSql('ALTER TABLE utilisateur DROP est_actif, CHANGE password mot_de_passe VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B386CC499D ON utilisateur (pseudo)');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_identifier_email TO UNIQ_1D1C63B3E7927C74');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE exercice_materiel (exercice_id INT NOT NULL, materiel_id INT NOT NULL, INDEX IDX_B048D4B216880AAF (materiel_id), INDEX IDX_B048D4B289D40298 (exercice_id), PRIMARY KEY (exercice_id, materiel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE exercice_materiel ADD CONSTRAINT `FK_B048D4B216880AAF` FOREIGN KEY (materiel_id) REFERENCES materiel (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exercice_materiel ADD CONSTRAINT `FK_B048D4B289D40298` FOREIGN KEY (exercice_id) REFERENCES exercice (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cibler ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE exercice DROP FOREIGN KEY FK_E418C74D16880AAF');
        $this->addSql('DROP INDEX IDX_E418C74D16880AAF ON exercice');
        $this->addSql('ALTER TABLE exercice DROP materiel_id');
        $this->addSql('ALTER TABLE noter ADD id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX UNIQ_1D1C63B386CC499D ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD est_actif TINYINT NOT NULL, CHANGE mot_de_passe password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_1d1c63b3e7927c74 TO UNIQ_IDENTIFIER_EMAIL');
    }
}
