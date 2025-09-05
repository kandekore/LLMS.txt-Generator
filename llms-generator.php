<?php
/**
 * Plugin Name:       Simple LLMS.txt Generator
 * Description:       Create and manage an llms.txt file from the WordPress admin to control AI crawlers. Includes one-click generate and download.
 * Version:           1.2.0
 * Author:            D Kandekore
 * Author URI:        https://darrenk.uk
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-llms-txt-generator
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Ensure core helpers are available.
 */
if ( ! function_exists( 'get_home_path' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
require_once ABSPATH . 'wp-admin/includes/file.php';

/**
 * Admin menu.
 */
function llms_txt_generator_add_admin_menu() {
    add_options_page(
        esc_html__( 'LLMS.txt Generator', 'simple-llms-txt-generator' ),
        esc_html__( 'LLMS.txt Generator', 'simple-llms-txt-generator' ),
        'manage_options',
        'llms_txt_generator',
        'llms_txt_generator_options_page_html'
    );
}
add_action( 'admin_menu', 'llms_txt_generator_add_admin_menu' );

/**
 * Settings registration.
 */
function llms_txt_generator_settings_init() {
    register_setting( 'llms_txt_page', 'slg_options', 'llms_txt_generator_options_sanitize' );

    add_settings_section(
        'slg_section_user_agents',
        esc_html__( '1. Choose AI User-Agents', 'simple-llms-txt-generator' ),
        'llms_txt_generator_section_user_agents_callback',
        'llms_txt_page'
    );

    add_settings_section(
        'slg_section_usage_policies',
        esc_html__( '2. Set AI Usage Policies', 'simple-llms-txt-generator' ),
        'llms_txt_generator_section_usage_policies_callback',
        'llms_txt_page'
    );

    add_settings_section(
        'slg_section_disallow_rules',
        esc_html__( '3. Set Directory Disallow Rules', 'simple-llms-txt-generator' ),
        'llms_txt_generator_section_disallow_rules_callback',
        'llms_txt_page'
    );

    // User agents.
    add_settings_field('slg_field_gptbot', esc_html__( 'GPTBot (OpenAI)', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_checkbox_callback', 'llms_txt_page', 'slg_section_user_agents', [ 'label_for' => 'slg_field_gptbot', 'option_name' => 'gptbot' ]);
    add_settings_field('slg_field_google_extended', esc_html__( 'Google-Extended (Google AI)', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_checkbox_callback', 'llms_txt_page', 'slg_section_user_agents', [ 'label_for' => 'slg_field_google_extended', 'option_name' => 'google_extended' ]);
    add_settings_field('slg_field_ccbot', esc_html__( 'CCBot (Common Crawl)', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_checkbox_callback', 'llms_txt_page', 'slg_section_user_agents', [ 'label_for' => 'slg_field_ccbot', 'option_name' => 'ccbot' ]);

    // Policies.
    add_settings_field('slg_field_training', esc_html__( 'Training', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_policy_select_callback', 'llms_txt_page', 'slg_section_usage_policies', [ 'label_for' => 'slg_field_training', 'option_name' => 'training' ]);
    add_settings_field('slg_field_summarization', esc_html__( 'Summarization', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_policy_select_callback', 'llms_txt_page', 'slg_section_usage_policies', [ 'label_for' => 'slg_field_summarization', 'option_name' => 'summarization' ]);
    add_settings_field('slg_field_indexing', esc_html__( 'Indexing', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_policy_select_callback', 'llms_txt_page', 'slg_section_usage_policies', [ 'label_for' => 'slg_field_indexing', 'option_name' => 'indexing' ]);
    add_settings_field('slg_field_attribution', esc_html__( 'Attribution', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_policy_select_callback', 'llms_txt_page', 'slg_section_usage_policies', [ 'label_for' => 'slg_field_attribution', 'option_name' => 'attribution' ]);

    // Disallow rules.
    add_settings_field('slg_field_disallow_wp_admin', esc_html__( 'Disallow /wp-admin/', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_checkbox_callback', 'llms_txt_page', 'slg_section_disallow_rules', [ 'label_for' => 'slg_field_disallow_wp_admin', 'option_name' => 'disallow_wp_admin' ]);
    add_settings_field('slg_field_disallow_wp_includes', esc_html__( 'Disallow /wp-includes/', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_checkbox_callback', 'llms_txt_page', 'slg_section_disallow_rules', [ 'label_for' => 'slg_field_disallow_wp_includes', 'option_name' => 'disallow_wp_includes' ]);
    add_settings_field('slg_field_custom_disallow', esc_html__( 'Custom Disallow Rules', 'simple-llms-txt-generator' ), 'llms_txt_generator_field_custom_disallow_callback', 'llms_txt_page', 'slg_section_disallow_rules');
}
add_action( 'admin_init', 'llms_txt_generator_settings_init' );

/**
 * Sanitize the options array.
 */
function llms_txt_generator_options_sanitize( $input ) {
    $options = get_option( 'slg_options', [] );
    $new_input = [];

    $checkboxes = ['gptbot', 'google_extended', 'ccbot', 'disallow_wp_admin', 'disallow_wp_includes'];
    foreach ( $checkboxes as $field ) {
        if ( isset( $input[ $field ] ) ) {
            $new_input[ $field ] = '1';
        }
    }

    $selects = ['training', 'summarization', 'indexing', 'attribution'];
    foreach ( $selects as $field ) {
        if ( isset( $input[ $field ] ) && in_array( $input[ $field ], ['allow', 'disallow'] ) ) {
            $new_input[ $field ] = sanitize_text_field( $input[ $field ] );
        }
    }

    if ( isset( $input['custom_disallow'] ) ) {
        $new_input['custom_disallow'] = sanitize_textarea_field( $input['custom_disallow'] );
    }

    // Merge new input with old options to prevent wiping out data on unchecked fields
    return array_merge( $options, $new_input );
}

/**
 * Section callbacks.
 */
function llms_txt_generator_section_user_agents_callback() {
    echo '<p>' . esc_html__( 'Select the AI crawlers you wish to add rules for. The policies and rules below will apply to all selected agents.', 'simple-llms-txt-generator' ) . '</p>';
}
function llms_txt_generator_section_usage_policies_callback() {
    echo '<p>' . esc_html__( 'Specify how the selected AI agents are permitted to use your site content.', 'simple-llms-txt-generator' ) . '</p>';
}
function llms_txt_generator_section_disallow_rules_callback() {
    echo '<p>' . esc_html__( 'Select standard paths to block, and add any other custom paths you want to disallow.', 'simple-llms-txt-generator' ) . '</p>';
}

/**
 * Field callbacks.
 */
function llms_txt_generator_field_checkbox_callback( $args ) {
    $options = get_option( 'slg_options', [] );
    $option_name = $args['option_name'];
    $checked = isset( $options[$option_name] ) ? 'checked' : '';
    printf(
        '<input type="checkbox" id="%s" name="slg_options[%s]" %s>',
        esc_attr( $args['label_for'] ),
        esc_attr( $option_name ),
        esc_attr( $checked )
    );
}
function llms_txt_generator_field_policy_select_callback( $args ) {
    $options = get_option('slg_options', []);
    $option_name = $args['option_name'];
    $current_value = isset($options[$option_name]) ? $options[$option_name] : 'allow';
    printf(
        '<select id="%s" name="slg_options[%s]">',
        esc_attr( $args['label_for'] ),
        esc_attr( $option_name )
    );
    echo '<option value="allow"' . selected($current_value, 'allow', false) . '>' . esc_html__('Allow', 'simple-llms-txt-generator') . '</option>';
    echo '<option value="disallow"' . selected($current_value, 'disallow', false) . '>' . esc_html__('Disallow', 'simple-llms-txt-generator') . '</option>';
    // translators: %s: The policy name (Training, Summarization, etc.).
    echo '<p class="description">' . sprintf( esc_html__( 'Controls if AI can use content for %s.', 'simple-llms-txt-generator' ), '<strong>' . esc_html( ucfirst($option_name) ) . '</strong>' ) . '</p>';
}
function llms_txt_generator_field_custom_disallow_callback() {
    $options = get_option( 'slg_options', [] );
    $custom_rules = isset( $options['custom_disallow'] ) ? esc_textarea( $options['custom_disallow'] ) : '';
    printf(
        '<textarea name="slg_options[custom_disallow]" rows="5" cols="50" class="large-text" placeholder="%s">%s</textarea>',
        esc_attr( '/private-directory/' . "\n" . '/some-specific-page.html' ),
        $custom_rules
    );
    echo '<p class="description">' . esc_html__( 'Enter one path per line, starting with a slash. e.g., /wp-content/plugins/', 'simple-llms-txt-generator' ) . '</p>';
}

/**
 * Build the file content string from option values.
 */
function llms_txt_generator_build_llms_content( $value ) {
    $file_content = '';
    $agents = [];

    if ( ! empty( $value['gptbot'] ) )          $agents[] = 'GPTBot';
    if ( ! empty( $value['google_extended'] ) ) $agents[] = 'Google-Extended';
    if ( ! empty( $value['ccbot'] ) )           $agents[] = 'CCBot';

    $policy_rules = [];
    foreach ( ['training','summarization','indexing','attribution'] as $policy ) {
        if ( isset( $value[ $policy ] ) ) {
            $policy_rules[] = ucfirst( $policy ) . ': ' . ucfirst( $value[ $policy ] );
        }
    }

    $disallow_rules = [];
    if ( ! empty( $value['disallow_wp_admin'] ) )    $disallow_rules[] = 'Disallow: /wp-admin/';
    if ( ! empty( $value['disallow_wp_includes'] ) ) $disallow_rules[] = 'Disallow: /wp-includes/';
    if ( ! empty( $value['custom_disallow'] ) ) {
        $custom_lines = explode( "\n", str_replace( "\r", "", $value['custom_disallow'] ) );
        foreach ( $custom_lines as $line ) {
            $line = trim( $line );
            if ( $line !== '' ) {
                // sanitize without HTML-encoding
                $disallow_rules[] = 'Disallow: ' . ltrim( sanitize_text_field( $line ) );
            }
        }
    }

    if ( ! empty( $agents ) ) {
        // Single block for all agents
        $file_content .= 'User-agent: ' . implode( ', ', $agents ) . PHP_EOL;

        $rules_to_add = array_merge( $policy_rules, $disallow_rules );
        if ( ! empty( $rules_to_add ) ) {
            $file_content .= implode( PHP_EOL, $rules_to_add ) . PHP_EOL;
        }
    }

    // Ensure file ends with newline
    if ( substr( $file_content, -1 ) !== "\n" ) {
        $file_content .= PHP_EOL;
    }

    return $file_content;
}

/**
 * Write helper (WP_Filesystem with fallback).
 */
function llms_txt_generator_write_file( $path, $contents ) {
    global $wp_filesystem;
    if ( ! $wp_filesystem ) {
        WP_Filesystem();
    }
    if ( $wp_filesystem ) {
        return $wp_filesystem->put_contents( $path, $contents, FS_CHMOD_FILE );
    }
    return @file_put_contents( $path, $contents );
}

/**
 * Option-updated hook (auto-generate when settings change).
 */
function llms_txt_generator_generate_llms_txt_file( $old_value, $value ) {
    $content   = llms_txt_generator_build_llms_content( $value );
    $file_path = trailingslashit( get_home_path() ) . 'llms.txt';
    $written   = llms_txt_generator_write_file( $file_path, $content );

    set_transient( 'slg_admin_notice', $written ? 'success' : 'error', 30 );
}
add_action( 'update_option_slg_options', 'llms_txt_generator_generate_llms_txt_file', 10, 2 );

/**
 * First-time save (option added) — also generate.
 */
add_action( 'added_option', function( $option, $value ) {
    if ( 'slg_options' === $option ) {
        llms_txt_generator_generate_llms_txt_file( null, $value );
    }
}, 10, 2 );

/**
 * Admin notices.
 */
function llms_txt_generator_display_admin_notices() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $notice = get_transient( 'slg_admin_notice' );
    if ( ! $notice ) {
        return;
    }

    $file_url  = home_url( '/llms.txt' );
    $file_path = trailingslashit( get_home_path() ) . 'llms.txt';

    if ( in_array( $notice, [ 'success', 'generated_now_success' ], true ) ) {
        // Try to get filesize if it exists
        $size_text = '';
        if ( file_exists( $file_path ) ) {
            $bytes = filesize( $file_path );
            if ( $bytes !== false ) {
                $size_text = ' (' . size_format( $bytes ) . ')';
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>';
        echo esc_html__( 'Success! The llms.txt file has been generated and uploaded to your site root.', 'simple-llms-txt-generator' ) . esc_html( $size_text ) . ' ';
        // translators: %s: a link to the llms.txt file.
        printf(
            esc_html__( 'You can %s.', 'simple-llms-txt-generator' ),
            sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $file_url ), esc_html__( 'view it here', 'simple-llms-txt-generator' ) )
        );
        echo '</p></div>';
    } elseif ( 'download_ready' === $notice ) {
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'A fresh copy of llms.txt has been prepared for download.', 'simple-llms-txt-generator' ) . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error: The llms.txt file could not be created. Please check write permissions to the WordPress root directory.', 'simple-llms-txt-generator' ) . '</p></div>';
    }

    delete_transient( 'slg_admin_notice' );
}
add_action( 'admin_notices', 'llms_txt_generator_display_admin_notices' );

/**
 * Settings page UI.
 */
function llms_txt_generator_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $generate_action = 'slg_generate_now';
    $download_action = 'slg_download_llms';
    $file_exists     = file_exists( trailingslashit( get_home_path() ) . 'llms.txt' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php echo esc_html__( 'This page lets you create an', 'simple-llms-txt-generator' ); ?> <code>llms.txt</code> <?php echo esc_html__( 'file (like robots.txt) to guide LLM/AI crawlers.', 'simple-llms-txt-generator' ); ?></p>

        <form action="options.php" method="post" style="margin-top:1em;">
            <?php
            settings_fields( 'llms_txt_page' );
            do_settings_sections( 'llms_txt_page' );
            submit_button( esc_html__( 'Save Settings & Generate File', 'simple-llms-txt-generator' ) );
            ?>
        </form>

        <hr style="margin: 24px 0;">

        <h2><?php echo esc_html__( 'Actions', 'simple-llms-txt-generator' ); ?></h2>
        <p><?php echo esc_html__( 'Use these tools to generate or download the current llms.txt anytime:', 'simple-llms-txt-generator' ); ?></p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-right:10px;">
            <?php wp_nonce_field( $generate_action . '_nonce', '_slg_nonce' ); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr( $generate_action ); ?>">
            <?php submit_button( esc_html__( 'Generate Now', 'simple-llms-txt-generator' ), 'primary', 'submit', false ); ?>
        </form>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
            <?php wp_nonce_field( $download_action . '_nonce', '_slg_nonce' ); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr( $download_action ); ?>">
            <?php submit_button( $file_exists ? esc_html__( 'Download Current llms.txt', 'simple-llms-txt-generator' ) : esc_html__( 'Download Preview llms.txt', 'simple-llms-txt-generator' ), 'secondary', 'submit', false ); ?>
        </form>

        <?php if ( $file_exists ) : ?>
            <p style="margin-top:10px;">
                <?php
                // translators: %s: a link to the llms.txt file.
                printf(
                    esc_html__( 'Current file: %s', 'simple-llms-txt-generator' ),
                    sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( home_url( '/llms.txt' ) ), esc_html__( '/llms.txt', 'simple-llms-txt-generator' ) )
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle "Generate Now" (runs even if options didn’t change).
 */
function llms_txt_generator_handle_generate_now() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized.', 'simple-llms-txt-generator' ) );
    }

    $action = 'slg_generate_now';
    check_admin_referer( $action . '_nonce', '_slg_nonce' );

    $options   = get_option( 'slg_options', [] );
    $content   = llms_txt_generator_build_llms_content( $options );
    $file_path = trailingslashit( get_home_path() ) . 'llms.txt';
    $written   = llms_txt_generator_write_file( $file_path, $content );

    set_transient( 'slg_admin_notice', $written ? 'generated_now_success' : 'error', 30 );

    $redirect = add_query_arg( [ 'page' => 'llms_txt_generator' ], admin_url( 'options-general.php' ) );
    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_slg_generate_now', 'llms_txt_generator_handle_generate_now' );

/**
 * Handle "Download llms.txt".
 * If the file exists, download actual; else, serve generated preview from current settings.
 */
function llms_txt_generator_handle_download_llms() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized.', 'simple-llms-txt-generator' ) );
    }

    $action = 'slg_download_llms';
    check_admin_referer( $action . '_nonce', '_slg_nonce' );

    $file_path = trailingslashit( get_home_path() ) . 'llms.txt';
    $filename  = 'llms.txt';
    $contents  = '';

    if ( file_exists( $file_path ) && is_readable( $file_path ) ) {
        $contents = file_get_contents( $file_path );
    } else {
        // Fallback: generate preview based on current options
        $options  = get_option( 'slg_options', [] );
        $contents = llms_txt_generator_build_llms_content( $options );
    }

    // Serve download
    nocache_headers();
    header( 'Content-Description: File Transfer' );
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $contents ) );
    echo $contents;
    exit;
}
add_action( 'admin_post_slg_download_llms', 'llms_txt_generator_handle_download_llms' );