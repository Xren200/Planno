<?php

namespace App\Tests\Command;

use DateTime;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\WorkingHour;
use Tests\PLBWebTestCase;

class WorkingHourDailyCommandTest extends PLBWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder->delete(WorkingHour::class);
    }

    public function testSomething(): void
    {
        $this->setUpPantherClient();
        $WorkingHour1 = $this->builder->build(WorkingHour::class,array(
            'perso_id' => 1,
            'actuel' => 0,
            'valide' => 1,
            'debut' => new DateTime("2000-01-01"),
        )); 

        $WorkingHour2 = $this->builder->build(WorkingHour::class,array(
            'perso_id' => 1,
            'actuel' => 1,
            'valide' => 1,
            'debut' => new DateTime("2000-01-01"),
        )); 

        $WorkingHour3 = $this->builder->build(WorkingHour::class,array(
            'perso_id' => 1,
            'actuel' => 0,
            'valide' => 1,
            'debut' => new DateTime(),
        )); 

        $WorkingHour4 = $this->builder->build(WorkingHour::class,array(
            'perso_id' => 1,
            'actuel' => 0,
            'valide' => 1,
            'debut' => new DateTime(),
        )); 

        $entityManager = $GLOBALS['entityManager'];

        $entityManager->persist($WorkingHour1);
        $entityManager->persist($WorkingHour2);
        $entityManager->persist($WorkingHour3);
        $entityManager->persist($WorkingHour4);
        $entityManager->flush();

        $id1 = $WorkingHour1->getId();
        $id2 = $WorkingHour2->getId();
        $id3 = $WorkingHour3->getId();
        $id4 = $WorkingHour4->getId();

        $repo = $entityManager->getRepository(WorkingHour::class);
        $wh1 = $repo->find( $id1);
        $wh2 = $repo->find( $id2);
        $wh3 = $repo->find( $id3);
        $wh4 = $repo->find( $id4);

        $this->assertEquals(0, $wh1->isCurrent(), '');
        $this->assertEquals(1, $wh2->isCurrent(), '');
        $this->assertEquals(0, $wh3->isCurrent(), '');
        $this->assertEquals(0, $wh4->isCurrent(), '');

        $this->execute();
        $entityManager->clear();

        $repo = $entityManager->getRepository(WorkingHour::class);
        $wh11 = $repo->find( $id1);
        $wh22 = $repo->find( $id2);
        $wh33 = $repo->find( $id3);
        $wh44 = $repo->find( $id4);

        $this->assertEquals(0, $wh11->isCurrent(), '');
        $this->assertEquals(0, $wh22->isCurrent(), '');
        $this->assertEquals(0, $wh33->isCurrent(), '');
        $this->assertEquals(1, $wh44->isCurrent(), '');
    }

    private function execute(): void
    {
         $application = new Application(self::$kernel);
 
         $command = $application->find('app:workinghour:daily');
         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);

         $commandTester->assertCommandIsSuccessful();
         $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Weekly planning records have been successfully updated for all employees.', $output);

    }


}
