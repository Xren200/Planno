<?php
//fini
namespace App\Tests\Command;

use App\Entity\Agent;
use App\Entity\Absence;
use App\Entity\ConfigParam;
use App\Entity\AbsenceDocument;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\CommandTestCase;

class AbsenceDeleteDocumentsCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder->delete(Agent::class);
    }

    public function testConfigOff_NoDeletion(): void
    {
        var_dump($_SERVER['APP_ENV']);
        self::bootKernel();
        $this->setParam('Absences-DelaiSuppressionDocuments', 0);

        $em= $GLOBALS['entityManager'];

        $old = (new AbsenceDocument())
            ->setFilename('old')
            ->setDate(new \DateTime('2022-10-09'))
            ->setAbsenceId(100);

        $em->persist($old);
        $em->flush();

        $this->execute();

        $em->clear();

        $info = $em->getRepository(AbsenceDocument::class)->findOneBy(array('filename' => 'old'));

        $this->assertEquals('old',  $info->getFilename(), 'filename is fichier');
        //$this->assertEquals($date, $info->getDate(), "date is 09/10/2022");
        $this->assertEquals(100, $info->getAbsenceId(), 'absence_id is 100');
        $this->assertStringContainsString('/src/Entity/../../var/upload/test/absences/', $info->upload_dir(), 'upload dir ok');
        $still = $em->getRepository(AbsenceDocument::class)->find($old->getId());

    }

    public function testHaveOneToDeleteAndOneNo(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', 1);

        $entityManager = $GLOBALS['entityManager'];
        $builder = $this->builder;
        $builder->delete(Agent::class);
        $agent = $builder->build(Agent::class, array('login' => 'jdevoe'));
        $builder->delete(AbsenceDocument::class);

        $now = new \DateTime();
        $past = \DateTime::createFromFormat("d/m/Y", '09/10/2022');

        $abs_doc_now = new AbsenceDocument();
        $abs_doc_now->setFilename('fichier_now');
        $abs_doc_now->setDate($now);
        $abs_doc_now->setAbsenceId(100);

        $abs_doc_past = new AbsenceDocument();
        $abs_doc_past->setFilename('fichier_past');
        $abs_doc_past->setDate($past);
        $abs_doc_past->setAbsenceId(100);

        $entityManager->persist($abs_doc_past);
        $entityManager->persist($abs_doc_now);
        $entityManager->flush();

        foreach ([$abs_doc_now, $abs_doc_past] as $doc) {
            $projectDir = self::getContainer()->getParameter('kernel.project_dir');
            $alt = $projectDir . '/var/upload/test/absences/' . $doc->getAbsenceId() . '/' . $doc->getId() . '/' . $doc->getFilename();
            if (!is_dir(dirname($alt))) {
                mkdir(dirname($alt), 0777, true);
            }
            
            file_put_contents($alt, 'dummy');
        }

        $this->execute();

        $entityManager->clear();

        $deleted = $entityManager->getRepository(AbsenceDocument::class)
            ->findOneBy(['filename' => 'fichier_past']);
        $this->assertNull($deleted, 'Old doc should be deleted by cron');

        $kept = $entityManager->getRepository(AbsenceDocument::class)
            ->findOneBy(['filename' => 'fichier_now']); 
        $this->assertNotNull($kept, 'Recent doc should be kept by cron');

    }

    public function testHaveNothingToDelete_OneNew(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', 2);

        $entityManager = $GLOBALS['entityManager'];
        $builder = $this->builder;
        $builder->delete(Agent::class);
        $agent = $builder->build(Agent::class, array('login' => 'jdevoe'));
        $builder->delete(AbsenceDocument::class);

        $now = new \DateTime();

        $abs_doc_now = new AbsenceDocument();
        $abs_doc_now->setFilename('fichier_now');
        $abs_doc_now->setDate($now);
        $abs_doc_now->setAbsenceId(100);

        $entityManager->persist($abs_doc_now);
        $entityManager->flush();
        $this->execute();

        $entityManager->clear();

        $kept = $entityManager->getRepository(AbsenceDocument::class)
            ->findOneBy(['filename' => 'fichier_now']); 
        $this->assertNotNull($kept, 'Recent doc should be kept by cron');

    }

    public function testHaveNothingToDelete_Nothing(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', 1);

        $entityManager = $GLOBALS['entityManager'];
        $builder = $this->builder;
        $builder->delete(Agent::class);
        $agent = $builder->build(Agent::class, array('login' => 'jdevoe'));
        $builder->delete(AbsenceDocument::class);

        $this->execute();

        $entityManager->clear();

        $deleted = $entityManager->getRepository(AbsenceDocument::class)
            ->findAll();
        $this->assertEmpty($deleted, 'Old doc should be deleted by cron');

    }

    private function execute(): void
    {
         $application = new Application(self::$kernel);
 
         $command = $application->find('app:absence:delete-documents');
         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);
         $commandTester->assertCommandIsSuccessful();

    }
}
