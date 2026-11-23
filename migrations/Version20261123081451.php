<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261123081451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Slugs et identifiant wger uniques (exercice, matériel, groupe musculaire, programme)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E418C74D989D9B62 ON exercice (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E418C74DB795096C ON exercice (id_wger)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BEAB78AF989D9B62 ON groupe_musculaire (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_18D2B091989D9B62 ON materiel (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3DDCB9FF989D9B62 ON programme (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_E418C74D989D9B62 ON exercice');
        $this->addSql('DROP INDEX UNIQ_E418C74DB795096C ON exercice');
        $this->addSql('DROP INDEX UNIQ_BEAB78AF989D9B62 ON groupe_musculaire');
        $this->addSql('DROP INDEX UNIQ_18D2B091989D9B62 ON materiel');
        $this->addSql('DROP INDEX UNIQ_3DDCB9FF989D9B62 ON programme');
    }
}
