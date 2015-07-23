<?php

namespace Phower\Escaper;

use Phower\EscaperTest\EscaperTest;

function function_exists($name)
{
    if ($name === 'iconv' && null !== EscaperTest::$mockFunctionExistsIconv) {
        return EscaperTest::$mockFunctionExistsIconv;
    } elseif ($name === 'mb_convert_encoding' && null !== EscaperTest::$mockFunctionExistsMbConvertEncoding) {
        return EscaperTest::$mockFunctionExistsMbConvertEncoding;
    }
    return \function_exists($name);
}

function iconv($from, $to, $string)
{
    if (null !== EscaperTest::$mockIconv) {
        return EscaperTest::$mockIconv;
    }
    return \iconv($from, $to, $string);
}

namespace Phower\EscaperTest;

use PHPUnit_Framework_TestCase;
use Phower\Escaper\Escaper;

class EscaperTest extends PHPUnit_Framework_TestCase
{

    public static $mockFunctionExistsIconv = null;
    public static $mockFunctionExistsMbConvertEncoding = null;
    public static $mockIconv = null;

    /**
     * @var array
     */
    protected $supportedEncodings = [
        'iso-8859-1', 'iso8859-1', 'iso-8859-5', 'iso8859-5',
        'iso-8859-15', 'iso8859-15', 'utf-8', 'cp866',
        'ibm866', '866', 'cp1251', 'windows-1251',
        'win-1251', '1251', 'cp1252', 'windows-1252',
        '1252', 'koi8-r', 'koi8-ru', 'koi8r',
        'big5', '950', 'gb2312', '936',
        'big5-hkscs', 'shift_jis', 'sjis', 'sjis-win',
        'cp932', '932', 'euc-jp', 'eucjp',
        'eucjp-win', 'macroman'
    ];

    /**
     * @var array
     */
    protected $htmlSpecialChars = [
        '\'' => '&#039;',
        '"' => '&quot;',
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;'
    ];

    /**
     * @var array
     */
    protected $attributesSpecialChars = [
        '\'' => '&#x27;',
        '"' => '&quot;',
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '&#x0100;',
        /* Immune chars excluded */
        ',' => ',',
        '.' => '.',
        '-' => '-',
        '_' => '_',
        /* Basic alnums exluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '&#x0D;',
        "\n" => '&#x0A;',
        "\t" => '&#x09;',
        "\0" => '&#xFFFD;', // should use Unicode replacement char
        /* Encode chars as named entities where possible */
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
        '"' => '&quot;',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '&#x20;',
    ];

    /**
     * @var array
     */
    protected $jsSpecialChars = [
        /* HTML special chars - escape without exception to hex */
        '<' => '\\x3C',
        '>' => '\\x3E',
        '\'' => '\\x27',
        '"' => '\\x22',
        '&' => '\\x26',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '\\u0100',
        /* Immune chars excluded */
        ',' => ',',
        '.' => '.',
        '_' => '_',
        /* Basic alnums exluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '\\x0D',
        "\n" => '\\x0A',
        "\t" => '\\x09',
        "\0" => '\\x00',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '\\x20',
    ];

    /**
     * @var array
     */
    protected $urlSpecialChars = [
        /* HTML special chars - escape without exception to percent encoding */
        '<' => '%3C',
        '>' => '%3E',
        '\'' => '%27',
        '"' => '%22',
        '&' => '%26',
        /* Characters beyond ASCII value 255 to hex sequence */
        'Ā' => '%C4%80',
        /* Punctuation and unreserved check */
        ',' => '%2C',
        '.' => '.',
        '_' => '_',
        '-' => '-',
        ':' => '%3A',
        ';' => '%3B',
        '!' => '%21',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '%0D',
        "\n" => '%0A',
        "\t" => '%09',
        "\0" => '%00',
        /* PHP quirks from the past */
        ' ' => '%20',
        '~' => '~',
        '+' => '%2B',
    ];

    /**
     * @var array
     */
    protected $cssSpecialChars = [
        /* HTML special chars - escape without exception to hex */
        '<' => '\\3C ',
        '>' => '\\3E ',
        '\'' => '\\27 ',
        '"' => '\\22 ',
        '&' => '\\26 ',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '\\100 ',
        /* Immune chars excluded */
        ',' => '\\2C ',
        '.' => '\\2E ',
        '_' => '\\5F ',
        /* Basic alnums exluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '\\D ',
        "\n" => '\\A ',
        "\t" => '\\9 ',
        "\0" => '\\0 ',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '\\20 ',
    ];

    protected function setUp()
    {
        parent::setUp();

        self::$mockFunctionExistsIconv = null;
        self::$mockFunctionExistsMbConvertEncoding = null;
        self::$mockIconv = null;
    }

    public function testEscaperClassImplementsEscaperInterface()
    {
        $escaper = new Escaper();
        $this->assertInstanceOf('Phower\Escaper\EscaperInterface', $escaper);
    }

    public function testConstructMethodAcceptsEncodingArgumentToBeNull()
    {
        $escaper = new Escaper(null);
        $this->assertInstanceOf('Phower\Escaper\EscaperInterface', $escaper);
    }

    public function testConstructMethodAcceptsEncodingArgumentToBeASupportedEncodingName()
    {
        foreach ($this->supportedEncodings as $encoding) {
            $escaper = new Escaper($encoding);
            $this->assertInstanceOf('Phower\Escaper\EscaperInterface', $escaper);
        }
    }

    public function testConstructMethodRequiresEncodingArgumentToBeANonEmptyString()
    {
        $this->setExpectedException('InvalidArgumentException');
        $escaper = new Escaper('');
    }

    public function testConstructMethodRequiresEncodingArgumentToBeASupportedEncodingName()
    {
        $this->setExpectedException('InvalidArgumentException');
        $escaper = new Escaper('my-encoding');
    }

    public function testConstructMethodDefaultsEncodingArgumentToUtf8()
    {
        $escaper = new Escaper();
        $this->assertEquals('utf-8', $escaper->getEncoding());
    }

    public function testGetEncodingMethodReturnsEncodingInUse()
    {
        $escaper = new Escaper('iso-8859-1');
        $this->assertEquals('iso-8859-1', $escaper->getEncoding());
    }

    public function testEscapeHtmlMethodReturnsSpecialHtmlCharacterEscaped()
    {
        $escaper = new Escaper();
        foreach ($this->htmlSpecialChars as $original => $escaped) {
            $this->assertEquals($escaped, $escaper->escapeHtml($original));
        }
    }

    public function testEscapeAttributesMethodReturnsSpecialAttributesCharacterEscaped()
    {
        $escaper = new Escaper();
        foreach ($this->attributesSpecialChars as $original => $escaped) {
            $this->assertEquals($escaped, $escaper->escapeAttribute($original));
        }
    }

    public function testEscapeJsMethodReturnsSpecialJsCharacterEscaped()
    {
        $escaper = new Escaper();
        foreach ($this->jsSpecialChars as $original => $escaped) {
            $this->assertEquals($escaped, $escaper->escapeJs($original));
        }
    }

    public function testEscapeCssMethodReturnsSpecialCssCharacterEscaped()
    {
        $escaper = new Escaper();
        foreach ($this->cssSpecialChars as $original => $escaped) {
            $this->assertEquals($escaped, $escaper->escapeCss($original));
        }
    }

    public function testEscapeUrlMethodReturnsSpecialUrlCharacterEscaped()
    {
        $escaper = new Escaper();
        foreach ($this->urlSpecialChars as $original => $escaped) {
            $this->assertEquals($escaped, $escaper->escapeUrl($original));
        }
    }

    public function testConvertEncodingRequiresOneOfIconvOrMbConvertEncodingFunctions()
    {
        $escaper = new Escaper('iso-8859-1');
        $chars = $this->attributesSpecialChars;

        reset($chars);
        $original = key($chars);
        $escaped = current($chars);

        self::$mockFunctionExistsIconv = true;
        self::$mockFunctionExistsMbConvertEncoding = false;
        $this->assertEquals($escaped, $escaper->escapeAttribute($original));
        self::$mockFunctionExistsIconv = null;
        self::$mockFunctionExistsMbConvertEncoding = null;

        self::$mockFunctionExistsIconv = false;
        self::$mockFunctionExistsMbConvertEncoding = true;
        $this->assertEquals($escaped, $escaper->escapeAttribute($original));
        self::$mockFunctionExistsIconv = null;
        self::$mockFunctionExistsMbConvertEncoding = null;

        self::$mockFunctionExistsIconv = false;
        self::$mockFunctionExistsMbConvertEncoding = false;
        $this->setExpectedException('RuntimeException');
        $this->assertEquals($escaped, $escaper->escapeAttribute($original));
    }

    public function testConvertEncodingReturnsEmptyStringOnFailedConversion()
    {
        $escaper = new Escaper('iso-8859-1');
        self::$mockIconv = false;
        $this->assertEquals('', $escaper->escapeAttribute('abc'));
    }

    public function testEscapeJsReturnsEmptyStringAndDigitsAsIs()
    {
        $escaper = new Escaper('iso-8859-1');
        $this->assertEquals('', $escaper->escapeJs(''));
        $this->assertEquals('123', $escaper->escapeJs('123'));
    }

    public function testEscapeCssReturnsEmptyStringAndDigitsAsIs()
    {
        $escaper = new Escaper('iso-8859-1');
        $this->assertEquals('', $escaper->escapeCss(''));
        $this->assertEquals('123', $escaper->escapeCss('123'));
    }

}
