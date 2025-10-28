<?php
//no idea
namespace App\Tests\Command;

use App\Entity\Agent;
use App\Entity\Absence;
use App\Entity\ConfigParam;
use App\Entity\AbsenceDocument;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\PLBWebTestCase;

class AbsenceImportCSVCommandTest extends PLBWebTestCase
{
    private string $lockFile;
    protected function setUp(): void
    {
        parent::setUp();

        $this->lockFile = sys_get_temp_dir() . 'xxxx.lock';//test todo
        if (file_exists($this->lockFile)) {
            @unlink($this->lockFile);
        }
        $params = [
            'hamac_status_extra' => [],
            'hamac_status_waiting' => [3],
            'hamac_status_validated' => [2,5],
            'hamac_days_before' => null,
            'Hamac-debug' => false,
            'Hamac-motif' => 'Hamac',
            'Hamac-id' => 'mail',
            'Hamac-status' => '2,3,5',
            'Hamac-csv' => __DIR__ . '/../data/absences.csv',
        ];

        foreach ($params as $k => $v) {
            try {
                $this->setParam($k, $v);
            } catch (\Throwable $e) {
            }
        }

        $this->builder->delete(Agent::class);
    }

    public function testLockFileIsCreated(): void
    {
        $lockPath = sys_get_temp_dir() . '/tests/data/absences.csv';
        if (file_exists($lockPath)) {
            unlink($lockPath);
        }
        $this->execute();
        $this->assertFileExists($lockPath, 'lock file should be created by the command');
    }

    public function testExitsWhenLockFileIsRecent(): void
    {
        file_put_contents($this->lockFile, '');
        touch($this->lockFile, time());

        $exited = false;
        try {
            $this->execute();
        } catch (\Exception $e) {
            $exited = true;
        }

        $this->assertTrue($exited, 'it should exit if find lock file recent');
        $this->assertFileExists($this->lockFile, 'lock file should be found afetr exit');
    }

    public function testDeletesOldLockFile(): void
    {
        file_put_contents($this->lockFile, 'old');
        touch($this->lockFile, time() - 1200);

        $this->execute();

        $this->assertFileExists($this->lockFile);
        $this->assertSame('', file_get_contents($this->lockFile));
        $this->assertTrue(time() - filemtime($this->lockFile) < 5, 'the lock file should have been updated');
    }

    public function testAgent(): void
    {
        $this->setUpPantherClient();

        $alice = $this->builder->build(Agent::class, [
            'login' => 'alice', 'mail' => 'alice@example.com', 'nom' => 'Doe', 'prenom' => 'Alice',
            'droits' => [99,100], 'supprime' => 0, 'check_hamac' => 1
        ]);
        $jdevoe = $this->builder->build(Agent::class, array(
            'login' => 'jdevoe', 'mail' => 'jdevoe@example.com', 'nom' => 'Devoe', 'prenom' => 'John',
            'droits' => array(99,100),'supprime' => 0,'check_hamac' => 0
        ));
        $abreton = $this->builder->build(Agent::class, array(
            'login' => 'abreton', 'mail' => 'abreton@example.com', 'nom' => 'Breton', 'prenom' => 'Aubert',
            'droits' => array(99,100),'supprime' => 1,'check_hamac' => 0
        ));
        $kboivin = $this->builder->build(Agent::class, array(
            'login' => 'kboivin', 'mail' => 'kboivin@example.com', 'nom' => 'Boivin', 'prenom' => 'Karel',
            'droits' => array(201,501,99,100),'supprime' => 0,'check_hamac' => 1
        ));
        $csvPath = sys_get_temp_dir() . '/plb_hamac_test_' . uniqid() . '.csv';
        $uid = 'UID123';
        $debut = '01/10/2025 00:00:00';
        $fin = '02/10/2025 00:00:00';
        //uid ; commentaires ; debut ; fin ; login ; (col5) ; status
        $line = implode(';', [$uid, 'Test absence', $debut, $fin, $alice->getMail(), '', '2']) . PHP_EOL;
        file_put_contents($csvPath, $line);

        $this->setParam('Hamac-csv', $csvPath);

        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();

        $this->execute();

        $entityManager->clear();
        $repo = $entityManager->getRepository(Absence::class);
        $imported = $repo->findOneBy(['uid' => $uid]);

        $this->assertNotNull($imported, 'CSV imported absence should be found in database');
        $this->assertSame($alice->getId(), (int)$imported->getPersoId(), 'imported absence should belong to Alice');

        @unlink($csvPath);

    }

    private function execute(): void
    {
         $application = new Application(self::$kernel);
 
         $entityManager = $GLOBALS['entityManager'];
 
         $command = $application->find('app:absence:delete-documents');
         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);
         $commandTester->assertCommandIsSuccessful();

    }
    
}
