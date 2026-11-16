<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116133507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cibler (id INT AUTO_INCREMENT NOT NULL, est_principal TINYINT NOT NULL, exercice_id INT NOT NULL, groupe_musculaire_id INT NOT NULL, INDEX IDX_DDE34B5589D40298 (exercice_id), INDEX IDX_DDE34B551287564D (groupe_musculaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, est_masque TINYINT NOT NULL, utilisateur_id INT NOT NULL, programme_id INT NOT NULL, INDEX IDX_67F068BCFB88E14F (utilisateur_id), INDEX IDX_67F068BC62BB7AEE (programme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exercice (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, difficulte VARCHAR(30) DEFAULT NULL, id_wger INT DEFAULT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exercice_materiel (exercice_id INT NOT NULL, materiel_id INT NOT NULL, INDEX IDX_B048D4B289D40298 (exercice_id), INDEX IDX_B048D4B216880AAF (materiel_id), PRIMARY KEY (exercice_id, materiel_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exercice_programme (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, series_cibles INT NOT NULL, reps_cibles VARCHAR(20) NOT NULL, repos_secondes INT NOT NULL, notes LONGTEXT DEFAULT NULL, jour_programme_id INT NOT NULL, exercice_id INT NOT NULL, INDEX IDX_9F1352E936C3DE5D (jour_programme_id), INDEX IDX_9F1352E989D40298 (exercice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE groupe_musculaire (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, slug VARCHAR(60) NOT NULL, zone_corps VARCHAR(50) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jour_programme (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, position INT NOT NULL, programme_id INT NOT NULL, INDEX IDX_2FBF4E5C62BB7AEE (programme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE materiel (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, slug VARCHAR(60) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mensuration (id INT AUTO_INCREMENT NOT NULL, date_mesure DATE NOT NULL, poids_kg NUMERIC(5, 2) NOT NULL, masse_grasse_pct NUMERIC(4, 1) DEFAULT NULL, tour_taille_cm NUMERIC(5, 1) DEFAULT NULL, tour_bras_cm NUMERIC(4, 1) DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_C6AB984AFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE noter (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, date_note DATETIME NOT NULL, utilisateur_id INT NOT NULL, programme_id INT NOT NULL, INDEX IDX_761C961AFB88E14F (utilisateur_id), INDEX IDX_761C961A62BB7AEE (programme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE programme (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, objectif VARCHAR(50) DEFAULT NULL, niveau VARCHAR(30) DEFAULT NULL, est_public TINYINT NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_3DDCB9FFFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favori (programme_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_EF85A2CC62BB7AEE (programme_id), INDEX IDX_EF85A2CCFB88E14F (utilisateur_id), PRIMARY KEY (programme_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, date_seance DATETIME NOT NULL, duree_min INT DEFAULT NULL, ressenti VARCHAR(30) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, utilisateur_id INT NOT NULL, jour_programme_id INT DEFAULT NULL, INDEX IDX_DF7DFD0EFB88E14F (utilisateur_id), INDEX IDX_DF7DFD0E36C3DE5D (jour_programme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE serie (id INT AUTO_INCREMENT NOT NULL, numero_serie INT NOT NULL, repetitions INT NOT NULL, charge_kg NUMERIC(6, 2) NOT NULL, rpe INT DEFAULT NULL, seance_id INT NOT NULL, exercice_id INT NOT NULL, INDEX IDX_AA3A9334E3797A94 (seance_id), INDEX IDX_AA3A933489D40298 (exercice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, pseudo VARCHAR(50) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, est_actif TINYINT NOT NULL, date_inscription DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE cibler ADD CONSTRAINT FK_DDE34B5589D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
        $this->addSql('ALTER TABLE cibler ADD CONSTRAINT FK_DDE34B551287564D FOREIGN KEY (groupe_musculaire_id) REFERENCES groupe_musculaire (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id)');
        $this->addSql('ALTER TABLE exercice_materiel ADD CONSTRAINT FK_B048D4B289D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exercice_materiel ADD CONSTRAINT FK_B048D4B216880AAF FOREIGN KEY (materiel_id) REFERENCES materiel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exercice_programme ADD CONSTRAINT FK_9F1352E936C3DE5D FOREIGN KEY (jour_programme_id) REFERENCES jour_programme (id)');
        $this->addSql('ALTER TABLE exercice_programme ADD CONSTRAINT FK_9F1352E989D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
        $this->addSql('ALTER TABLE jour_programme ADD CONSTRAINT FK_2FBF4E5C62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id)');
        $this->addSql('ALTER TABLE mensuration ADD CONSTRAINT FK_C6AB984AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE noter ADD CONSTRAINT FK_761C961AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE noter ADD CONSTRAINT FK_761C961A62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id)');
        $this->addSql('ALTER TABLE programme ADD CONSTRAINT FK_3DDCB9FFFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CC62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0EFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E36C3DE5D FOREIGN KEY (jour_programme_id) REFERENCES jour_programme (id)');
        $this->addSql('ALTER TABLE serie ADD CONSTRAINT FK_AA3A9334E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE serie ADD CONSTRAINT FK_AA3A933489D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cibler DROP FOREIGN KEY FK_DDE34B5589D40298');
        $this->addSql('ALTER TABLE cibler DROP FOREIGN KEY FK_DDE34B551287564D');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC62BB7AEE');
        $this->addSql('ALTER TABLE exercice_materiel DROP FOREIGN KEY FK_B048D4B289D40298');
        $this->addSql('ALTER TABLE exercice_materiel DROP FOREIGN KEY FK_B048D4B216880AAF');
        $this->addSql('ALTER TABLE exercice_programme DROP FOREIGN KEY FK_9F1352E936C3DE5D');
        $this->addSql('ALTER TABLE exercice_programme DROP FOREIGN KEY FK_9F1352E989D40298');
        $this->addSql('ALTER TABLE jour_programme DROP FOREIGN KEY FK_2FBF4E5C62BB7AEE');
        $this->addSql('ALTER TABLE mensuration DROP FOREIGN KEY FK_C6AB984AFB88E14F');
        $this->addSql('ALTER TABLE noter DROP FOREIGN KEY FK_761C961AFB88E14F');
        $this->addSql('ALTER TABLE noter DROP FOREIGN KEY FK_761C961A62BB7AEE');
        $this->addSql('ALTER TABLE programme DROP FOREIGN KEY FK_3DDCB9FFFB88E14F');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CC62BB7AEE');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCFB88E14F');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0EFB88E14F');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E36C3DE5D');
        $this->addSql('ALTER TABLE serie DROP FOREIGN KEY FK_AA3A9334E3797A94');
        $this->addSql('ALTER TABLE serie DROP FOREIGN KEY FK_AA3A933489D40298');
        $this->addSql('DROP TABLE cibler');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE exercice');
        $this->addSql('DROP TABLE exercice_materiel');
        $this->addSql('DROP TABLE exercice_programme');
        $this->addSql('DROP TABLE groupe_musculaire');
        $this->addSql('DROP TABLE jour_programme');
        $this->addSql('DROP TABLE materiel');
        $this->addSql('DROP TABLE mensuration');
        $this->addSql('DROP TABLE noter');
        $this->addSql('DROP TABLE programme');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE serie');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
