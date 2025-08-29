<?php
/**
 * Plugin Name:       Simple LLMS.txt Generator
 * Description:       A simple plugin to create and manage an llms.txt file from the WordPress admin panel to control AI crawlers.
 * Version:           1.0.0
 * Author:            Darren Kandekore
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       slg
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add the options page to the admin menu.
 */
function slg_add_admin_menu() {
    add_options_page(
        'LLMS.txt Generator',          // Page title
        'LLMS.txt Generator',          // Menu title
        'manage_options',              // Capability required
        'llms_txt_generator',          // Menu slug
        'slg_options_page_html'        // Function to display the page
    );
}
add_action( 'admin_menu', 'slg_add_admin_menu' );

/**
 * Register settings, sections, and fields.
 */
function slg_settings_init() {
    // Register a setting
    register_setting( 'llms_txt_page', 'slg_options' );

    // Add a settings section for User-Agents
    add_settings_section(
        'slg_section_user_agents',
        __( 'Choose AI User-Agents to Control', 'slg' ),
        'slg_section_user_agents_callback',
        'llms_txt_page'
    );

    // Add a settings section for Disallow Rules
    add_settings_section(
        'slg_section_disallow_rules',
        __( 'Choose Directories to Disallow', 'slg' ),
        'slg_section_disallow_rules_callback',
        'llms_txt_page'
    );

    // Add fields for User-Agents
    add_settings_field(
        'slg_field_gptbot',
        __( 'GPTBot (OpenAI)', 'slg' ),
        'slg_field_checkbox_callback',
        'llms_txt_page',
        'slg_section_user_agents',
        [ 'label_for' => 'slg_field_gptbot', 'option_name' => 'gptbot' ]
    );

    add_settings_field(
        'slg_field_google_extended',
        __( 'Google-Extended (Google AI)', 'slg' ),
        'slg_field_checkbox_callback',
        'llms_txt_page',
        'slg_section_user_agents',
        [ 'label_for' => 'slg_field_google_extended', 'option_name' => 'google_extended' ]
    );

    add_settings_field(
        'slg_field_ccbot',
        __( 'CCBot (Common Crawl)', 'slg' ),
        'slg_field_checkbox_callback',
        'llms_txt_page',
        'slg_section_user_agents',
        [ 'label_for' => 'slg_field_ccbot', 'option_name' => 'ccbot' ]
    );

    // Add fields for Disallow Rules
    add_settings_field(
        'slg_field_disallow_wp_admin',
        __( 'Disallow /wp-admin/', 'slg' ),
        'slg_field_checkbox_callback',
        'llms_txt_page',
        'slg_section_disallow_rules',
        [ 'label_for' => 'slg_field_disallow_wp_admin', 'option_name' => 'disallow_wp_admin' ]
    );

    add_settings_field(
        'slg_field_disallow_wp_includes',
        __( 'Disallow /wp-includes/', 'slg' ),
        'slg_field_checkbox_callback',
        'llms_txt_page',
        'slg_section_disallow_rules',
        [ 'label_for' => 'slg_field_disallow_wp_includes', 'option_name' => 'disallow_wp_includes' ]
    );

    add_settings_field(
        'slg_field_custom_disallow',
        __( 'Custom Disallow Rules', 'slg' ),
        'slg_field_custom_disallow_callback',
        'llms_txt_page',
        'slg_section_disallow_rules'
    );
}
add_action( 'admin_init', 'slg_settings_init' );


/**
 * Callbacks for rendering sections and fields.
 */
function slg_section_user_agents_callback() {
    echo '<p>' . __( 'Select the AI crawlers you wish to add rules for. The rules below will apply to all selected agents.', 'slg' ) . '</p>';
}

function slg_section_disallow_rules_callback() {
    echo '<p>' . __( 'Select standard paths to block, and add any other custom paths you want to disallow.', 'slg' ) . '</p>';
}

function slg_field_checkbox_callback( $args ) {
    $options = get_option( 'slg_options' );
    $option_name = $args['option_name'];
    $checked = isset( $options[$option_name] ) ? 'checked' : '';
    echo "<input type='checkbox' id='{$args['label_for']}' name='slg_options[{$option_name}]' {$checked}>";
}

function slg_field_custom_disallow_callback() {
    $options = get_option( 'slg_options' );
    $custom_rules = isset( $options['custom_disallow'] ) ? esc_textarea( $options['custom_disallow'] ) : '';
    echo "<textarea name='slg_options[custom_disallow]' rows='5' cols='50' class='large-text' placeholder='/private-directory/{PHP_EOL}/some-specific-page.html'>{$custom_rules}</textarea>";
    echo '<p class="description">' . __( 'Enter one path per line, starting with a slash. e.g., <code>/wp-content/plugins/</code>', 'slg' ) . '</p>';
}

/**
 * Display the main options page HTML.
 */
function slg_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>This page allows you to create an <code>llms.txt</code> file in your website's root directory. This file works like <code>robots.txt</code> but is specifically for Large Language Models (LLMs) or AI crawlers.</p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'llms_txt_page' );
            do_settings_sections( 'llms_txt_page' );
            submit_button( 'Save Settings & Generate File' );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Generate the llms.txt file when settings are updated.
 */
function slg_generate_llms_txt_file( $old_value, $value ) {
    $file_content = '';
    $agents = [];

    // Map option keys to User-Agent strings
    if ( ! empty( $value['gptbot'] ) ) {
        $agents[] = 'GPTBot';
    }
    if ( ! empty( $value['google_extended'] ) ) {
        $agents[] = 'Google-Extended';
    }
    if ( ! empty( $value['ccbot'] ) ) {
        $agents[] = 'CCBot';
    }

    // If no agents are selected, we can stop or create an empty file.
    // Let's build the content even if agents are selected later.
    
    $disallow_rules = [];
    if ( ! empty( $value['disallow_wp_admin'] ) ) {
        $disallow_rules[] = 'Disallow: /wp-admin/';
    }
    if ( ! empty( $value['disallow_wp_includes'] ) ) {
        $disallow_rules[] = 'Disallow: /wp-includes/';
    }

    if ( ! empty( $value['custom_disallow'] ) ) {
        $custom_lines = explode( "\n", str_replace( "\r", "", $value['custom_disallow'] ) );
        foreach ( $custom_lines as $line ) {
            $line = trim( $line );
            if ( ! empty( $line ) ) {
                $disallow_rules[] = 'Disallow: ' . esc_html( $line );
            }
        }
    }

    // Build the file content string
    if ( ! empty( $agents ) && ! empty( $disallow_rules ) ) {
        foreach ( $agents as $agent ) {
            $file_content .= "User-agent: " . $agent . PHP_EOL;
        }
        $file_content .= implode( PHP_EOL, $disallow_rules ) . PHP_EOL;
    }

    // Get the path to the WordPress root directory
    $file_path = get_home_path() . 'llms.txt';

    // Write the content to the file
    $file_written = file_put_contents( $file_path, $file_content );

    // Set a transient to show an admin notice on success or failure
    if ( $file_written !== false ) {
        set_transient( 'slg_admin_notice', 'success', 5 );
    } else {
        set_transient( 'slg_admin_notice', 'error', 5 );
    }
}
// This hook fires after the option has been successfully updated in the database.
add_action( 'update_option_slg_options', 'slg_generate_llms_txt_file', 10, 2 );


/**
 * Display admin notices based on the transient.
 */
function slg_display_admin_notices() {
    if ( get_transient( 'slg_admin_notice' ) ) {
        $notice_type = get_transient( 'slg_admin_notice' );
        if ( $notice_type === 'success' ) {
            $file_url = home_url( '/llms.txt' );
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Success! The <code>llms.txt</code> file has been generated. You can <a href="%s" target="_blank">view it here</a>.', 'slg' ), esc_url($file_url) ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __( '<strong>Error:</strong> The <code>llms.txt</code> file could not be created. Please check if your web server has permission to write to the WordPress root directory.', 'slg' ) . '</p></div>';
        }
        delete_transient( 'slg_admin_notice' );
    }
}
add_action( 'admin_notices', 'slg_display_admin_notices' );