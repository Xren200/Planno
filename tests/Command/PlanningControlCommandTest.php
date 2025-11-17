<?php

namespace App\Tests\Command;

use App\Entity\PlanningPosition;
use App\Entity\PlanningPositionLock;
use App\Entity\PlanningPositionTabAffectation;
use App\Entity\PlanningPositionTab;
use App\Entity\Position;
use Tests\PLBWebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
class PlanningControlCommandTest extends PLBWebTestCase
{

    public function testSomething(): void
    {
        $this->setParam('Rappels-Actifs', 1);
        $this->setParam('Multisites-nombre', 1);
        $this->setParam('Multisites-site1', 1);
        $this->setParam('Multisites-site2', 0);
        $this->setParam('Multisites-site3', 0);
        $this->setParam('Multisites-site4', 0);
        $this->setParam('Rappels-Jours', 1);
        $this->setParam('Dimanche', 0);
        $this->setParam('Rappels-Renfort', 0);
        $this->setParam('Conges-Enable', 0);
        $this->setParam('Mail-Planning', 'xinying.sun@biblibre.com');
        $today = new \DateTime('');
        $ppta = $this->builder->build(PlanningPositionTabAffectation::class, [
            'date' => $today, 'tableau' => 1, 'site' => 1
        ]);
        $ppl = $this->builder->build(PlanningPositionLock::class, [
            'date' => $today, 'verrou' => '1', 'perso' => '0',
            'verrou2' => 1, 'validation2' => new \DateTime('2025-11-17 10:19:36'), 'perso2' => '1','vivier'=>'0','site'=>'1'
        ]);
        $this->builder->build(PlanningPositionTab::class, [
            'id' => 1, 'tableau'=>1,
            'nom' => 'Scolaire : Mercredi - Samedi',
            'site'=> 1
        ]);
        $this->builder->build(Position::class, [
            'nom' => 'toto', 'obligatoire'=>'Obligatoire'
        ]);
        $this->builder->build(PlanningPosition::class, [
            'perso_id' => 19, 'date'=>$today,
            'site'=>1, 'debut' => new \DateTime($today->format('Y-m-d') . ' 09:00:00'), 'fin' => new \DateTime($today->format('Y-m-d') . ' 10:00:00')
        ]);
        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();
        $this->execute();
        $mail = \CJMail::$lastMail;
        $this->assertNotNull($mail, "Aucun mail n'a été envoyé");
        $this->assertEquals(['xinying.sun@biblibre.com'], $mail['to']);
        $this->assertStringContainsString("Plannings du", $mail['subject']);
        $this->assertStringContainsString("ne sont pas occupés", $mail['message']);
    }

     private function execute(): void
    {
         $application = new Application(self::$kernel);
 
         $entityManager = $GLOBALS['entityManager'];
 
         $command = $application->find('app:planning:control');
         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);

         $commandTester->assertCommandIsSuccessful();
         $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Planning check completed successfully; notification email sent.', $output);

    }
    
}
