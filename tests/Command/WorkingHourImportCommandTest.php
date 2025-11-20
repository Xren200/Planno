<?php

namespace App\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\WorkingHour;
use App\Entity\Agent;
use DateTime;
use App\Entity\Config;
use Tests\PLBWebTestCase;

class WorkingHourImportCommandTest extends PLBWebTestCase
{
    public function testSomething(): void
    {
        $this->setParam('PlanningHebdo-ImportAgentId', 'login');
        $this->setParam('PlanningHebdo-CSV', '/../data/workingHourImport.csv');
        $this->setParam('Multisites-nombre', 1);
        
        $alice = $this->builder->build(Agent::class, [
            'login' => 'alice', 'mail' => 'alice@example.com', 'nom' => 'Doe', 'prenom' => 'Alice',
            'supprime' => 0, 'check_hamac' => 1, 'matricule' => '0000000ff040'
        ]);
        $jdevoe = $this->builder->build(Agent::class, array(
            'login' => 'jdevoe', 'mail' => 'jdevoe@example.com', 'nom' => 'Devoe', 'prenom' => 'John',
            'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee490'
        ));
        $abreton = $this->builder->build(Agent::class, array(
            'login' => 'abreton', 'mail' => 'abreton@example.com', 'nom' => 'Breton', 'prenom' => 'Aubert',
            'supprime' => 1,'check_hamac' => 1, 'matricule' => '0000000ee493'
        ));
        $kboivin = $this->builder->build(Agent::class, array(
            'login' => 'kboivin', 'mail' => 'kboivin@example.com', 'nom' => 'Boivin', 'prenom' => 'Karel',
            'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee856'
        ));
        $WorkingHour1 = $this->builder->build(WorkingHour::class,array(
            'perso_id' => 1,
            'actuel' => 0,
            'valide' => 1,
            'debut' => new DateTime("2000-01-01"),
        )); 
        
        $this->execute();

    }

    private function execute(): void
    {
         
        $application = new Application(self::$kernel);
 
        $command = $application->find('app:workinghour:import');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName()
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ]);
        $commandTester->assertCommandIsSuccessful();

    }
}
