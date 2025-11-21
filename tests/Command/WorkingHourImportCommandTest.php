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
        $this->addConfig('PlanningHebdo-ImportAgentId', 'login');
        $this->setParam('PlanningHebdo-CSV', __DIR__ . '/../data/workingHourImport.csv');
        $this->setParam('Multisites-nombre', 1);
        
        $alex = $this->builder->build(Agent::class, [
            'login' => 'alex', 'mail' => 'alice@example.com', 'nom' => 'Doe', 'prenom' => 'Alice',
            'supprime' => 0
        ]);
        $aurelie = $this->builder->build(Agent::class, [
            'login' => 'aurelie', 'mail' => 'alice@example.com', 'nom' => 'Doe', 'prenom' => 'Alice',
            'supprime' => 0
        ]);
        $WorkingHour1 = $this->builder->build(WorkingHour::class,array(
            'perso_id' => 1,
            'actuel' => 0,
            'valide' => 1,
            'debut' => new DateTime("2000-01-01"),
        )); 
        
        $this->execute();

        $whAlex = $this->entityManager->getRepository(WorkingHour::class)->findOneBy(["perso_id"=> $alex->getId()]);
        $whAurelie = $this->entityManager->getRepository(WorkingHour::class)->findOneBy(["perso_id"=> $aurelie->getId()]);

        $this->assertNotNull( $whAlex, '');
        $this->assertNotNull( $whAurelie, '');
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
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('CSV weekly planning import completed: new/updated schedules inserted and obsolete ones purged.', $output);

    }

    private function addConfig($key, $value) {
        $c = new Config();
        $c->setName($key);
        $c->setValue($value);
        $c->setType('text');
        $c->setComment('');
        $c->setCategory('test');
        $c->setValues('');
        $c->setTechnical(0);
        $c->setOrder(0);
        $this->entityManager->persist($c);
    }
}
