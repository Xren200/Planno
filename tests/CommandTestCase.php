<?php

namespace Tests;

use App\Entity\Config;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommandTestCase extends KernelTestCase
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

        $this->builder = new FixtureBuilder();
        $this->entityManager = $entityManager;

    }

}
