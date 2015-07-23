<?php

namespace Phower\Escaper;

interface EscaperInterface
{

    /**
     * Escape HTML
     * 
     * @param string $html
     * @return string
     */
    public function escapeHtml($html);

    /**
     * Escape attribute
     * 
     * @param string $attribute
     * @return string
     */
    public function escapeAttribute($attribute);

    /**
     * Escape JS
     * 
     * @param string $js
     * @return string
     */
    public function escapeJs($js);

    /**
     * Escape CSS
     * 
     * @param string $css
     * @return string
     */
    public function escapeCss($css);

    /**
     * Escape CSS
     * 
     * @param string $url
     * @return string
     */
    public function escapeUrl($url);
}
