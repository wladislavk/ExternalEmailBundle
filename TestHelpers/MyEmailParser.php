<?php
namespace VKR\ExternalEmailBundle\TestHelpers;

use VKR\ExternalEmailBundle\Interfaces\EmailParserInterface;

class MyEmailParser implements EmailParserInterface
{
    public function parse(array $arguments=[])
    {
        if (isset($arguments['email'])) {
            return $arguments['email'];
        }
        return 'foo@bar.com';
    }
}
