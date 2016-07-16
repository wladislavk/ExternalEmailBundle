<?php
namespace VKR\ExternalEmailBundle\Interfaces;

interface EmailParserInterface
{
    /**
     * @param array $arguments
     * @return string
     */
    public function parse(array $arguments=[]);
}
