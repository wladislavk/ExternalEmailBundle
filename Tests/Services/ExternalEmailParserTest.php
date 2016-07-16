<?php
namespace VKR\ExternalEmailBundle\Tests\Services;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use VKR\ExternalEmailBundle\Services\ExternalEmailParser;
use PHPUnit_Framework_MockObject_MockObject;
use VKR\ExternalEmailBundle\TestHelpers\MyEmailParser;
use VKR\ExternalEmailBundle\TestHelpers\MyNameParser;
use VKR\SettingsBundle\Exception\SettingNotFoundException;
use VKR\SettingsBundle\Services\SettingsRetriever;

class ExternalEmailParserTest extends \PHPUnit_Framework_TestCase
{
    protected $settings = [
        'mailer_from_address' => 'admin@mysite.com',
        'mailer_from_name' => 'My cool site',
        'email_file' => __DIR__ . '/../../TestFixtures/message.txt',
        'email_subject' => 'Test email',
        'nonexistent_file' => 'foo.txt',
    ];

    /**
     * @var ExternalEmailParser
     */
    protected $emailParser;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsRetriever;

    /**
     * @var array
     */
    protected $wildcardParsers;

    public function setUp()
    {
        $this->mockSettingsRetriever();
        $this->emailParser = new ExternalEmailParser($this->settingsRetriever);
        $this->wildcardParsers = [
            new MyNameParser(),
            new MyEmailParser(),
        ];
    }

    public function testWithoutParsers()
    {
        $message = $this->emailParser->parseEmail('email_file', 'email_subject', 'test@test.com',
            []);
        $parsedMessage = 'Hello! My name is %MY_NAME% and my email is %MY_EMAIL%.';
        $this->assertEquals($parsedMessage, $message->getBody());
        $this->assertEquals(['test@test.com'], array_keys($message->getTo()));
        $this->assertEquals($this->settings['email_subject'], $message->getSubject());
        $from = [$this->settings['mailer_from_address'] => $this->settings['mailer_from_name']];
        $this->assertEquals($from, $message->getFrom());
    }

    public function testWildcardParsing()
    {
        $message = $this->emailParser->parseEmail('email_file', 'email_subject', 'test@test.com',
                                                  $this->wildcardParsers);
        $parsedMessage = 'Hello! My name is Foo and my email is foo@bar.com.';
        $this->assertEquals($parsedMessage, $message->getBody());
    }

    public function testWildcardParsingWithArguments()
    {
        $args = [
            'name' => 'Bar',
            'email' => 'bar@foo.com',
        ];
        $message = $this->emailParser->parseEmail('email_file', 'email_subject', 'test@test.com',
                                                  $this->wildcardParsers, $args);
        $parsedMessage = 'Hello! My name is Bar and my email is bar@foo.com.';
        $this->assertEquals($parsedMessage, $message->getBody());
    }

    public function testNonExistentFile()
    {
        $exceptionReflection = new \ReflectionClass(FileNotFoundException::class);
        $this->setExpectedException($exceptionReflection->getName());
        $message = $this->emailParser->parseEmail('nonexistent_file', 'email_subject', 'test@test.com',
                                                  []);
    }

    protected function mockSettingsRetriever()
    {
        $this->settingsRetriever = $this
            ->getMockBuilder(SettingsRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->settingsRetriever->expects($this->any())
            ->method('get')
            ->will($this->returnCallback([$this, 'getMockedSettingValueCallback']));
    }

    public function getMockedSettingValueCallback($settingName)
    {
        if (isset($this->settings[$settingName])) {
            return $this->settings[$settingName];
        }
        throw new SettingNotFoundException($settingName);
    }
}
