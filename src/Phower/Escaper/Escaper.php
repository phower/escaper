<?php

namespace Phower\Escaper;

use InvalidArgumentException;
use RuntimeException;

class Escaper implements EscaperInterface
{

    /**
     * @var array
     */
    protected $htmlNamedEntityMap = [
        34 => 'quot',
        38 => 'amp',
        60 => 'lt',
        62 => 'gt',
    ];

    /**
     * @var string
     */
    protected $encoding = 'utf-8';

    /**
     * @var string
     */
    protected $htmlSpecialCharsFlags = ENT_QUOTES;

    /**
     * @var callable
     */
    protected $attributeMatcher;

    /**
     * @var callable
     */
    protected $jsMatcher;

    /**
     * @var callable
     */
    protected $cssMatcher;

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
     * Construct
     * 
     * @param string $encoding
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($encoding = null)
    {
        if ($encoding !== null) {
            $encoding = strtolower($encoding);
            if (!in_array($encoding, $this->supportedEncodings)) {
                throw new InvalidArgumentException(sprintf('Encoding "%s" is not supported.', $encoding));
            }
            $this->encoding = $encoding;
        }

        if (defined('ENT_SUBSTITUTE')) {
            $this->htmlSpecialCharsFlags |= ENT_SUBSTITUTE;
        }

        $this->attributeMatcher = [$this, 'attributeMatcher'];
        $this->jsMatcher = [$this, 'jsMatcher'];
        $this->cssMatcher = [$this, 'cssMatcher'];
    }

    /**
     * Get encoding
     * 
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Attribute matcher
     * 
     * @param array $matches
     * @return string
     */
    protected function attributeMatcher(array $matches)
    {
        $chr = $matches[0];
        $ord = ord($chr);

        /**
         * The following replaces characters undefined in HTML with the
         * hex entity for the Unicode replacement character.
         */
        if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r") ||
                ($ord >= 0x7f && $ord <= 0x9f)
        ) {
            return '&#xFFFD;';
        }

        /**
         * Check if the current character to escape has a name entity we should
         * replace it with while grabbing the integer value of the character.
         */
        if (strlen($chr) > 1) {
            $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');
        }

        $hex = bin2hex($chr);
        $ord = hexdec($hex);
        if (isset($this->htmlNamedEntityMap[$ord])) {
            return '&' . $this->htmlNamedEntityMap[$ord] . ';';
        }

        /**
         * Per OWASP recommendations, we'll use upper hex entities
         * for any other characters where a named entity does not exist.
         */
        if ($ord > 255) {
            return sprintf('&#x%04X;', $ord);
        }
        return sprintf('&#x%02X;', $ord);
    }

    /**
     * JS matcher
     * 
     * @param array $matches
     * @return string
     */
    protected function jsMatcher(array $matches)
    {
        $chr = $matches[0];

        if (strlen($chr) == 1) {
            return sprintf('\\x%02X', ord($chr));
        }

        $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');

        return sprintf('\\u%04s', strtoupper(bin2hex($chr)));
    }

    /**
     * CSS matcher
     * 
     * @param array $matches
     * @return string
     */
    protected function cssMatcher(array $matches)
    {
        $chr = $matches[0];

        if (strlen($chr) == 1) {
            $ord = ord($chr);
        } else {
            $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');
            $ord = hexdec(bin2hex($chr));
        }

        return sprintf('\\%X ', $ord);
    }

    /**
     * Convert string to UTF-8
     * 
     * @param string $string
     * @return string
     * @throws RuntimeException
     */
    protected function toUtf8($string)
    {
        if ($this->getEncoding() === 'utf-8') {
            $result = $string;
        } else {
            $result = $this->convertEncoding($string, 'UTF-8', $this->getEncoding());
        }

        return $result;
    }

    /**
     * Convert string from UTF-8
     * 
     * @param string $string
     * @return string
     */
    protected function fromUtf8($string)
    {
        if ($this->getEncoding() === 'utf-8') {
            return $string;
        }

        return $this->convertEncoding($string, $this->getEncoding(), 'UTF-8');
    }

    /**
     * Convert encoding
     * 
     * @param string $string
     * @param string $to
     * @param string $from
     * @return string
     * @throws RuntimeException
     */
    protected function convertEncoding($string, $to, $from)
    {
        if (function_exists('iconv')) {
            $result = iconv($from, $to, $string);
        } elseif (function_exists('mb_convert_encoding')) {
            $result = mb_convert_encoding($string, $to, $from);
        } else {
            throw new RuntimeException(sprintf('%s requires either the iconv or'
                    . ' mbstring extension to be installed when escaping for non'
                    . ' UTF-8 strings.', get_class($this)));
        }

        if ($result === false) {
            $result = '';
        }

        return $result;
    }

    /**
     * Escape HTML
     * 
     * @param string $html
     * @return string
     */
    public function escapeHtml($html)
    {
        return htmlspecialchars($html, $this->htmlSpecialCharsFlags, $this->encoding);
    }

    /**
     * Escape attribute
     * 
     * @param string $attribute
     * @return string
     */
    public function escapeAttribute($attribute)
    {
        $attribute = $this->toUtf8($attribute);

        if ($attribute === '' || ctype_digit($attribute)) {
            return $attribute;
        }

        $result = preg_replace_callback('/[^a-z0-9,\.\-_]/iSu', $this->attributeMatcher, $attribute);
        return $this->fromUtf8($result);
    }

    /**
     * Escape JS
     * 
     * @param string $js
     * @return string
     */
    public function escapeJs($js)
    {
        $js = $this->toUtf8($js);
        
        if ($js === '' || ctype_digit($js)) {
            return $js;
        }

        $result = preg_replace_callback('/[^a-z0-9,\._]/iSu', $this->jsMatcher, $js);
        
        return $this->fromUtf8($result);
    }

    /**
     * Escape CSS
     * 
     * @param string $css
     * @return string
     */
    public function escapeCss($css)
    {
        $css = $this->toUtf8($css);

        if ($css === '' || ctype_digit($css)) {
            return $css;
        }

        $result = preg_replace_callback('/[^a-z0-9]/iSu', $this->cssMatcher, $css);

        return $this->fromUtf8($result);
    }

    /**
     * Escape url
     * 
     * @param string $url
     * @return string
     */
    public function escapeUrl($url)
    {
        return rawurlencode($url);
    }

}
