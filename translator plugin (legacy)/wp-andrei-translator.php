<?php

/**
 * Plugin Name: Andrei's Page Translator (DeepL)
 * Description: Translates all pages into a specific language using the DeepL API.
 * Version: 1.2
 * Author: Andrei Bogdan
 */

// Block direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Load the plugin text domain for localization (if you plan to add language files in the future)
function custom_translator_load_textdomain()
{
    load_plugin_textdomain('custom-translator', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'custom_translator_load_textdomain');

// Add plugin settings to the admin panel
function custom_translator_add_settings_page()
{
    add_options_page('Custom Translator Settings', 'Translator Settings', 'manage_options', 'custom-translator-settings', 'custom_translator_render_settings_page');
}
add_action('admin_menu', 'custom_translator_add_settings_page');

// Render settings page
function custom_translator_render_settings_page()
{
?>
    <div class="wrap">
        <h2>Andrei Translator Settings</h2>
        <form method="post" action="options.php" id="translatorSettingsForm">
            <?php settings_fields('custom_translator_settings'); ?>
            <?php do_settings_sections('custom_translator_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">DeepL API Key</th>
                    <td>
                        <input type="text" name="custom_translator_deepl_key" value="<?php echo esc_attr(get_option('custom_translator_deepl_key')); ?>" placeholder="Enter your DeepL API key" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Select Language</th>
                    <td>
                        <select name="custom_translator_language" id="custom_translator_language">
                            <option value="en" <?php selected(get_option('custom_translator_language'), 'en'); ?>>English</option>
                            <option value="ro" <?php selected(get_option('custom_translator_language'), 'ro'); ?>>Romanian</option>
                            <option value="fr" <?php selected(get_option('custom_translator_language'), 'fr'); ?>>French</option>
                            <option value="de" <?php selected(get_option('custom_translator_language'), 'de'); ?>>German</option>
                            <!-- Add more languages as needed -->
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

// Register plugin settings
function custom_translator_register_settings()
{
    register_setting('custom_translator_settings', 'custom_translator_deepl_key');
    register_setting('custom_translator_settings', 'custom_translator_language');
}
add_action('admin_init', 'custom_translator_register_settings');

// Translate content based on the selected language
function custom_translator_translate_page($content)
{
    $selected_language = get_option('custom_translator_language', 'en');
    $deepl_api_key = get_option('custom_translator_deepl_key');

    // If no API key is set, return the original content
    if (empty($deepl_api_key)) {
        return '<p>No DeepL API key provided. Showing original content.</p>' . $content;
    }

    // Skip translation if the language is English
    if ($selected_language === 'en') {
        return $content;
    }

    // Call the translation function
    $translated_content = custom_translate_function_deepl($content, $selected_language, $deepl_api_key);

    return $translated_content;
}
add_filter('the_content', 'custom_translator_translate_page');

// Translation function using DeepL API
function custom_translate_function_deepl($text, $language, $api_key)
{
    $url = 'https://api-free.deepl.com/v2/translate';

    // Make a POST request to the DeepL API
    $response = wp_remote_post($url, array(
        'body' => array(
            'auth_key' => $api_key,
            'text' => $text,
            'target_lang' => strtoupper($language), // DeepL expects uppercase language codes
        ),
    ));

    // Check if the API request failed
    if (is_wp_error($response)) {
        error_log('DeepL API request failed: ' . $response->get_error_message());
        return '<p>Translation failed. Showing original content.</p>' . $text;
    }

    // Retrieve the response body and decode JSON
    $body = wp_remote_retrieve_body($response);
    $response_data = json_decode($body, true);

    // Check if translation was successful
    if (isset($response_data['translations'][0]['text'])) {
        return esc_html($response_data['translations'][0]['text']);
    }

    // If no translation is available, log the error and return original text
    error_log('DeepL translation failed: ' . print_r($response_data, true));
    return '<p>Translation not available. Showing original content.</p>' . $text;
}
