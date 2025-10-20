<?php

namespace App\Tests\Command;

use App\Entity\Agent;
use Symfony\Component\Process\Process;
use Tests\PLBWebTestCase;

class HolidayResetCompTimeCommandTest extends PLBWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder->delete(Agent::class);
    }

    public function testSomething(): void
    {
        $this->setUpPantherClient();
        $jdupont = $this->builder->build(Agent::class, array(
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

        $repo = $entityManager->getRepository(Agent::class);
        $agent = $repo->findOneBy(['login' => 'jduponttt']);
        $this->assertNotNull($agent, 'Agent should exist in database');
        $this->assertSame('1.9', (string)$agent->getCompTime(), 'comp_time should be 1.9');
        $this->execute();
        $entityManager->clear();
        $repo = $entityManager->getRepository(Agent::class);
        $agentAfter = $repo->findOneBy(['login' => 'jduponttt']);
        $this->assertNotNull($agentAfter, 'Agent should still exist after cron');
        $this->assertEquals(0.0, (float)$agentAfter->getCompTime(), 'After the command comp_time should be 0');
    
    }

    private function execute(): void
    {
        //  $kernel = self::bootKernel();
        //  $application = new Application(self::$kernel);
 
        //  $entityManager = $GLOBALS['entityManager'];
 
        //  $command = $application->find('app:absence:delete-documents');
        //  $commandTester = new CommandTester($command);
        //  $commandTester->execute([
        //      'command'  => $command->getName()
        //  ], [
        //      'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        //  ]);
        //  $commandTester->assertCommandIsSuccessful();
        //  $output = $commandTester->getDisplay();

        // $output = shell_exec('php public/absences/cron.deleteOldDocuments.php');
        //$this->assertStringContainsString('Hello World', $output);

        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $script = $projectDir.'/src/Cron/Legacy/cron.holiday_reset_comp_time.php';

        if (!is_file($script) || !is_readable($script)) {
            $this->fail("Cron script not found or not readable: {$script}");
        }

        $php = \PHP_BINARY;
        $proc = new Process([$php, $script], $projectDir, [
            'APP_ENV' => 'test',
            'APP_DEBUG' => '0',
        ]);
        $proc->run();
        echo "STDOUT:\n" . $proc->getOutput() . "\n";
        echo "STDERR:\n" . $proc->getErrorOutput() . "\n";
        /* STDERR:
        PHP Warning:  Undefined global variable $CSRFSession in /home/planno/www/planno/src/Cron/Legacy/cron.holiday_reset_comp_time.php on line 18
        */

        if (!$proc->isSuccessful()) {
            $this->fail("Cron failed\nEXIT={$proc->getExitCode()}\nSTDOUT:\n{$proc->getOutput()}\nSTDERR:\n{$proc->getErrorOutput()}");
        }
    
    }
    
}