<?php
/*
 * Plugin Name: Feature Post
 * Plugin URI: http://localhost/wordpress
 * Description: feature post with rest API
 * Version: 1.0
 * Author: Ajmal
 * Author URI: http://localhost/wordpress
 */
//
//
/** error log function to check errors */
if (!function_exists('write_log')) {
    function write_log($log)
    {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

/** custom box function */
function wporg_add_custom_box()
{
    $screens = ['post'];
    foreach ($screens as $screen) {
        add_meta_box(
            'wporg_box_id',           // Unique ID
            'Custom Meta Box Title',  // Box title
            'wporg_custom_box_html',  // Content callback, must be of type callable
            $screen                   // Post type
        );
    }
}

add_action('add_meta_boxes', 'wporg_add_custom_box', 10, 1);

/** ad custom radio buttons functions */
function wporg_custom_box_html($post)
{
    $value = get_post_meta($post->ID, 'feature_key', true);
    ?>
    <div class="custom-control custom-radio custom-control-inline">
    <input type="radio" class="custom-control-input" id="feature"
           value="1"<?php if (isset($value['wporg_field']) && $value['wporg_field'] == '1') echo 'checked="checked"'; ?>
           name="feature" <?php selected($value, '0'); ?> >
    <label class="custom-control-label" for="defaultInline2">Save as feature</label>
    <div class="custom-control custom-radio custom-control-inline">
    <input type="radio" class="custom-control-input" id="feature"
           value="0"<?php if (isset($value['feature']) && $value['feature'] == '0') echo 'checked="checked"'; ?>
           name="feature" <?php selected($value, '0'); ?> >
    <label class="custom-control-label" for="defaultInline2">Un feature</label>
    <?php
}

/** save post function */
function wporg_save_postdata($post_id)
{
    if (array_key_exists('feature', $_POST)) {
        update_post_meta(
            $post_id,
            'feature_key',
            $_POST['feature']
        );
    }
}

add_action('save_post', 'wporg_save_postdata', 10, 1);

add_shortcode('post-data', 'get_featured_posts');

/** get featured data */
function get_featured_posts()
{
    $output = '';
    global $wpdb;
    $prefix = $wpdb->prefix;

    $query = "SELECT {$prefix}posts.ID, {$prefix}posts.post_title,{$prefix}posts.post_content, {$prefix}postmeta.meta_key, {$prefix}postmeta.meta_value FROM {$prefix}posts INNER JOIN {$prefix}postmeta ON {$prefix}posts.ID = {$prefix}postmeta.post_id WHERE {$prefix}postmeta.meta_key = 'feature_key' AND {$prefix}postmeta.meta_value = 1 ";
    $result = $wpdb->get_results($query);
    foreach ($result as $key => $value) {
        $output .= '<div>';
        $output .= '<span><a href=' . get_post_permalink($value->ID) . '>' . $value->post_title . '</a></span>';
        $output .= '<span><a href=' . get_post_permalink($value->ID) . '>' . $value->post_content . '</a></span>';
        $output .= '</div>';
        $output .= '<br>';
    }
    return $output;
}

/** Rest API to get featured post */
add_action('rest_api_init', function () {
    register_rest_route('wp/v2', 'get_feature_post_ids', [
        'methods' => ['GET', 'POST'],
        'callback' => 'get_feature_post_ids',
        'permission_callback' => '__return_true'
    ]);

});

/** get featured-post ids function */
function get_feature_post_ids()
{
    global $wpdb;
    $prefix = $wpdb->prefix;
    $sql = "SELECT * FROM {$prefix}users WHERE user_login = 'admin'";
    $user = $wpdb->get_results($sql);
    if ($user) {
        $sql = "SELECT post_id FROM {$prefix}postmeta WHERE meta_key = 'feature_key' AND meta_value = 1";
        $result = $wpdb->get_results($sql);
        /** getting only values */
        foreach ($result as $key => $item) {
            $arr[] = $item->post_id;
        }
        $output = ['feature' => $arr];
        return $output;
    }
}
