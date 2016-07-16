<?php
namespace VKR\ExternalEmailBundle\Services;

use Swift_Message;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use VKR\ExternalEmailBundle\Interfaces\EmailParserInterface;
use VKR\SettingsBundle\Services\SettingsRetriever;

/**
 * Gets an email template from an external source, as defined in Settings table, parses its wildcards and
 * sends it via Swift Mailer
 */
class ExternalEmailParser
{
    /**
     * @var SettingsRetriever
     */
    protected $settingsRetriever;

    /**
     * @param SettingsRetriever $settingsRetriever
     */
    public function __construct(SettingsRetriever $settingsRetriever)
    {
        $this->settingsRetriever = $settingsRetriever;
    }

    /**
     * Changes wildcards formatted as '%wildcardName%' in the external email for their respective values using
     * getter methods of this class. Only those wildcards will be parsed that were explicitly set in $wildcards
     * array in the calling class
     *
     * @param string $emailFileSettingName
     * @param string $emailSubjectSettingName
     * @param string $emailAddress
     * @param EmailParserInterface[] $wildcardParsers
     * @param array $additionalArguments
     * @return Swift_Message
     */
    public function parseEmail($emailFileSettingName, $emailSubjectSettingName, $emailAddress,
                               array $wildcardParsers, array $additionalArguments = [])
    {
        $message = $this->getExternalEmail($emailFileSettingName);
        foreach ($wildcardParsers as $wildcardParser) {
            $wildcardName = $this->getWildcardName($wildcardParser);
            if (strstr($message, $wildcardName)) {
                $replacement = $this->parseWildcard($wildcardParser, $additionalArguments);
                $message = str_replace($wildcardName, $replacement, $message);
            }
        }
        return $this->formExternalEmail($message, $emailSubjectSettingName, $emailAddress);
    }

    /**
     * Gets contents of email file with path defined at $settingName. File can be either local or remote
     *
     * @param string $settingName
     * @return string
     * @throws FileNotFoundException
     */
    protected function getExternalEmail($settingName)
    {
        $emailFile = $this->settingsRetriever->get($settingName);
        try {
            $emailContents = file_get_contents($emailFile);
        } catch (\Exception $e) {
            throw new FileNotFoundException("File $emailFile does not exist or is unreachable");
        }
        return $emailContents;
    }

    /**
     * Creates Swift_Message object from the contents of external file, subject setting and email address
     *
     * @param string $message
     * @param string $subjectSettingName
     * @param string $emailAddress
     * @param string $type
     * @return Swift_Message
     */
    protected function formExternalEmail($message, $subjectSettingName, $emailAddress,
                                         $type='text/html')
    {
        $emailSubject = $this->settingsRetriever->get($subjectSettingName);
        $senderAddress = $this->settingsRetriever->get('mailer_from_address');
        $senderName = $this->settingsRetriever->get('mailer_from_name');
        $swiftMessage = new Swift_Message();
        $swiftMessage->setSubject($emailSubject);
        $swiftMessage->setFrom($senderAddress, $senderName);
        $swiftMessage->setTo($emailAddress);
        $swiftMessage->setBody($message, $type);
        return $swiftMessage;
    }

    /**
     * @param EmailParserInterface $parser
     * @param array $additionalArguments
     * @return string
     */
    protected function parseWildcard(EmailParserInterface $parser, array $additionalArguments=[])
    {
        return $parser->parse($additionalArguments);
    }

    /**
     * @param EmailParserInterface $parser
     * @return string
     */
    protected function getWildcardName(EmailParserInterface $parser)
    {
        $reflection = new \ReflectionClass($parser);
        $className = $reflection->getShortName();
        $className = lcfirst(str_replace('Parser', '', $className));
        $converter = new CamelCaseToSnakeCaseNameConverter();
        $wildcardName = '%' . strtoupper($converter->normalize($className)) . '%';
        return $wildcardName;
    }

}
