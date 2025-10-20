<?php

namespace App\Tests\Command;

use App\Entity\Agent;
use App\Entity\Absence;
use Tests\PLBWebTestCase;

class AbsenceDeleteDocumentsCommandTest extends PLBWebTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder->delete(Agent::class);
    }

    public function testSomething(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/');

        $this->assertSelectorTextContains('h1', 'Hello World');
    }
}
