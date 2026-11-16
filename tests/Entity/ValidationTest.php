<?php

namespace App\Tests\Entity;

use App\Entity\Noter;
use App\Entity\Programme;
use App\Entity\Serie;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    /** @return list<string> propriétés en erreur */
    private function erreurs(object $entite): array
    {
        $proprietes = [];
        foreach ($this->validator->validate($entite) as $violation) {
            $proprietes[] = $violation->getPropertyPath();
        }

        return $proprietes;
    }

    public function testUneNoteDoitEtreEntre1Et5(): void
    {
        $note = (new Noter())->setNote(6)->setDateNote(new \DateTimeImmutable());
        self::assertContains('note', $this->erreurs($note));

        $note->setNote(4);
        self::assertNotContains('note', $this->erreurs($note));
    }

    public function testUneSerieRefuseUneChargeNegativeEtZeroRepetition(): void
    {
        $serie = (new Serie())->setNumeroSerie(1)->setRepetitions(0)->setChargeKg('-5');
        $erreurs = $this->erreurs($serie);

        self::assertContains('chargeKg', $erreurs);
        self::assertContains('repetitions', $erreurs);
    }

    public function testUnProgrammeSansTitreEstRefuseAvecUnMessageEnFrancais(): void
    {
        $programme = (new Programme())->setTitre('')->setSlug('mon-programme')->setEstPublic(true);
        $violations = $this->validator->validate($programme);

        $messages = [];
        foreach ($violations as $violation) {
            if ('titre' === $violation->getPropertyPath()) {
                $messages[] = $violation->getMessage();
            }
        }

        self::assertSame(['Cette valeur ne doit pas être vide.'], $messages);
    }
}
