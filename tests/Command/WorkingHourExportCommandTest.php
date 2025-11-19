<?php

namespace App\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\WorkingHour;
use App\Entity\Agent;
use App\Entity\Config;
use Tests\PLBWebTestCase;

class WorkingHourExportCommandTest extends PLBWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder->delete(WorkingHour::class);
        $this->builder->delete(Agent::class);

    }
    public function testSomething(): void
    {

        $this->addConfig('PlanningHebdo-ExportFile', '/tmp/test-export.csv');
        $this->addConfig('PlanningHebdo-ExportDaysBefore', '1');
        $this->addConfig('PlanningHebdo-ExportDaysAfter', '1');
        $this->addConfig('PlanningHebdo-ExportAgentId', 'matricule');
        $this->setParam('EDTSamedi',1);
        $this->setParam('PlanningHebdo',1);
        $this->entityManager->flush();

        $alice = new Agent();
        $alice->setLogin('alice_test');
        $alice->setMail('alice@example.com');
        $alice->setFirstname('Doe');
        $alice->setLastname('Alice');
        $alice->setStatus('');
        $alice->setCategory('Titulaire');
        $alice->setService('');
        $alice->setArrival(new \DateTime('2021-12-12 00:00:00'));
        $alice->setDeparture(new \DateTime('2028-12-12 00:00:00'));
        $alice->setSkills('');
        $alice->setActive('Actif');
        $alice->setACL([1,1,1]);
        $alice->setPassword('password');
        $alice->setComments('111');
        $alice->setLastLogin(new \DateTime(''));
        $alice->setWeeklyServiceHours(0);
        $alice->setWeeklyWorkingHours(0);
        $alice->setSites('["3"]' );
        $alice->setWorkingHours(' [["09:00:00","12:00:00","13:00:00","17:00:00"],["09:00:00","12:00:00","13:00:00","17:00:00"],["09:00:00","12:00:00","13:00:00","17:00:00"],["09:00:00","12:00:00","13:00:00","17:00:00"],["09:00:00","12:00:00","13:00:00","17:00:00"],["","","",""]] ');
        $alice->setInformations('');
        $alice->setRecovery('');
        $alice->setMailsResponsables('');
        $alice->setCheckHamac(1);
        $alice->setCheckMsGraph(0);
        $alice->setDeletion(0);
        $alice->setHolidayCredit(11);
        $alice->setCompTime(22);
        $alice->setAnticipation(33);
        $alice->setMatricule('0000000ff040');
        $this->entityManager->persist($alice);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $workinghour = new WorkingHour();
        $workinghour->setId(100);
        $workinghour->setUser($alice->getId());
        $workinghour->setStart(new \DateTime(''));
        $workinghour->setEnd(new \DateTime(''));
        $workinghour->setTime('[["09:00:00","","","19:00:00","1"],["09:00:00","","","19:00:00","1"],["09:00:00","","","19:00:00","1"],["09:00:00","","","19:00:00","1"],["09:00:00","","","19:00:00","1"],["09:00:00","","","19:00:00","1"],["","","","",""]]');
        $workinghour->setBreaktime([1,1,1,1,1,1,0]);
        $workinghour->setEntry(new \DateTime('2024-12-12 00:00:00'));
        $workinghour->setChange(1);
        $workinghour->setChangeDate(new \DateTime('2023-12-12 00:00:00'));
        $workinghour->setValideLevel1(0);
        $workinghour->setValideLevel2(1);
        $workinghour->setDateValideLevel2(new \DateTime('2024-12-12 00:00:00'));
        $workinghour->setCurrent(1);
        $workinghour->setReplace(0);
        $workinghour->setException(0);
        $this->entityManager->persist($workinghour);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->execute();


        $this->assertFileExists('/tmp/test-export.csv');
        $contents = file_get_contents('/tmp/test-export.csv');
        $this->assertStringContainsString('0000000ff040', $contents);
    }

    private function execute(): void
    {
        $application = new Application(self::$kernel);
 
        $command = $application->find('app:workinghour:export');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--not-really' => true
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
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
