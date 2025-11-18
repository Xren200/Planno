<?php

namespace App\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\WorkingHour;
use App\Entity\Agent;
use Tests\PLBWebTestCase;

class WorkingHourExportCommandTest extends PLBWebTestCase
{
    public function testSomething(): void
    {
        $entityManager = $GLOBALS['entityManager'];
        
        $this->setParam('PlanningHebdo-ExportFile','/tmp/export-planno-edt.csv');
        $this->setParam('PlanningHebdo-ExportDaysBefore',15);
        $this->setParam('PlanningHebdo-ExportDaysAfter',60);
        $this->setParam('PlanningHebdo-ExportAgentId','matricule');
        $this->setParam('EDTSamedi',1);
        $this->setParam('PlanningHebdo',1);

        $alice = new Agent();
        $alice->setLogin('alice');
        $alice->setLogin('alice');
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
        $entityManager->persist($alice);
        $entityManager->flush();
        $entityManager->clear();


    }
}
