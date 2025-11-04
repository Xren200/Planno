<?php

namespace App\Tests\Command;
use Tests\PLBWebTestCase;
use App\Entity\Agent;
use App\Entity\Holiday;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

class HolidayResetCreditsCommandTest extends PLBWebTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder->delete(Agent::class);
    }
    public function testConfigOn(): void
    {
        $this->setParam('Conges-transfer-comp-time', 1);
        $this->setUpPantherClient();
        $jdupont = $this->builder->build(Agent::class, array(
            'id' => 100,
            'login' => 'jduponttt', 'nom' => 'Duponttt', 'prenom' => 'Jean', 'temps'=>'',
            'droits' => array(3,4,5,6,9,17,20,21,22,23,25,99,100,201,202,301,302,401,402,501,502,601,602,701,801,802,901,1001,1002,1101,1201,1301),
            'sites' => '["1"]',
            'conges_credit' => "30",
            'conges_reliquat' => "40",
            'conges_anticipation' => "10",
            'comp_time' => "20",
            'conges_annuel' => "50",
        ));
        $conge_j = $this->builder->build(Holiday::class, array(
            'perso_id' => 100,
            'debut' => new \DateTime('2023-01-01'),
            'fin' => new \DateTime('2023-12-31'),
            'solde_prec' => 0,
            'recup_prec' => 1,
            'reliquat_prec' => 2,
            'anticipation_prec' => 3,
            'solde_actuel' => 4,
            'recup_actuel' => 5,
            'reliquat_actuel' => 6,
            'anticipation_actuel' => 7,
        ));

        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();

        $agentBefore = $entityManager->getRepository(Agent::class)->findOneBy(['login' => 'jduponttt']);
        $this->assertEquals(40, (int)$agentBefore->getRemainder(), 'Before the command Holiday Credit should be 40');
        $this->assertEquals(20, (int)$agentBefore->getCompTime(), 'Before the command comp_time should be 20');
        $this->assertEquals(30, (int)$agentBefore->getHolidayCredit(), 'Before the command Holiday Credit should be 30');
        
        $congeBefore = $entityManager->getRepository(Holiday::class)->findOneBy(['perso_id' => 100]);
        $this->assertEquals(6, (int)$congeBefore->getActualRemainder(), 'After the command Holiday Credit should be 6');


        $this->execute();
        $entityManager->clear();

        $agentAfter = $entityManager->getRepository(Agent::class)->findOneBy(['login' => 'jduponttt']);
        $this->assertEquals(50, (int)$agentAfter->getRemainder(), 'After the command Holiday Credit should be 5.7');
        $this->assertEquals(0, (int)$agentAfter->getCompTime(), 'After the command comp_time should be 0');
        $this->assertEquals(40, (int)$agentAfter->getHolidayCredit(), 'After the command Holiday Credit should be 3.8');

        $congeAfter = $entityManager->getRepository(Holiday::class)->findOneBy(['perso_id' => 100]);
        $this->assertEquals(6, (int)$congeAfter->getActualRemainder(), 'After the command Holiday Credit should be 6');
    }

    public function testConfigOff(): void
    {
        $this->setParam('Conges-transfer-comp-time', 0);
        $this->setUpPantherClient();
        $jdupont = $this->builder->build(Agent::class, array(
            'id' => 100,
            'login' => 'jduponttt', 'nom' => 'Duponttt', 'prenom' => 'Jean', 'temps'=>'',
            'droits' => array(3,4,5,6,9,17,20,21,22,23,25,99,100,201,202,301,302,401,402,501,502,601,602,701,801,802,901,1001,1002,1101,1201,1301),
            'sites' => '["1"]',
            'conges_credit' => "27",
            'conges_reliquat' => "38",
            'conges_anticipation' => "0",
            'comp_time' => "19",
            'conges_annuel' => "54",
        ));
        $conge_j = $this->builder->build(Holiday::class, array(
            'perso_id' => 100,
            'debut' => new \DateTime('2023-01-01'),
            'fin' => new \DateTime('2023-12-31'),
            'solde_prec' => 0,
            'recup_prec' => 1,
            'reliquat_prec' => 2,
            'anticipation_prec' => 3,
            'solde_actuel' => 4,
            'recup_actuel' => 5,
            'reliquat_actuel' => 6,
            'anticipation_actuel' => 7,
        ));

        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();
        $agentBefore = $entityManager->getRepository(Agent::class)->findOneBy(['login' => 'jduponttt']);
        $congeBefore = $entityManager->getRepository(Holiday::class)->findOneBy(['perso_id' => 100]);
        $this->assertEquals(6, (int)$congeBefore->getActualRemainder(), 'After the command Holiday Credit should be 6');
        $this->assertEquals(38, (int)$agentBefore->getRemainder(), 'Before the command Holiday Credit should be 40');
        //$this->assertEquals(20, (int)$agentBefore->getCompTime(), 'Before the command comp_time should be 20');
        //$this->assertEquals(30, (int)$agentBefore->getHolidayCredit(), 'Before the command Holiday Credit should be 30');

        $this->execute();
        $entityManager->clear();

        $agentAfter = $entityManager->getRepository(Agent::class)->findOneBy(['login' => 'jduponttt']);
        $this->assertEquals(27, (int)$agentAfter->getRemainder(), 'After the command Holiday Credit should be 5.7');
        //$this->assertEquals(0, (int)$agentAfter->getCompTime(), 'After the command comp_time should be 0');
        //$this->assertEquals(40, (int)$agentAfter->getHolidayCredit(), 'After the command Holiday Credit should be 3.8');

        $congeAfter = $entityManager->getRepository(Holiday::class)->findOneBy(['perso_id' => 100]);
        $this->assertEquals(5.7, (int)$congeAfter->getActualRemainder(), 'After the command Holiday Credit should be 3.8');
    }


    private function execute(): void
    {
         $application = new Application(self::$kernel);
 
         $entityManager = $GLOBALS['entityManager'];
 
         $command = $application->find('app:holiday:reset:credits');
         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);
         $commandTester->assertCommandIsSuccessful();
         $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Reset the credits for holiday successfully', $output);

    }
}
