<?php
//TO BE CONTINUED
namespace App\Tests\Command;

use App\Entity\Absence;
use App\Entity\Agent;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Tests\PLBWebTestCase;

class AbsenceImportICSCommandTest extends PLBWebTestCase
{
    private string $lockFile;
    protected function setUp(): void
    {
        parent::setUp();

        $this->lockFile = sys_get_temp_dir() . '/plannoAbsenceImportICS.lock';
        if (file_exists($this->lockFile)) {
            @unlink($this->lockFile);
        }
        $params = [
            'hamac_status_extra' => [0,1],
            'hamac_status_waiting' => [3],
            'hamac_status_validated' => [2,5],
            'hamac_days_before' => "2020-11-14 00:00:00",
            'Hamac-debug' => true,
            'Hamac-motif' => 'Hamac',
            'Hamac-id' => 'matricule',
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

        $alice = $this->builder->build(Agent::class, array(
            'login' => 'alice', 'mail' => 'alice@example.com', 'nom' => 'Doe', 'prenom' => 'Alice',
            'supprime' => 0, 'check_hamac' => 1, 'matricule' => '0000000ff040'
        ));
        $jdevoe = $this->builder->build(Agent::class, array(
            'login' => 'jdevoe', 'mail' => 'jdevoe@example.com', 'nom' => 'Devoe', 'prenom' => 'John',
            'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee490'
        ));
        $abreton = $this->builder->build(Agent::class, array(
            'login' => 'abreton', 'mail' => 'abreton@example.com', 'nom' => 'Breton', 'prenom' => 'Aubert',
            'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee493'
        ));
        $kboivin = $this->builder->build(Agent::class, array(
            'login' => 'kboivin', 'mail' => 'kboivin@example.com', 'nom' => 'Boivin', 'prenom' => 'Karel',
            'supprime' => 0,'check_hamac' => 1, 'matricule' => '0000000ee856'
        ));

        $ics1=new \CJICS();
        $ics1->src = $url;
        $ics1->number = $i;
        $ics1->perso_id = $agent["id"];
        $ics1->table = "absences";
        $ics1->logs = true;

        $ics2=new \CJICS();
        $ics2->src=$url;
        $ics2->perso_id=$agent["id"];
        $ics2->pattern = $config["ICS-Pattern$i"];
        $ics2->status = $config["ICS-Status$i"];
        $ics2->desc = $config["ICS-Description$i"];
        $ics2->number = $i;
        $ics2->table="absences";
        $ics2->logs=true;

        $abs = $this->entityManager->getRepository(Absence::class)->find(["cal_name"=> "$calName","perso_id"=>$perso_id]);
        $this->assertNotNull( $abs, '');

        $this->execute();

        $abs = $this->entityManager->getRepository(Absence::class)->find(["cal_name"=> "$calName","perso_id"=>$perso_id]);
        $this->assertNull( $abs, '');

        // $this->assertSame(96, (int)$countAfter, '96 absence should be imported');

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
