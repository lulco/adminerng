<?php

namespace Adminerng\Drivers\Redis;

use Adminerng\Core\CredentialsFormInterface;
use Nette\Application\UI\Form;

class RedisForm implements CredentialsFormInterface
{
    public function addFieldsToForm(Form $form)
    {
        $form->addText('host', 'redis.form.host.label')
            ->setAttribute('placeholder', 'localhost');
        $form->addText('port', 'redis.form.port.label')
            ->setAttribute('placeholder', '6379');
        $form->addText('database', 'redis.form.database.label')
            ->setAttribute('placeholder', 0);
    }
}
