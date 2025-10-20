<?php

namespace App\Tests\Command;

use App\Entity\Agent;
use App\Entity\Absence;
use App\Entity\ConfigParam;
use App\Entity\AbsenceDocument;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Tests\PLBWebTestCase;

class AbsenceDeleteDocumentsCommandTest extends PLBWebTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder->delete(Agent::class);
    }

    public function configOff(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', null);
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/');

        $this->assertSelectorTextContains('h1', 'Hello World');
    }

    public function haveOneToDeleteAndOneNo(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', 1);

        $entityManager = $this->entityManager;
        $builder = $this->builder;
        $builder->delete(Agent::class);
        $agent = $builder->build(Agent::class, array('login' => 'jdevoe'));
        $builder->delete(AbsenceDocument::class);
        $this->logInAgent($agent, array(100));

        $now = new \DateTime();
        $past = \DateTime::createFromFormat("d/m/Y", '09/10/2022');

        $abs_doc_now = new AbsenceDocument();
        $abs_doc_now->setFilename('fichier');
        $abs_doc_now->setDate($now);
        $abs_doc_now->setAbsenceId(100);

        $abs_doc_past = new AbsenceDocument();
        $abs_doc_past->setFilename('fichier');
        $abs_doc_past->setDate($now);
        $abs_doc_past->setAbsenceId(100);

        $entityManager->persist($abs_doc_past);
        $entityManager->persist($abs_doc_now);
        $entityManager->flush();
        $this->execute();


        $info = $entityManager->getRepository(AbsenceDocument::class)->findOneBy(array('filename' => 'fichier'));

        //$this->assertEquals('fichier', $info->getFilename(), "filename is fichier");
        //$this->assertEquals($date, $info->getDate(), "date is 09/10/2022");
        //$this->assertEquals(100, $info->getAbsenceId(), 'absence_id is 100');
        //$this->assertStringContainsString('/src/Entity/../../var/upload/test/absences/', $info->upload_dir(), 'upload dir ok');

    }

    public function haveNothingToDelete_OneNew(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', 1);

        $entityManager = $this->entityManager;
        $builder = $this->builder;
        $builder->delete(Agent::class);
        $agent = $builder->build(Agent::class, array('login' => 'jdevoe'));
        $builder->delete(AbsenceDocument::class);
        $this->logInAgent($agent, array(100));

        $now = new \DateTime();

        $abs_doc_now = new AbsenceDocument();
        $abs_doc_now->setFilename('fichier');
        $abs_doc_now->setDate($now);
        $abs_doc_now->setAbsenceId(100);

        $entityManager->persist($abs_doc_now);
        $entityManager->flush();
        $this->execute();

        $info = $entityManager->getRepository(AbsenceDocument::class)->findOneBy(array('filename' => 'fichier'));
    }

    public function haveNothingToDelete_Nothing(): void
    {
        $this->setParam('Absences-DelaiSuppressionDocuments', 1);

        $entityManager = $this->entityManager;
        $builder = $this->builder;
        $builder->delete(Agent::class);
        $agent = $builder->build(Agent::class, array('login' => 'jdevoe'));
        $builder->delete(AbsenceDocument::class);
        $this->logInAgent($agent, array(100));

        $this->execute();

        $info = $entityManager->getRepository(AbsenceDocument::class)->findOneBy(array('filename' => 'fichier'));
    }

         private function execute(): void
     {
 
         $kernel = self::bootKernel();
         $application = new Application(self::$kernel);
 
         $entityManager = $GLOBALS['entityManager'];
 
 
         $command = $application->find('app:holiday:reminder');
         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);
 
         $commandTester->assertCommandIsSuccessful();
 
         // the output of the command in the console
         $output = $commandTester->getDisplay();
 
     }
}
