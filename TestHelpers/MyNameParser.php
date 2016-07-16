<?php
namespace VKR\ExternalEmailBundle\TestHelpers;

use VKR\ExternalEmailBundle\Interfaces\EmailParserInterface;

class MyNameParser implements EmailParserInterface
{
    public function parse(array $arguments=[])
    {
        if (isset($arguments['name'])) {
            return $arguments['name'];
        }
        return 'Foo';
    }
}
