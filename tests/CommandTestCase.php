<?php

namespace Tests;

use App\Entity\Config;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommandTestCase extends KernelTestCase
{
    protected $builder;
    protected $client;
    protected $CSRFToken;
    protected $entityManager;
    protected $backupFile = '/tmp/test_db_backup.sql';
    private array $backup = [];
    protected function setParam($name, $value)
    {
        $GLOBALS['config'][$name] = $value;
        $param = $this->entityManager
            ->getRepository(Config::class)
            ->findOneBy(['nom' => $name]);

        $param->setValue($value);
        $this->entityManager->persist($param);
        $this->entityManager->flush();
    }

    protected function setUp(): void
    {
        global $entityManager;

        $this->builder = new FixtureBuilder();
        $this->entityManager = $entityManager;

    }

    /**
     * Backup database before running tests
     */
    protected function backup()
    {
        $this->backup = [];

        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metaData as $meta) {
            /** @var ClassMetadata $meta */
            $repo = $this->entityManager->getRepository($meta->getName());

            $rows = $repo->createQueryBuilder('e')->getQuery()->getArrayResult();
            $this->backup[$meta->getTableName()] = $rows;
        }
        // $dbUser = $_ENV['DB_USER'] ?? 'root';
        // $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        // $dbName = $_ENV['DB_NAME'] ?? 'planno_test';
        // $dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';

        // $cmd = sprintf(
        //     'mysqldump -u%s -p%s -h%s %s > %s',
        //     escapeshellarg($dbUser),
        //     escapeshellarg($dbPass),
        //     escapeshellarg($dbHost),
        //     escapeshellarg($dbName),
        //     $this->backupFile
        // );

        // exec($cmd, $output, $result);

        // if ($result !== 0) {
        //     throw new \Exception("Database backup failed");
        // }

        // echo "✔ Backup OK → " . $this->backupFile . "\n";
    }

    /**
     * Restore database after tests
     */
    protected function restore()
    {
        $this->entityManager->clear();
        $conn = $this->entityManager->getConnection();
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $conn->executeStatement('SET foreign_key_checks = 0');

        foreach ($metaData as $meta) {
            $conn->executeStatement("TRUNCATE TABLE {$meta->getTableName()}");
        }

        foreach ($this->backup as $table => $rows) {
            foreach ($rows as $row) {
                $conn->insert($table, $row);
            }
        }
        
        foreach ($this->backup as $table => $rows) {
            foreach ($rows as $row) {
                $entity = new $class();

                foreach ($rows as $field => $value) {
                    if ($field === 'id') {
                    } else {
                        $method = 'set'. ucfirst($field);
                        if (method_exists($entity, $method)) {
                            $entity->$method($value);
                        }
                    }
                }
                $this->entityManager->persist($entity);
            }
        }

        $this->entityManager->flush();

        $conn->executeStatement('SET foreign_key_checks = 1');
        // $dbUser = $_ENV['DB_USER'] ?? 'root';
        // $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        // $dbName = $_ENV['DB_NAME'] ?? 'planno_test';
        // $dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';

        // $cmd = sprintf(
        //     'mysql -u%s -p%s -h%s %s < %s',
        //     escapeshellarg($dbUser),
        //     escapeshellarg($dbPass),
        //     escapeshellarg($dbHost),
        //     escapeshellarg($dbName),
        //     $this->backupFile
        // );

        // exec($cmd, $output, $result);

        // if ($result !== 0) {
        //     throw new \Exception("Database restore failed");
        // }

        // echo "✔ Restore OK\n";
    }

}
