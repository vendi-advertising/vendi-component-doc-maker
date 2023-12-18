<?php

function esc_html_e($text, $domain = 'default')
{
    echo esc_html($text);
}

function esc_html($text)
{
    $safe_text = wp_check_invalid_utf8($text);
    $safe_text = _wp_specialchars($safe_text, ENT_QUOTES);

    /**
     * Filters a string cleaned and escaped for output in HTML.
     *
     * Text passed to esc_html() is stripped of invalid or special characters
     * before output.
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text The text prior to being escaped.
     * @since 2.8.0
     *
     */
    return $safe_text;
}


function vendi_maybe_get_row_id_attribute()
{
    return 'row-1';
}

function get_sub_field(...$args): mixed
{
    global $sub_fields;

    $selected = $args[0];
    $format = $args[1] ?? false;

    if (!array_key_exists($selected, $sub_fields)) {
        throw new RuntimeException('Sub field not found: '.$selected);
    }

    $value = $sub_fields[$selected];

    if (is_string($value) && str_starts_with($value, '@lorem/')) {

        $commandParts = explode('/', $value);
        $command = match ($commandParts[2]) {
            'paragraphs' => 'paragraphs',
            'sentences' => 'sentences',
            'words' => 'words',
            default => throw new RuntimeException('Unknown lorem command: '.$commandParts[2]),
        };

        $lipsum = new joshtronic\LoremIpsum();

        $count = (int)$commandParts[1];

        $value = $lipsum->$command($count, 'p');
    }

    return $value;
}

function esc_attr_e($text, $domain = 'default')
{
    echo esc_attr($text);
}


function wp_check_invalid_utf8($text, $strip = false)
{
    $text = (string)$text;

    if (0 === strlen($text)) {
        return '';
    }

    // Store the site charset as a static to avoid multiple calls to get_option().
    static $is_utf8 = null;
    if (!isset($is_utf8)) {
        $is_utf8 = true;
    }
    if (!$is_utf8) {
        return $text;
    }

    // Check for support for utf8 in the installed PCRE library once and store the result in a static.
    static $utf8_pcre = null;
    if (!isset($utf8_pcre)) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $utf8_pcre = @preg_match('/^./u', 'a');
    }
    // We can't demand utf8 in the PCRE installation, so just return the string in those cases.
    if (!$utf8_pcre) {
        return $text;
    }

    // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- preg_match fails when it encounters invalid UTF8 in $text.
    if (1 === @preg_match('/^./us', $text)) {
        return $text;
    }

    // Attempt to strip the bad chars if requested (not recommended).
    if ($strip && function_exists('iconv')) {
        return iconv('utf-8', 'utf-8', $text);
    }

    return '';
}


function esc_attr($text)
{
    $safe_text = wp_check_invalid_utf8($text);
    $safe_text = _wp_specialchars($safe_text, ENT_QUOTES);

    /**
     * Filters a string cleaned and escaped for output in an HTML attribute.
     *
     * Text passed to esc_attr() is stripped of invalid or special characters
     * before output.
     *
     * @param string $safe_text The text after it has been escaped.
     * @param string $text The text prior to being escaped.
     * @since 2.0.6
     *
     */
    return $safe_text;
}


function _wp_specialchars($text, $quote_style = ENT_NOQUOTES, string|bool $charset = false, $double_encode = false)
{
    $text = (string)$text;

    if ($text === '') {
        return '';
    }

    // Don't bother if there are no specialchars - saves some processing.
    if (!preg_match('/[&<>"\']/', $text)) {
        return $text;
    }

    // Account for the previous behavior of the function when the $quote_style is not an accepted value.
    if (empty($quote_style)) {
        $quote_style = ENT_NOQUOTES;
    } elseif (ENT_XML1 === $quote_style) {
        $quote_style = ENT_QUOTES | ENT_XML1;
    } elseif (!in_array($quote_style, array(ENT_NOQUOTES, ENT_COMPAT, ENT_QUOTES, 'single', 'double'), true)) {
        $quote_style = ENT_QUOTES;
    }

    // Store the site charset as a static to avoid multiple calls to wp_load_alloptions().
    if (!$charset) {
        $charset = '';
    }

    if (in_array($charset, array('utf8', 'utf-8', 'UTF8'), true)) {
        $charset = 'UTF-8';
    }

    $_quote_style = $quote_style;

    if ('double' === $quote_style) {
        $quote_style = ENT_COMPAT;
        $_quote_style = ENT_COMPAT;
    } elseif ('single' === $quote_style) {
        $quote_style = ENT_NOQUOTES;
    }

    if (!$double_encode) {
        /*
         * Guarantee every &entity; is valid, convert &garbage; into &amp;garbage;
         * This is required for PHP < 5.4.0 because ENT_HTML401 flag is unavailable.
         */
        $text = wp_kses_normalize_entities($text, ($quote_style & ENT_XML1) ? 'xml' : 'html');
    }

    $text = htmlspecialchars($text, $quote_style, $charset, $double_encode);

    // Back-compat.
    if ('single' === $_quote_style) {
        $text = str_replace("'", '&#039;', $text);
    }

    return $text;
}


function wp_kses_normalize_entities($content, $context = 'html')
{
    // Disarm all entities by converting & to &amp;
    $content = str_replace('&', '&amp;', $content);

    // Change back the allowed entities in our list of allowed entities.
    if ('xml' === $context) {
        $content = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_xml_named_entities', $content);
    } else {
        $content = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_named_entities', $content);
    }
    $content = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'wp_kses_normalize_entities2', $content);
    $content = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'wp_kses_normalize_entities3', $content);

    return $content;
}

function vendi_apply_the_content_filter_and_echo(mixed $content, bool $echo = true): ?string
{
    if (!is_string($content)) {
        return null;
    }

    if ($echo) {
        echo $content;
    }

    return $content;
}