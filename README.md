About
=====

This bundle, in its present state, can do two things. First, it fetches an external file
(either from your system or from the internet) and sends it as an email message. Second,
it can also replace any wildcard that is included in that file into any value you want.
The rules for wildcard parsing can include any kind of PHP code.

Installation
============

This bundle depends on Symfony and VKRSettingsBundle, see that bundle's docs for details
of its installation.

You need to configure some settings.

*mailer_from_address* and *mailer_from_name* correspond to the name and address in email
*From* header.

There are two more settings with arbitrary names, one for the external file location, and
another for the email subject. You need to define a pair of these settings for every
message you want to send using this bundle. File location settings can be either full
system paths or URLs.

See VKRSettingsBundle documentation on how to configure settings.

Usage
=====

Without wildcards
-----------------

If you don't have any wildcards, the usage is trivial. Write this code in your controller:

```
$emailParser = $this->get('vkr_external_email.parser');
$message = $emailParser->parse('file_location_setting', 'email_subject_setting',
                               'receiver@address.com', []);
$mailer = $this->get('mailer');
$mailer->send($message);
```

With wildcards
--------------

You can include an arbitrary number of wildcards to be parsed in your external file. If you
choose to use them, you need to write some extra code.

### Wildcard names

Wildcard names can include only alphanumeric uppercase ASCII characters and underscores
and must begin with a letter. You need to include % signs before and after a wildcard:
```%MY_WILDCARD%```

### Wildcard parsers

For every wildcard you create, you need to write a parser class that defines how this wildcard
will be transformed. A parser class is just a PHP class that must implement
*VKR\ExternalEmailBundle\Interfaces\EmailParserInterface* and define its *parse()* method.

The parser class name should conform to a convention: camel-cased wildcard name with 'Parser'
appended to it. So, if your wildcard is called *MY_WILDCARD*, your parser class should
be called *MyWildcardParser*. There is no convention regarding namespaces for those classes.

Here is an example of a simple parser class:

```
class MyWildcardParser implements VKR\ExternalEmailBundle\Interfaces\EmailParserInterface
{
    public function parse($additionalArguments=[])
    {
        return 'foo';
    }
}
```

This code means that every instance of ```%MY_WILDCARD%``` in your external file will be
converted to ```foo```.

After defining your parser classes, you must bootstrap them. In order to do it, you must pass
an array of parser class instances as the fourth argument to the *parse()* method.

```
$wildcardParsers = [
    new MyWildcardParser(),
    new SomeOtherWildcardParser(),
];
$message = $emailParser->parse('file_location_setting', 'email_subject_setting',
                               'receiver@address.com', $wildcardParsers);
```

It is recommended that you register all your parsers as Symfony services and instantiate
them via *$this->get()*.

### Additional arguments

You might want to pass arguments to your parser's *parse()* method. In order to do it,
you can specify any number of additional arguments and pass them as a fifth argument.

```
$wildcardParsers = [
    new MyWildcardParser(),
];
$args = [
    'value' => 'bar',
];
$message = $emailParser->parse('file_location_setting', 'email_subject_setting',
                               'receiver@address.com', $wildcardParsers);
```

Then, in your parser:

```
    public function parse($additionalArguments=[])
    {
        if (isset($additionalArguments['value'])) {
            return $additionalArguments['value'];
        }
        return 'foo';
    }
```

Now ```%MY_WILDCARD%``` will be changed to 'bar' rather than 'foo'.

An important thing to note is that if you have several parsers, all arguments will be
passed to every parser. To avoid unexpected behavior, use unique argument names for every
parser you have.

API
===

*void ExternalEmailParser::__construct(VKR\SettingsBundle\Services\SettingsRetriever $settingsRetriever)*

*Swift_Message ExternalEmailParser::parse(string $emailFileSettingName, string $emailSubjectSettingName, string $receiverAddress, VKR\ExternalEmailBundle\Interfaces\EmailParserInterface[] $wildcardParsers, array $additionalArguments=[])*

The fourth argument cannot be empty, if you don't have any parsers, pass empty array.
