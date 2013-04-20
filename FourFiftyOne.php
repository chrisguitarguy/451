<?php
/**
 * 451
 *
 * @category    WordPress
 * @package     451
 * @author      Christopher Davis <http://christopherdavis.me>
 * @copyright   2013 Christopher Davis
 * @license     http://opensource.org/licenses/MIT MIT
 */

class FourFiftyOne
{
    const CODE  = 451;
    const NONCE = '_451_nonce';
    const META  = '_451_status';

    private static $instance = null;

    public static function init()
    {
        add_action('plugins_loaded', array(self::instance(), '_setup'));
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function _setup()
    {
        add_action('add_meta_boxes', array($this, 'addBox'));
        add_action('save_post', array($this, 'save'), 10, 2);
        add_action('template_redirect', array($this, 'statusHeader'));
        add_filter('template_include', array($this, 'hijackTemplate'));
    }

    public function addBox($post_type)
    {
        $type = get_post_type_object($post_type);

        if ($type && apply_filters('allow_451_box', !empty($type->public))) {
            add_meta_box(
                '451',
                __('451', '451'),
                array($this, 'boxCallback'),
                $post_type,
                'side',
                'low'
            );
        }
    }

    public function boxCallback($post)
    {
        wp_nonce_field(self::NONCE . $post->ID, self::NONCE, false);

        echo '<p>';

        printf(
            '<label for"%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="on" %2$s /> %3$s</label>',
            self::META,
            checked('on', self::getmeta($post->ID), false),
            esc_html__('Enable 451', '451')
        );

        echo '</p>';
    }

    public static function save($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (
            !isset($_POST[self::NONCE]) ||
            !wp_verify_nonce($_POST[self::NONCE], self::NONCE . $post_id)
        ) {
            return;
        }

        $type = get_post_type_object($post->post_type);
        if (!current_user_can($type->cap->edit_post, $post_id)) {
            return;
        }

        if (empty($_POST[self::META])) {
            self::deleteMeta($post_id);
        } else {
            self::setMeta($post_id);
        }
    }

    public function statusHeader()
    {
        if (is_singular() && self::is451(get_queried_object_id())) {
            $protocol = $_SERVER["SERVER_PROTOCOL"];
            if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ) {
                $protocol = 'HTTP/1.0';
            }
    
            @header(sprintf("{$protocol} %d Unavailable For Legal Reasons", self::CODE), true, self::CODE);
        }
    }

    public function hijackTemplate($template)
    {
        if (
            is_singular() &&
            self::is451(get_queried_object_id()) &&
            ($tmp = locate_template(array('451.php', '404.php')))
        ) {
            $template = $tmp;
        }

        return $template;
    }

    public static function is451($post_id)
    {
        return apply_filters('451_enabled', 'on' == self::getMeta($post_id), $post_id);
    }

    public static function getMeta($post_id)
    {
        return get_post_meta($post_id, self::META, true);
    }

    public static function setMeta($post_id, $status='on')
    {
        return update_post_meta($post_id, self::META, $status);
    }

    public static function deleteMeta($post_id)
    {
        return delete_post_meta($post_id, self::META);
    }
}
