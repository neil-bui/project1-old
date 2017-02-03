<?php
/**
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com/)
 *
 * This file is part of TubePress Pro.
 *
 * License summary
 *   - Can be used on 1 site, 1 server
 *   - Cannot be resold or distributed
 *   - Commercial use allowed
 *   - Can modify source-code but cannot distribute modifications (derivative works)
 *
 * Please see http://tubepress.com/license for details.
 */

/**
 * @api
 * @since 4.2.0
 */
class tubepress_pro_shortcode_impl_ShortcodeExtractor implements tubepress_api_shortcode_ShortcodeExtractorInterface
{
    /**
     * ATTRIBUTES REGEX
     *
     * (\w+)                            // 1. word characters
     * \s*=\s*                          // =, with optional whitespace around it
     * "                                // "
     * (
     *      (?:
     *          (?:\\\")
     *          |
     *          [^"]
     *      )*
     * )
     * "                                // "
     * (?:\s|$)                         // a whitespace character, of the end of the string
     *
     *
     * |
     *
     *
     * (\w+)                            // 3. word characters
     * \s*=\s*                          // =, with optional whitespace around it
     * \'                               // '
     * (
     *      (?:
     *          (?:\\\')
     *          |
     *          [^\']
     *      )*
     * )
     * \'                               // '
     * (?:\s|$)                         // a whitespace character, of the end of the string
     *
     *
     * |
     *
     *
     * (\w+)                            // 5. word characters
     * \s*=\s*                          // =, with optional whitespace around it
     * ([^\s\'"]+)                      // 6. not whitespace, or quotes
     * (?:\s|$)                         // a whitespace character, of the end of the string
     *
     *
     * |
     *
     *
     * "                                // quote
     * ([^"]*)                          // 7. anything but a double quote
     * "                                // quote
     * (?:\s|$)                         // a whitespace character, of the end of the string
     *
     *
     * |
     *
     *
     * (\S+)                            // 8. non-whitespace character
     * (?:\s|$)                         // a whitespace character, of the end of the string
     *
     */

    private static $_REGEX_ATTRIBUTES = '/(\w+)\s*=\s*"((?:(?:\\\")|[^"])*)"(?:\s|$)|(\w+)\s*=\s*\'((?:(?:\\\')|[^\'])*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    private static $_REGEX_CACHE      = array();

    /**
     * @api
     * @since 4.2.0
     *
     * @param string $name The shortcode name to look for.
     * @param string $text The text to scan.
     *
     * @return array An array, which may be empty but never null, of
     *               tubepress_api_shortcode_ShortcodeInterface instances.
     */
    public function getShortcodes($name, $text)
    {
        if (!$text || !is_string($text)) {

            return array();
        }

        //ensure that the name is valid
        new tubepress_shortcode_impl_Shortcode($name);

        if (!$this->_containsShortcode($name, $text)) {

            return array();
        }

        $regex = $this->_getShortcodeRegex($name);

        preg_match_all("/$regex/s", $text, $matches, PREG_SET_ORDER);

        $toReturn = array();

        foreach ($matches as $match) {

            $fullMatchText = $match[0];
            $shortCode     = $this->_buildShortcodeFromMatch($match, $fullMatchText);

            $toReturn[] = $shortCode;
        }

        return $toReturn;
    }

    private function _buildShortcodeFromMatch($m, $fullMatchText)
    {
        $name       = $m[1];
        $attributes = $this->_collectAttributes($m[2]);

        if (isset($m[4]) && $m[4]) {

            // enclosing tag - extra parameter
            return new tubepress_shortcode_impl_Shortcode($name, $attributes, $m[4], $fullMatchText);

        } else {

            // self-closing tag
            return new tubepress_shortcode_impl_Shortcode($name, $attributes, null, $fullMatchText);
        }
    }

    private function _collectAttributes($text)
    {
        $toReturn = array();
        $pattern  = self::$_REGEX_ATTRIBUTES;
        $text     = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);     //no-break space or zero-width space

        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {

            foreach ($match as $m) {

                if (!empty($m[1])) {

                    $toReturn[$m[1]] = $this->_unquote($m[2]);

                } elseif (!empty($m[3])) {

                    $toReturn[$m[3]] = $this->_unquote($m[4]);

                } elseif (!empty($m[5])) {

                    $toReturn[$m[5]] = $this->_unquote($m[6]);

                } elseif (isset($m[7]) && strlen($m[7])) {

                    $toReturn[] = $this->_unquote($m[7]);

                } elseif (isset($m[8])) {

                    $toReturn[] = $this->_unquote($m[8]);
                }
            }

        } else {

            $toReturn = array();
        }

        return $this->_toAssociativeArray($toReturn);
    }

    private function _unquote($incoming)
    {
        return str_replace(array('\\\'', '\"'), array('\'', '"'), $incoming);
    }

    private function _toAssociativeArray(array $incoming)
    {
        $toReturn = array();

        foreach ($incoming as $key => $value) {

            if (is_string($key)) {

                $toReturn[$key] = $value;

            } else {

                $toReturn[$value] = null;
            }
        }

        return $toReturn;
    }

    private function _containsShortcode($name, $text)
    {
        if (strpos($text, '[') === false || strpos($text, ']') === false) {

            return array();
        }

        $shortcodeRegex = $this->_getShortcodeRegex($name);

        preg_match_all("/$shortcodeRegex/s", $text, $matches, PREG_SET_ORDER);

        if (empty($matches)) {

            return false;
        }

        foreach ($matches as $shortcode) {

            if ($shortcode[1] === $name) {

                return true;
            }

            if (!empty($shortcode[4]) && $this->_containsShortcode($name, $shortcode[4])) {

                return true;
            }
        }

        return false;
    }

    private function _getShortcodeRegex($name)
    {
        $cacheKey = "shortcode-$name";

        if (!isset(self::$_REGEX_CACHE[$cacheKey])) {

            $optionalWhitespace  = '\s*';
            $openingBracket      = '\\[';
            $closingBracket      = '\\]';
            $forwardSlash        = '\\/';
            $closingTag          = "$optionalWhitespace$forwardSlash$optionalWhitespace\\1$optionalWhitespace$closingBracket";
            $notAnOpeningBracket = "[^$openingBracket]";

            $regex =

                $openingBracket                                   // [
                . $optionalWhitespace
                . "($name)"                                         // 1: Shortcode name
                . '('                                               // 2: Inside the opening shortcode tag
                .     "[^$closingBracket$forwardSlash]*"            // Optionally anything except ] or /
                .     '(?:'
                .         "$forwardSlash(?!$closingBracket)"        // / not followed by ]
                .     ')*?'
                . ')'
                . '(?:'
                .     "($forwardSlash)"                             // 3: Self closing tag, e.g. /
                .     $optionalWhitespace
                .     $closingBracket                               // ]
                . '|'
                .     $optionalWhitespace
                .     $closingBracket                               // ]
                .     '(?:'
                .         '('                                       // 4: Anything between the opening and closing shortcode tags. i.e. the content
                .             "$notAnOpeningBracket*+"              // anything but [
                .             '(?:'
                .                 "$openingBracket(?!$closingTag)"  // [ not followed by /tubepress]
                .                 "$notAnOpeningBracket*+"            // anything but [
                .             ')*+'
                .         ')'
                .         "$openingBracket$closingTag"              // e.g. [/tubepress]
                .     ')?'
                . ')';

            self::$_REGEX_CACHE[$cacheKey] = $regex;
        }

        return self::$_REGEX_CACHE[$cacheKey];
    }
}