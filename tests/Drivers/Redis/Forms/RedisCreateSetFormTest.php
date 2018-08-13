<?php

namespace UniMan\Tests\Drivers\Redis\Forms;

use UniMan\Drivers\Redis\Forms\RedisCreateSetForm;
use UniMan\Tests\Drivers\AbstractDriverTest;
use Nette\Application\UI\Form;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;
use RedisProxy\RedisProxy;

class RedisCreateSetFormTest extends AbstractDriverTest
{
    private $connection;

    protected function setUp()
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('redis extension is not available');
        }
        $this->connection = new RedisProxy(getenv('UNIMAN_REDIS_HOST'), getenv('UNIMAN_REDIS_PORT'), getenv('UNIMAN_REDIS_DATABASE'));
        $this->connection->flushdb();
    }

    public function testForm()
    {
        $form = new Form();
        $controls = $form->getControls();
        self::assertCount(0, $controls);

        $credentialsForm = new RedisCreateSetForm($this->connection);
        $credentialsForm->addFieldsToForm($form);
        self::assertGreaterThan(0, $form->getControls()->count());
        foreach ($form->getControls() as $control) {
            self::assertInstanceOf(IControl::class, $control);
        }

        $key = 'my_test_set_key';
        $members = ['my_test_set_member_1', 'my_test_set_member_2'];
        self::assertEquals(0, $this->connection->scard($key));
        self::assertEquals([], $this->connection->smembers($key));
        $values = ArrayHash::from([
            'key' => $key,
            'members' => implode(',', $members),
        ]);
        self::assertNull($credentialsForm->submit($form, $values));
        self::assertCount(0, $form->getErrors());
        self::assertCount(0, $form->getOwnErrors());
        self::assertEquals(2, $this->connection->scard($key));
        $smembers = $this->connection->smembers($key);
        sort($smembers);
        sort($members);
        self::assertEquals($members, $smembers);
    }
}
