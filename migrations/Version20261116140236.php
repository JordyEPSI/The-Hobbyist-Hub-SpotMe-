<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116140236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Règles de suppression : supprimer un membre, un programme, un jour ou une séance supprime les données qui en dépendent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `FK_67F068BC62BB7AEE`');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `FK_67F068BCFB88E14F`');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exercice_programme DROP FOREIGN KEY `FK_9F1352E936C3DE5D`');
        $this->addSql('ALTER TABLE exercice_programme ADD CONSTRAINT FK_9F1352E936C3DE5D FOREIGN KEY (jour_programme_id) REFERENCES jour_programme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jour_programme DROP FOREIGN KEY `FK_2FBF4E5C62BB7AEE`');
        $this->addSql('ALTER TABLE jour_programme ADD CONSTRAINT FK_2FBF4E5C62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mensuration DROP FOREIGN KEY `FK_C6AB984AFB88E14F`');
        $this->addSql('ALTER TABLE mensuration ADD CONSTRAINT FK_C6AB984AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE noter DROP FOREIGN KEY `FK_761C961A62BB7AEE`');
        $this->addSql('ALTER TABLE noter DROP FOREIGN KEY `FK_761C961AFB88E14F`');
        $this->addSql('ALTER TABLE noter ADD CONSTRAINT FK_761C961A62BB7AEE FOREIGN KEY (programme_id) REFERENCES programme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE noter ADD CONSTRAINT FK_761C961AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE programme DROP FOREIGN KEY `FK_3DDCB9FFFB88E14F`');
        $this->addSql('ALTER TABLE programme ADD CONSTRAINT FK_3DDCB9FFFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY `FK_DF7DFD0E36C3DE5D`');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY `FK_DF7DFD0EFB88E14F`');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E36C3DE5D FOREIGN KEY (jour_programme_id) REFERENCES jour_programme (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0EFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE serie DROP FOREIGN KEY `FK_AA3A9334E3797A94`');
        $this->addSql('ALTER TABLE serie ADD CONSTRAINT FK_AA3A9334E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC62BB7AEE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `FK_67F068BCFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `FK_67F068BC62BB7AEE` FOREIGN KEY (programme_id) REFERENCES programme (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE exercice_programme DROP FOREIGN KEY FK_9F1352E936C3DE5D');
        $this->addSql('ALTER TABLE exercice_programme ADD CONSTRAINT `FK_9F1352E936C3DE5D` FOREIGN KEY (jour_programme_id) REFERENCES jour_programme (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE jour_programme DROP FOREIGN KEY FK_2FBF4E5C62BB7AEE');
        $this->addSql('ALTER TABLE jour_programme ADD CONSTRAINT `FK_2FBF4E5C62BB7AEE` FOREIGN KEY (programme_id) REFERENCES programme (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mensuration DROP FOREIGN KEY FK_C6AB984AFB88E14F');
        $this->addSql('ALTER TABLE mensuration ADD CONSTRAINT `FK_C6AB984AFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE noter DROP FOREIGN KEY FK_761C961AFB88E14F');
        $this->addSql('ALTER TABLE noter DROP FOREIGN KEY FK_761C961A62BB7AEE');
        $this->addSql('ALTER TABLE noter ADD CONSTRAINT `FK_761C961AFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE noter ADD CONSTRAINT `FK_761C961A62BB7AEE` FOREIGN KEY (programme_id) REFERENCES programme (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE programme DROP FOREIGN KEY FK_3DDCB9FFFB88E14F');
        $this->addSql('ALTER TABLE programme ADD CONSTRAINT `FK_3DDCB9FFFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0EFB88E14F');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E36C3DE5D');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT `FK_DF7DFD0EFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT `FK_DF7DFD0E36C3DE5D` FOREIGN KEY (jour_programme_id) REFERENCES jour_programme (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE serie DROP FOREIGN KEY FK_AA3A9334E3797A94');
        $this->addSql('ALTER TABLE serie ADD CONSTRAINT `FK_AA3A9334E3797A94` FOREIGN KEY (seance_id) REFERENCES seance (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
