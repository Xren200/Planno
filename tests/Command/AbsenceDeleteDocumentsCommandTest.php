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
use Tests\PLBWebTestCase;

class AbsenceDeleteDocumentsCommandTest extends PLBWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder->delete(Agent::class);
    }

    public function testConfigOff_NoDeletion(): void
    {
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
        $this->logInAgent($agent, array(100));

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
        $this->logInAgent($agent, array(100));

        $this->execute();

        $entityManager->clear();

        $deleted = $entityManager->getRepository(AbsenceDocument::class)
            ->findAll();
        $this->assertEmpty($deleted, 'Old doc should be deleted by cron');

    }

    private function execute(): void
    {
        //  $kernel = self::bootKernel();
        //  $application = new Application(self::$kernel);
 
        //  $entityManager = $GLOBALS['entityManager'];
 
        //  $command = $application->find('app:absence:delete-documents');
        //  $commandTester = new CommandTester($command);
        //  $commandTester->execute([
        //      'command'  => $command->getName()
        //  ], [
        //      'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        //  ]);
        //  $commandTester->assertCommandIsSuccessful();
        //  $output = $commandTester->getDisplay();

        // $output = shell_exec('php public/absences/cron.deleteOldDocuments.php');
        //$this->assertStringContainsString('Hello World', $output);

        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $script = $projectDir.'/public/absences/cron.deleteOldDocuments.php';

        $php = \PHP_BINARY;
        $proc = new Process([$php, $script], $projectDir, [
            'APP_ENV' => 'test',
            'APP_DEBUG' => '0',
        ]);
        $proc->run();

        if (!$proc->isSuccessful()) {
            $this->fail("Cron failed\nEXIT={$proc->getExitCode()}\nSTDOUT:\n{$proc->getOutput()}\nSTDERR:\n{$proc->getErrorOutput()}");
        }
    
    }
}
