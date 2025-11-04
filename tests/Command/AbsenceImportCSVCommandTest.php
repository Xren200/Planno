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

        $this->lockFile = sys_get_temp_dir() . '/plannoAbsenceImportCSV.lock';
        if (file_exists($this->lockFile)) {
            @unlink($this->lockFile);
        }
        $params = [
            'hamac_status_extra' => [0,1,9],
            'hamac_status_waiting' => [3],
            'hamac_status_validated' => [2,5],
            'hamac_days_before' => null,
            'Hamac-debug' => true,
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
    }

    public function testAgent(): void
    {
        $this->setUpPantherClient();

        $alice = $this->builder->build(Agent::class, [
            'login' => 'alice', 'mail' => 'alice@example.com', 'nom' => 'Doe', 'prenom' => 'Alice',
            'droits' => [99,100], 'supprime' => 0, 'check_hamac' => 1, 'matricule' => '0000000ff040'
        ]);
        $jdevoe = $this->builder->build(Agent::class, array(
            'login' => 'jdevoe', 'mail' => 'jdevoe@example.com', 'nom' => 'Devoe', 'prenom' => 'John',
            'droits' => array(99,100),'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee490'
        ));
        $abreton = $this->builder->build(Agent::class, array(
            'login' => 'abreton', 'mail' => 'abreton@example.com', 'nom' => 'Breton', 'prenom' => 'Aubert',
            'droits' => array(99,100),'supprime' => 1,'check_hamac' => 1, 'matricule' => '0000000ee493'
        ));
        $kboivin = $this->builder->build(Agent::class, array(
            'login' => 'kboivin', 'mail' => 'kboivin@example.com', 'nom' => 'Boivin', 'prenom' => 'Karel',
            'droits' => array(201,501,99,100),'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee856'
        ));
        $csvPath = __DIR__ . '/../data/absences.csv';
        $entityManager = $GLOBALS['entityManager'];
        $entityManager->clear();

        $this->execute();

        $entityManager->clear();
        $count = $this->entityManager->getConnection()->fetchOne("SELECT COUNT(*) FROM absences");
        $this->assertSame(132, (int)$count, '132 absence should be imported');
        $repo = $entityManager->getRepository(Absence::class);
        $imported = $repo->findOneBy(['perso_id' => $alice->getId()]);

        $this->assertNotNull($imported, 'CSV imported absence should be found in database');
        //$this->assertSame($alice->getId(), (int)$imported->getId(), 'imported absence should belong to Alice');

    }

    private function execute(): void
    {
         
         $application = new Application(self::$kernel);
 
         $command = $application->find('app:absence:import-csv');

         $commandTester = new CommandTester($command);
         $commandTester->execute([
             'command'  => $command->getName()
         ], [
             'verbosity' => OutputInterface::VERBOSITY_VERBOSE
         ]);
         $commandTester->assertCommandIsSuccessful();

    }
    
}
