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
            'conges_credit' => "3.8",
            'conges_reliquat' => "3.8",
            'conges_anticipation' => "0",
            'comp_time' => "1.9",
            'conges_annuel' => "3.8",
        ));

        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();

        $this->execute();
        $entityManager->clear();

        $agentAfter = $$entityManager->getRepository(Agent::class)->findOneBy(['login' => 'jduponttt']);
        $this->assertEquals(5.7, (float)$agentAfter->getRemainder(), 'After the command Holiday Credit should be 5.7');
        $this->assertEquals(0.0, (float)$agentAfter->getActualCompTime(), 'After the command comp_time should be 0');
        $this->assertEquals(3.8, (float)$agentAfter->getHolidayCredit(), 'After the command Holiday Credit should be 3.8');

        $conges = $entityManager->getRepository(Holiday::class)->findOneBy(['perso_id' => 100]);
        $this->assertEquals(5.7, (float)$conges->getActualRemainder(), 'After the command Holiday Credit should be 3.8');
    }

    public function testConfigOff(): void
    {
        $this->setParam('Conges-transfer-comp-time', 1);
        $this->setUpPantherClient();
        $jdupont = $this->builder->build(Agent::class, array(
            'id' => 100,
            'login' => 'jduponttt', 'nom' => 'Duponttt', 'prenom' => 'Jean', 'temps'=>'',
            'droits' => array(3,4,5,6,9,17,20,21,22,23,25,99,100,201,202,301,302,401,402,501,502,601,602,701,801,802,901,1001,1002,1101,1201,1301),
            'sites' => '["1"]',
            'conges_credit' => "38",
            'conges_reliquat' => "38",
            'conges_anticipation' => "0",
            'comp_time' => "19",
            'conges_annuel' => "38",
        ));

        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();

        $repo = $entityManager->getRepository(Agent::class);
        $agent = $repo->findOneBy(['login' => 'jduponttt']);
        
        $this->assertEquals(0.0, (float)$agent->getHolidayCredit(), 'After the command Holiday Credit should be 0');
        $this->assertEquals(38, (float)$agent->getHolidayCredit(), 'Holiday Credit should be 3.8');
        
        $this->execute();

        $entityManager->clear();
        $repo = $entityManager->getRepository(Agent::class);
        $agentAfter = $repo->findOneBy(['login' => 'jduponttt']);
        $this->assertEquals(, (float)$agent->getHolidayCredit(), 'Holiday Credit should be 3.8');
        //$this->assertEquals(0.0, (float)$agentAfter->getHolidayCredit(), 'After the command Holiday Credit should be 0');
        $this->assertEquals(38, (float)$agentAfter->getRemainder(), 'After the command Holiday Credit should be 3.8');
    
        $conges = $entityManager->getRepository(Holiday::class)->findOneBy(['perso_id' => 100]);
        $this->assertEquals(38, (float)$conges->get(), 'After the command Holiday Credit should be 3.8');
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
