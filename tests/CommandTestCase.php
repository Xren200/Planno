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
    // private array $bkp = [];
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
    // protected function backup()
    // {
    //     $this->bkp = [];

    //     $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

    //     foreach ($metaData as $meta) {
    //         /** @var ClassMetadata $meta */
    //         $repo = $this->entityManager->getRepository($meta->getName());//?

    //         $rows = $repo->createQueryBuilder('e')->getQuery()->getArrayResult();//que text?autre fonction?que string?
    //         $this->bkp[$meta->getTableName()] = $rows;
    //     }
        //legacy/migration/shcema or data
        //bootstrap
        //only restore pas backup
        

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
    // }

    /**
     * Restore database after tests
     */
    // protected function restore()
    // {
    //     $this->entityManager->clear();
    //     $conn = $this->entityManager->getConnection();
    //     $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

    //     $conn->executeStatement('SET foreign_key_checks = 0');

    //     foreach ($metaData as $meta) {
    //         $conn->executeStatement("TRUNCATE TABLE {$meta->getTableName()}");
    //     }

    //     foreach ($this->bkp as $table => $rows) {
    //         foreach ($rows as $row) {
    //             var_dump($row);
    //             echo "\n";
    //             $conn->insert($table, $row);
    //         }
    //     }

    //     $conn->executeStatement('SET foreign_key_checks = 1');
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
    // }
    function reset_test_database()
    {
        // 1. 加载 .env.test.local
        if (!file_exists(__DIR__ . "/../.env.test.local")) {
            throw new \RuntimeException(".env.test.local not found");
        }

        (new \Symfony\Component\Dotenv\Dotenv())
            ->load(__DIR__ . "/../.env.test.local");

        $database_url = $_ENV['DATABASE_URL'];

        // 2. 解析 DATABASE_URL
        $pattern = '/.[^\/]*\/\/(.[^:]*):(.[^@]*)@(.[^:]*):(\d*)\/(.*)/';

        $dbuser = preg_replace($pattern, '\1', $database_url);
        $dbpass = preg_replace($pattern, '\2', $database_url);
        $dbhost = preg_replace($pattern, '\3', $database_url);
        $dbport = preg_replace($pattern, '\4', $database_url);
        $dbname = preg_replace($pattern, '\5', $database_url);

        // 3. 连接 MySQL（系统库）
        $link = mysqli_init();
        mysqli_real_connect($link, $dbhost, $dbuser, $dbpass, 'mysql');

        // 4. 重建数据库
        $sqls = [];
        $sqls[] = "DROP DATABASE IF EXISTS `$dbname`;";
        $sqls[] = "CREATE DATABASE `$dbname` CHARACTER SET utf8 COLLATE utf8_bin;";
        $sqls[] = "USE `$dbname`;";

        // legacy schema 和 data
        include __DIR__ . '/../legacy/migrations/schema.php';
        include __DIR__ . '/../legacy/migrations/data.php';

        foreach ($sqls as $sql) {
            mysqli_multi_query($link, $sql);
        }

        mysqli_close($link);

        // 5. doctrine migrations（安静模式）
        exec(__DIR__ . '/../bin/console doctrine:migrations:migrate --env=test -q');

        // 6. 初始化 legacy 系统
        include_once(__DIR__ . '/../init/init.php');
        include_once(__DIR__ . '/../init/init_templates.php');
    }


}
