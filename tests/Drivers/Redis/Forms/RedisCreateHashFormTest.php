<?php

namespace Adminerng\Tests\Drivers\Redis\Forms;

use Adminerng\Drivers\Redis\Forms\RedisCreateHashForm;
use Adminerng\Tests\Drivers\AbstractDriverTest;
use Nette\Application\UI\Form;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;
use RedisProxy\RedisProxy;

class RedisCreateHashFormTest extends AbstractDriverTest
{
    private $connection;

    protected function setUp()
    {
        if (!extension_loaded('redis')) {
            self::markTestSkipped('redis extension is not available');
        }
        $this->connection = new RedisProxy(getenv('ADMINERNG_REDIS_HOST'), getenv('ADMINERNG_REDIS_PORT'), getenv('ADMINERNG_REDIS_DATABASE'));
        $this->connection->flushdb();
    }

    public function testForm()
    {
        $form = new Form();
        $controls = $form->getControls();
        self::assertCount(0, $controls);

        $credentialsForm = new RedisCreateHashForm($this->connection);
        $credentialsForm->addFieldsToForm($form);
        self::assertGreaterThan(0, count($form->getControls()));
        foreach ($form->getControls() as $control) {
            self::assertInstanceOf(IControl::class, $control);
        }

        $key = 'my_test_hash_key';
        $field = 'my_test_hash_field';
        $value = 'my_test_hash_value';
        self::assertEquals(0, $this->connection->hlen($key));
        self::assertNull($this->connection->hget($key, $field));
        $values = ArrayHash::from([
            'key' => $key,
            'field' => $field,
            'value' => $value,
        ]);
        self::assertNull($credentialsForm->submit($form, $values));
        self::assertCount(0, $form->getErrors());
        self::assertCount(0, $form->getOwnErrors());
        self::assertEquals(1, $this->connection->hlen($key));
        self::assertEquals($value, $this->connection->hget($key, $field));

        self::assertNull($credentialsForm->submit($form, $values));
        self::assertCount(1, $form->getErrors());
        self::assertCount(1, $form->getOwnErrors());
        self::assertEquals(1, $this->connection->hlen($key));
        self::assertEquals($value, $this->connection->hget($key, $field));
    }
}
