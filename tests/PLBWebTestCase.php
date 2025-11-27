<?php

namespace Tests;

use App\Entity\Config;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Panther\PantherTestCase;

class PLBWebTestCase extends PantherTestCase
{
    protected $builder;
    protected $client;
    protected $CSRFToken;
    protected $entityManager;

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

        $CSRFToken = '00000';

        $this->client = static::createClient();
        $this->CSRFToken = $CSRFToken;
        $this->builder = new FixtureBuilder();
        $this->entityManager = $entityManager;

        $_SESSION['oups']['Auth-Mode'] = 'SQL';
        $_SESSION['login_id'] = 1;
        $_SESSION['oups']['CSRFToken'] = $CSRFToken;
        $GLOBALS['CSRFSession'] = $CSRFToken;
    }

    protected function logInAgent($agent, $rights = array(99, 100)) {
        $_SESSION['login_id'] = $agent->getId();

        $agent->setACL($rights);

        global $entityManager;
        $entityManager->persist($agent);
        $entityManager->flush();

        $GLOBALS['droits'] = $rights;
        $crawler = $this->client->request('GET', '/login');
        $session = $this->client->getRequest()->getSession();
        $session->set('loginId', $agent->getId());
        $session->save();
    
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    protected function setUpPantherClient()
    {
        $this->client = static::createPantherClient(
            array(
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--headless'
            )
        );
    }

    protected function login($agent)
    {
        $this->logout();
        global $entityManager;

        $password = password_hash("MyPass", PASSWORD_BCRYPT);
        $agent->setPassword($password);
        $entityManager->persist($agent);
        $entityManager->flush();

        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Valider')->form();
        $form['login'] = $agent->getLogin();
        $form['password'] = 'MyPass';

        $crawler = $this->client->submit($form);

        $this->client->waitForVisibility('html');
    }

    protected function logout()
    {
        $this->client->request('GET', '/logout');
    }


    protected function jqueryAjaxFinished(): callable
    {
        return static function ($driver): bool {
            return $driver->executeScript('return $.active === 0;');
        };
    }

    protected function getSelect($id = null)
    {
        $driver = $this->client->getWebDriver();

        $select = new WebDriverSelect($driver->findElement(WebDriverBy::id($id)));

        return $select;
    }

    protected function getSelectValues($id = null)
    {
        $select = $this->getSelect($id);
        $options = array();

        foreach ($select->getOptions() as $option) {
            $options[] = $option->getAttribute('value');
        }

        return $options;
    }

    protected function getElementsText($selector = null)
    {
        $driver = $this->client->getWebDriver();

        $elements = $driver->findElements(WebDriverBy::cssSelector('ul#perso_ul1 li'));
        $values = array();

        foreach ($elements as $element) {
            $values[] = $element->getText();
        }

        return $values;
    }

    protected function restore()
    {
        if (!file_exists(__DIR__ . "/../.env.test.local")) {
            throw new \RuntimeException(".env.test.local not found");
        }

        (new \Symfony\Component\Dotenv\Dotenv())
            ->load(__DIR__ . "/../.env.test.local");

        $database_url = $_ENV['DATABASE_URL'];

        $pattern = '/.[^\/]*\/\/(.[^:]*):(.[^@]*)@(.[^:]*):(\d*)\/(.*)/';

        $dbuser = preg_replace($pattern, '\1', $database_url);
        $dbpass = preg_replace($pattern, '\2', $database_url);
        $dbhost = preg_replace($pattern, '\3', $database_url);
        $dbport = preg_replace($pattern, '\4', $database_url);
        $dbname = preg_replace($pattern, '\5', $database_url);

        $config = [
            'dbuser'   => $dbuser,
            'dbpass'   => $dbpass,
            'dbhost'   => $dbhost,
            'dbport'   => $dbport,
            'dbname'   => $dbname,
            'dbprefix' => $_ENV['DATABASE_PREFIX'] ?? '',
        ];

        $link = mysqli_init();
        mysqli_real_connect($link, $dbhost, $dbuser, $dbpass, 'mysql');

        $sqls = [];
        $sqls[] = "DROP DATABASE IF EXISTS `$dbname`;";
        $sqls[] = "CREATE DATABASE `$dbname` CHARACTER SET utf8 COLLATE utf8_bin;";
        $sqls[] = "USE `$dbname`;";
        $entityManager = $this->entityManager;
        include __DIR__ . '/../legacy/migrations/schema.php';
        include __DIR__ . '/../legacy/migrations/data.php';
        include(__DIR__ . '/../init/init.php');
        include(__DIR__ . '/../init/init_templates.php');

        foreach ($sqls as $sql) {
            mysqli_multi_query($link, $sql);
        }

        mysqli_close($link);

        exec(__DIR__ . '/../bin/console doctrine:migrations:migrate --env=test -q');


    }

}
