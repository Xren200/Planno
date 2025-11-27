<?php

namespace App\Tests\Command;

use DateTime;
use App\Entity\Log;
use Tests\PLBWebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class PurgeLogTableCommandTest extends PLBWebTestCase
{    

    public function testSomething(): void
    {
	    $date = new DateTime();
        $date->modify('-5 years');
	    for ($i = 0; $i < 11 ; $i ++) {
		$this->builder->build(Log::class, ['timestamp' => $date]);
		$date->modify('+6 months');
	    }

        $countBefore = $this->entityManager->getConnection()->fetchOne("SELECT COUNT(*) FROM log");
        $this->assertSame(11, (int)$countBefore, '11 log should be founded');

        $this->execute();

        $countAfter = $this->entityManager->getConnection()->fetchOne("SELECT COUNT(*) FROM log");

        $this->assertSame(4, (int)$countAfter, '1 log should be founded');
    }

    private function execute(): void
    {
         
        $kernel = self::bootKernel();
        $application = new Application($kernel);
 
        $command = $application->find('app:purge:log-table');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'delay' => '12 MONTH',
        ]);
        $commandTester->assertCommandIsSuccessful();

    }
}
