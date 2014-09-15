<?php
/*
Plugin Name: Rating-Widget: Star Rating System
Plugin URI: http://rating-widget.com/wordpress-plugin/
Description: Create and manage Rating-Widget ratings in WordPress.
Version: 2.1.5
Author: Rating-Widget
Author URI: http://rating-widget.com/wordpress-plugin/
License: GPLv2 or later
Text Domain: ratingwidget
Domain Path: /langs
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('RatingWidgetPlugin')) :

// Load common config.
require_once(dirname(__FILE__) . "/lib/config.common.php");
require_once(WP_RW__PLUGIN_LIB_DIR . "rw-core-functions.php");
require_once(WP_RW__PLUGIN_LIB_DIR . "rw-core-rw-functions.php");
require_once(WP_RW__PLUGIN_LIB_DIR . "rw-core-actions.php");
require_once(WP_RW__PLUGIN_LIB_DIR . "rw-core-admin.php");
require_once(WP_RW__PLUGIN_LIB_DIR . "rw-settings.php");
require_once(WP_RW__PLUGIN_LIB_DIR . "rw-shortcodes.php");
require_once(WP_RW__PLUGIN_DIR . "/lib/logger.php");

/**
* Rating-Widget Plugin Class
* 
* @package Wordpress
* @subpackage Rating-Widget Plugin
* @author Vova Feldman
* @version 1
* @copyright Rating-Widget
*/
class RatingWidgetPlugin
{
    public $admin;
    public $settings;
    
    private $errors;
    private $success;
    static $ratings = array();
    
    var $is_admin;
    var $languages;
    var $languages_short;
    var $_visibilityList;
    var $categories_list;
    var $availability_list;
    var $show_on_excerpts_list;
    var $custom_settings_enabled_list;
    var $custom_settings_list;
    var $_inDashboard = false;
    var $_isRegistered = false;
    var $_inBuddyPress;
    var $_inBBPress;
    
    static $VERSION;
    
    public static $WP_RW__HIDE_RATINGS = false;
    
/* Singleton pattern.
--------------------------------------------------------------------------------------------*/
    private static $INSTANCE;
    public static function Instance()
    {
        if (!isset(self::$INSTANCE))
        {
            self::$INSTANCE = new RatingWidgetPlugin();
        }
        
        return self::$INSTANCE;
    }

/* Plugin setup.
--------------------------------------------------------------------------------------------*/
    private function __construct()
    {
        if (WP_RW__DEBUG)
            $this->InitLogger();

        // Load plugin options.
        $this->LoadDefaultOptions();
        $this->LoadOptions();
        
        // Give 2nd chance to logger after options are loaded.
        if (!RWLogger::IsOn() && $this->GetOption(WP_RW__LOGGER))
            $this->InitLogger();

        // Make sure that matching Rating-Widget account exist.
        $this->Authenticate();

        // If not in admin dashboard and account don't exist, don't continue with plugin init.
        if (!$this->_isRegistered && !is_admin())
            return;

        // Load config after keys are loaded.
        require_once(WP_RW__PLUGIN_DIR . "/lib/config.php");

        $this->LogInitialData();
        
        // Run plugin setup.
        $continue = is_admin() ? 
            $this->SetupOnDashboard() : 
            $this->SetupOnSite();
        
        if (!$continue)
            return;

        if ($this->_isRegistered)
        {
            add_action('init', array(&$this, 'LoadPlan'));
            add_action('init', array(&$this, 'SetupBuddyPress'));
            add_action('init', array(&$this, 'SetupBBPress'));
        }
        
        $this->errors = new WP_Error();
        $this->success = new WP_Error();

        /**
        * IMPORTANT: 
        *   All scripts/styles must be enqueued from these actions, 
        *   otherwise it will mass-up the layout of the admin's dashboard
        *   on RTL WP versions.
        */
        add_action('admin_enqueue_scripts', array(&$this, 'InitScriptsAndStyles'));
        
        require_once(WP_RW__PLUGIN_DIR . "/languages/dir.php");
        $this->languages = $rw_languages;
        $this->languages_short = array_keys($this->languages);

        add_action( 'plugins_loaded', array(&$this, 'rw_load_textdomain'));
    }
    
    function rw_load_textdomain()
    {
        load_plugin_textdomain('ratingwidget', false, dirname(plugin_basename( __FILE__ )) . '/langs');
    }
    
    private function InitLogger()
    {
        // Start logger.
        RWLogger::PowerOn();
        
        if (is_admin())
            add_action('admin_footer', array(&$this, "DumpLog"));
        else
            add_action('wp_footer', array(&$this, "DumpLog"));
    }
    
    protected function LogInitialData()
    {
        if (!RWLogger::IsOn())
            return;

        RWLogger::Log("WP_RW__VERSION", WP_RW__VERSION);
        RWLogger::Log("WP_RW__SITE_PUBLIC_KEY", WP_RW__SITE_PUBLIC_KEY);
        RWLogger::Log('WP_RW__SITE_ID', WP_RW__SITE_ID);
        RWLogger::Log("WP_RW__DOMAIN", WP_RW__DOMAIN);
        RWLogger::Log("WP_RW__CLIENT_ADDR", WP_RW__CLIENT_ADDR);
        RWLogger::Log("WP_RW__PLUGIN_DIR", WP_RW__PLUGIN_DIR);
        RWLogger::Log("WP_RW__PLUGIN_URL", WP_RW__PLUGIN_URL);
        RWLogger::Log("WP_RW__SHOW_PHP_ERRORS", json_encode(WP_RW__SHOW_PHP_ERRORS));
        RWLogger::Log("WP_RW__LOCALHOST_SCRIPTS", json_encode(WP_RW__LOCALHOST_SCRIPTS));
        RWLogger::Log("WP_RW__CACHING_ON", json_encode(WP_RW__CACHING_ON));
        RWLogger::Log("WP_RW__STAGING", json_encode(WP_RW__STAGING));

        // Don't log secure data.
//        RWLogger::Log("WP_RW__SITE_SECRET_KEY", WP_RW__SITE_SECRET_KEY);
//        RWLogger::Log("WP_RW__SERVER_ADDR", WP_RW__SERVER_ADDR);
//        RWLogger::Log("WP_RW__DEBUG", json_encode(WP_RW__DEBUG));
    }
    
    private function SetupOnDashboard()
    {
        RWLogger::LogEnterence("SetupOnDashboard");

        // Init settings.
        $this->settings = new RatingWidgetPlugin_Settings();

        $this->_inDashboard = (isset($_GET['page']) && rw_starts_with($_GET['page'], $this->GetMenuSlug()));

        if (!$this->_isRegistered && $this->_inDashboard && strtolower($_GET['page']) !== $this->GetMenuSlug())
            rw_admin_redirect();
        
        $this->SetupDashboardActions();
        
        return true;
    }
    
    private function SetupOnSite()
    {
        RWLogger::LogEnterence("SetupOnSite");

        if ($this->IsHideOnMobile() && $this->IsMobileDevice())
        {
            // Don't show any ratings.
            self::$WP_RW__HIDE_RATINGS = true;
            
            return false;
        }
        
        $this->SetupSiteActions();
        
        return true;
    }
    
    private function SetupDashboardActions()
    {
        RWLogger::LogEnterence("SetupDashboardActions");

        // Add link to settings page.
        add_filter('plugin_action_links', array(&$this, 'ModifyPluginActionLinks' ), 10, 2);
        add_filter('network_admin_plugin_action_links', array(&$this, 'ModifyPluginActionLinks'), 10, 2);

        // Add activation and de-activation hooks.
        register_activation_hook(WP_RW__PLUGIN_FILE_FULL, 'rw_activated');
        register_deactivation_hook(WP_RW__PLUGIN_FILE_FULL, 'rw_deactivated');

        add_action('admin_head', array(&$this, "rw_admin_menu_icon_css"));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_menu', array(&$this, 'AddPostMetaBox')); // Metabox for posts/pages
        add_action('save_post', array(&$this, 'SavePostData'));
        add_action('updated_post_meta', array(&$this, 'PurgePostFeaturedImageTransient'), 10, 4);
        
        if ($this->_inDashboard)
        {
            add_action('init', array(&$this, 'RedirectOnLink'));
            
            if ($this->GetOption(WP_RW__DB_OPTION_TRACKING))
                add_action('admin_head', array(&$this, "GoogleAnalytics"));
            
            // wp_footer call validation.
            // add_action('init', array(&$this, 'test_footer_init'));
        }
    }
    
    private function SetupSiteActions()
    {
        RWLogger::LogEnterence("SetupSiteActions");

        // If not registered, don't add any actions to site.
        if (!$this->_isRegistered)
            return;
        
        // Posts / Pages / Comments.
        add_action("loop_start", array(&$this, "rw_before_loop_start"));
        
        // Register shortcode.
        add_action('init', array(&$this, 'RegisterShortcodes'));

        // wp_footer call validation.
        // add_action('init', array(&$this, 'test_footer_init'));

        // Rating-Widget main javascript load.
        add_action('wp_footer', array(&$this, "rw_attach_rating_js"));
    }
    
    private function IsHideOnMobile()
    {
        RWLogger::LogEnterence("IsHideOnMobile");

        $rw_show_on_mobile = $this->GetOption(WP_RW__SHOW_ON_MOBILE);
        
        if (RWLogger::IsOn())
            RWLogger::Log("WP_RW__SHOW_ON_MOBILE", $rw_show_on_mobile);
        
        return (!$rw_show_on_mobile);
    }
    
    private function IsMobileDevice()
    {
        RWLogger::LogEnterence("IsMobileDevice");

        require_once(WP_RW__PLUGIN_DIR . "/vendors/class.mobile.detect.php");
        $detect = new Mobile_Detect();
        
        $is_mobile = $detect->isMobile();
        
        if (RWLogger::IsOn()){ RWLogger::Log("WP_RW__IS_MOBILE_CLIENT", ($is_mobile ? "true" : "false")); }        
        
        return $is_mobile;
    }
    
    function RedirectOnLink()
    {
        $page = strtolower(rw_request_get('page', ''));
        
        if ($page === $this->GetMenuSlug('upgrade'))
            rw_site_redirect($this->GetUpgradeUrl()); 
        else if ($page === $this->GetMenuSlug('faq'))
            rw_site_redirect('support/wordpress/#platform');
        else if ($page === $this->GetMenuSlug('help'))
            rw_redirect('http://wordpress.org/support/plugin/rating-widget');
    }

/* Authentication.
--------------------------------------------------------------------------------------------*/
    /**
    * Authenticate user account.
    * 
    */
    private function Authenticate()
    {
        RWLogger::LogEnterence("Authenticate");

        // Load user key.
        $this->LoadUserKey();
        
        $this->_isRegistered = (false !== WP_RW__SITE_PUBLIC_KEY);        
    }
    
    function SecretKeyUpdateConfirmNotice()
    {
        $this->Notice('You have successfully updated your Secret Key.', 'update-nag success');
    }

    function UpdateSecret()
    {
        RWLogger::LogEnterence("UpdateSecret");

        if (!rw_request_is_action('update_secret'))
            return;

        check_admin_referer('update_secret');

        $new_secret = rw_request_get('rw_secret', '');
        
        $this->SetOption(WP_RW__DB_OPTION_SITE_SECRET_KEY, $new_secret);
        $this->StoreOptions();
        
        add_action('all_admin_notices', array(&$this, 'SecretKeyUpdateConfirmNotice'));
        
        rw_redirect('#');
    }
    
    function LoadPlan()
    {
        RWLogger::LogEnterence("LoadPlan");

        eval(base64_decode('DQogICAgICAgICRjdXJyZW50X3NpdGVfcGxhbiA9ICR0aGlzLT5HZXRPcHRpb24oV1BfUldfX0RCX09QVElPTl9TSVRFX1BMQU4pOw0KICAgICAgICANCiAgICAgICAgJHNpdGVfcGxhbiA9ICRjdXJyZW50X3NpdGVfcGxhbjsNCiAgICAgICAgDQogICAgICAgICR1cGRhdGUgPSBmYWxzZTsNCg0KICAgICAgICBpZiAoIWlzX3N0cmluZyhXUF9SV19fU0lURV9TRUNSRVRfS0VZKSkNCiAgICAgICAgew0KICAgICAgICAgICAgaWYgKCdmcmVlJyAhPT0gJHNpdGVfcGxhbikNCiAgICAgICAgICAgIHsNCiAgICAgICAgICAgICAgICAkc2l0ZV9wbGFuID0gJ2ZyZWUnOw0KICAgICAgICAgICAgICAgICR1cGRhdGUgPSB0cnVlOw0KICAgICAgICAgICAgfQ0KICAgICAgICB9DQogICAgICAgIGVsc2UNCiAgICAgICAgew0KICAgICAgICAgICAgJHNpdGVfcGxhbl91cGRhdGUgPSAkdGhpcy0+R2V0T3B0aW9uKFdQX1JXX19EQl9PUFRJT05fU0lURV9QTEFOX1VQREFURSwgZmFsc2UsIDApOw0KICAgICAgICAgICAgLy8gQ2hlY2sgaWYgdXNlciBhc2tlZCB0byBzeW5jIGxpY2Vuc2UuDQogICAgICAgICAgICBpZiAocndfcmVxdWVzdF9pc19hY3Rpb24oJ3N5bmNfbGljZW5zZScpKQ0KICAgICAgICAgICAgew0KICAgICAgICAgICAgICAgIGNoZWNrX2FkbWluX3JlZmVyZXIoJ3N5bmNfbGljZW5zZScpOw0KICAgICAgICAgICAgICAgICRzaXRlX3BsYW5fdXBkYXRlID0gMDsNCiAgICAgICAgICAgIH0NCiAgICAgICAgICAgIA0KICAgICAgICAgICAgLy8gVXBkYXRlIHBsYW4gb25jZSBpbiBldmVyeSAyNCBob3Vycy4NCiAgICAgICAgICAgIGlmIChmYWxzZSA9PT0gJGN1cnJlbnRfc2l0ZV9wbGFuIHx8ICRzaXRlX3BsYW5fdXBkYXRlIDwgKHRpbWUoKSAtIFdQX1JXX19USU1FXzI0X0hPVVJTX0lOX1NFQykpDQogICAgICAgICAgICB7DQogICAgICAgICAgICAgICAgLy8gR2V0IHBsYW4gZnJvbSByZW1vdGUgc2VydmVyIG9uY2UgYSBkYXkuDQogICAgICAgICAgICAgICAgdHJ5DQogICAgICAgICAgICAgICAgew0KICAgICAgICAgICAgICAgICAgICAkc2l0ZSA9IHJ3YXBpKCktPkFwaSgnP2ZpZWxkcz1pZCxwbGFuJyk7DQogICAgICAgICAgICAgICAgfQ0KICAgICAgICAgICAgICAgIGNhdGNoIChcRXhjZXB0aW9uICRlKQ0KICAgICAgICAgICAgICAgIHsNCiAgICAgICAgICAgICAgICAgICAgJHNpdGUgPSBmYWxzZTsNCiAgICAgICAgICAgICAgICB9DQogICAgICAgICAgICAgICAgDQogICAgICAgICAgICAgICAgaWYgKGlzX29iamVjdCgkc2l0ZSkgJiYgaXNzZXQoJHNpdGUtPmlkKSAmJiAkc2l0ZS0+aWQgPT0gV1BfUldfX1NJVEVfSUQpDQogICAgICAgICAgICAgICAgew0KICAgICAgICAgICAgICAgICAgICAkc2l0ZV9wbGFuID0gJHNpdGUtPnBsYW47DQogICAgICAgICAgICAgICAgICAgICR1cGRhdGUgPSB0cnVlOw0KICAgICAgICAgICAgICAgIH0NCiAgICAgICAgICAgIH0NCiAgICAgICAgfQ0KICAgICAgICAgICAgICAgIA0KICAgICAgICBkZWZpbmUoJ1dQX1JXX19TSVRFX1BMQU4nLCAkc2l0ZV9wbGFuKTsNCiAgICAgICAgDQogICAgICAgIGlmICgkdXBkYXRlKQ0KICAgICAgICB7DQogICAgICAgICAgICAkdGhpcy0+U2V0T3B0aW9uKFdQX1JXX19EQl9PUFRJT05fU0lURV9QTEFOLCAkc2l0ZV9wbGFuKTsNCiAgICAgICAgICAgICR0aGlzLT5TZXRPcHRpb24oV1BfUldfX0RCX09QVElPTl9TSVRFX1BMQU5fVVBEQVRFLCB0aW1lKCkpOw0KICAgICAgICAgICAgJHRoaXMtPlN0b3JlT3B0aW9ucygpOw0KICAgICAgICAgICAgDQovLyAgICAgICAgICAgIGlmICgkY3VycmVudF9zaXRlX3BsYW4gIT09ICRzaXRlLT5wbGFuKQ0KLy8gICAgICAgICAgICB7DQogICAgICAgICAgICAgICAgJHRoaXMtPkNsZWFyVHJhbnNpZW50cygpOw0KLy8gICAgICAgICAgICB9DQogICAgICAgIH0NCiAgICAgICAg'));
    }
    
    public function ClearTransients()
    {
        global $wpdb;

        // Clear all rw transients.
        $wpdb->query( 
            "DELETE FROM 
                $wpdb->options
             WHERE
                option_name LIKE '_transient_rw%'"
        );
    }
    
    /**
    * Load user's Rating-Widget account details.
    * 
    */
    private function LoadUserKey()
    {
        RWLogger::LogEnterence("LoadUserKey");

        eval(base64_decode('DQogICAgICAgICRzaXRlX3B1YmxpY19rZXkgPSAkdGhpcy0+R2V0T3B0aW9uKFdQX1JXX19EQl9PUFRJT05fU0lURV9QVUJMSUNfS0VZKTsNCiAgICAgICAgJHNpdGVfaWQgPSAkdGhpcy0+R2V0T3B0aW9uKFdQX1JXX19EQl9PUFRJT05fU0lURV9JRCk7DQogICAgICAgICRvd25lcl9pZCA9ICR0aGlzLT5HZXRPcHRpb24oV1BfUldfX0RCX09QVElPTl9PV05FUl9JRCk7DQogICAgICAgICRvd25lcl9lbWFpbCA9ICR0aGlzLT5HZXRPcHRpb24oV1BfUldfX0RCX09QVElPTl9PV05FUl9FTUFJTCk7DQoNCiAgICAgICAgJHVwZGF0ZSA9IGZhbHNlOw0KICAgICAgICANCiAgICAgICAgaWYgKCFkZWZpbmVkKCdXUF9SV19fU0lURV9QVUJMSUNfS0VZJykpDQogICAgICAgIHsNCiAgICAgICAgICAgIGRlZmluZSgnV1BfUldfX1NJVEVfUFVCTElDX0tFWScsICRzaXRlX3B1YmxpY19rZXkpOw0KICAgICAgICAgICAgZGVmaW5lKCdXUF9SV19fU0lURV9JRCcsICRzaXRlX2lkKTsNCiAgICAgICAgICAgIGRlZmluZSgnV1BfUldfX09XTkVSX0lEJywgJG93bmVyX2lkKTsNCiAgICAgICAgICAgIGRlZmluZSgnV1BfUldfX09XTkVSX0VNQUlMJywgJG93bmVyX2VtYWlsKTsNCiAgICAgICAgfQ0KICAgICAgICBlbHNlDQogICAgICAgIHsNCiAgICAgICAgICAgIGlmIChpc19zdHJpbmcoV1BfUldfX1NJVEVfUFVCTElDX0tFWSkgJiYgV1BfUldfX1NJVEVfUFVCTElDX0tFWSAhPT0gJHNpdGVfcHVibGljX2tleSkNCiAgICAgICAgICAgIHsNCiAgICAgICAgICAgICAgICAvLyBPdmVycmlkZSB1c2VyIGtleS4NCiAgICAgICAgICAgICAgICAkdGhpcy0+U2V0T3B0aW9uKFdQX1JXX19EQl9PUFRJT05fU0lURV9QVUJMSUNfS0VZLCBXUF9SV19fU0lURV9QVUJMSUNfS0VZKTsNCiAgICAgICAgICAgICAgICAkdGhpcy0+U2V0T3B0aW9uKFdQX1JXX19EQl9PUFRJT05fU0lURV9JRCwgV1BfUldfX1NJVEVfSUQpOw0KICAgICAgICAgICAgICAgIGlmIChkZWZpbmVkKCdXUF9SV19fT1dORVJfSUQnKSkNCiAgICAgICAgICAgICAgICAgICAgJHRoaXMtPlNldE9wdGlvbihXUF9SV19fREJfT1BUSU9OX09XTkVSX0lELCBXUF9SV19fT1dORVJfSUQpOw0KICAgICAgICAgICAgICAgIGlmIChkZWZpbmVkKCdXUF9SV19fT1dORVJfRU1BSUwnKSkNCiAgICAgICAgICAgICAgICAgICAgJHRoaXMtPlNldE9wdGlvbihXUF9SV19fREJfT1BUSU9OX09XTkVSX0VNQUlMLCBXUF9SV19fT1dORVJfRU1BSUwpOw0KICAgICAgICAgICAgICAgICAgICANCiAgICAgICAgICAgICAgICAkdXBkYXRlID0gdHJ1ZTsNCiAgICAgICAgICAgIH0NCiAgICAgICAgfQ0KDQogICAgICAgICRzZWNyZXRfa2V5ID0gJHRoaXMtPkdldE9wdGlvbihXUF9SV19fREJfT1BUSU9OX1NJVEVfU0VDUkVUX0tFWSk7DQoNCiAgICAgICAgaWYgKCFkZWZpbmVkKCdXUF9SV19fU0lURV9TRUNSRVRfS0VZJykpDQogICAgICAgIHsNCiAgICAgICAgICAgIGRlZmluZSgnV1BfUldfX1NJVEVfU0VDUkVUX0tFWScsICRzZWNyZXRfa2V5KTsNCiAgICAgICAgfQ0KICAgICAgICBlbHNlDQogICAgICAgIHsNCiAgICAgICAgICAgIGlmIChpc19zdHJpbmcoV1BfUldfX1NJVEVfU0VDUkVUX0tFWSkgJiYgV1BfUldfX1NJVEVfU0VDUkVUX0tFWSAhPT0gJHNlY3JldF9rZXkpDQogICAgICAgICAgICB7DQogICAgICAgICAgICAgICAgLy8gT3ZlcnJpZGUgdXNlciBrZXkuDQogICAgICAgICAgICAgICAgJHRoaXMtPlNldE9wdGlvbihXUF9SV19fREJfT1BUSU9OX1NJVEVfU0VDUkVUX0tFWSwgV1BfUldfX1NJVEVfU0VDUkVUX0tFWSk7DQogICAgICAgICAgICAgICAgDQogICAgICAgICAgICAgICAgJHVwZGF0ZSA9IHRydWU7DQogICAgICAgICAgICB9DQogICAgICAgIH0NCiAgICAgICAgICAgIA0KICAgICAgICBpZiAoJHVwZGF0ZSkNCiAgICAgICAgICAgICR0aGlzLT5TdG9yZU9wdGlvbnMoKTsNCiAgICAgICAg'));
    }
    
/* IDs transformations.
--------------------------------------------------------------------------------------------*/
    /* Private
    -------------------------------------------------*/
    private static function Urid2Id($pUrid, $pSubLength = 1, $pSubValue = 1)
    {
        return round((double)substr($pUrid, 0, strlen($pUrid) - $pSubLength) - $pSubValue);
    }

    function _getPostRatingGuid($id = false)
    {
        if (false === $id){ $id = get_the_ID(); }
        $urid = ($id + 1) . "0";
        
        if (RWLogger::IsOn()){
            RWLogger::Log("post-id", $id);
            RWLogger::Log("post-urid", $urid);
        }
        
        return $urid;
    }
    public static function Urid2PostId($pUrid)
    {
        return self::Urid2Id($pUrid);
    }
    
    private function _getCommentRatingGuid($id = false)
    {
        if (false === $id){ $id = get_comment_ID(); }
        $urid = ($id + 1) . "1";

        if (RWLogger::IsOn()){
            RWLogger::Log("comment-id", $id);
            RWLogger::Log("comment-urid", $urid);
        }
        
        return $urid;
    }
    public static function Urid2CommentId($pUrid)
    {
        return self::Urid2Id($pUrid);
    }

    private function _getActivityRatingGuid($id = false)
    {
        if (false === $id){ $id = bp_get_activity_id(); }
        $urid = ($id + 1) . "2";

        if (RWLogger::IsOn()){
            RWLogger::Log("activity-id", $id);
            RWLogger::Log("activity-urid", $urid);
        }
        
        return $urid;
    }

    public static function Urid2ActivityId($pUrid)
    {
        return self::Urid2Id($pUrid);
    }

    private function _getForumPostRatingGuid($id = false)
    {
        if (false === $id){ $id = bp_get_the_topic_post_id(); }
        $urid = ($id + 1) . "3";

        if (RWLogger::IsOn()){
            RWLogger::Log("forum-post-id", $id);
            RWLogger::Log("forum-post-urid", $urid);
        }
        
        return $urid;
    }

    public static function Urid2ForumPostId($pUrid)
    {
        return self::Urid2Id($pUrid);
    }
    
    function _getUserRatingGuid($id = false, $secondery_id = WP_RW__USER_SECONDERY_ID)
    {
        if (false === $id)
            $id = bp_displayed_user_id();
        
        $len = strlen($secondery_id);
        $secondery_id = ($len == 0) ? WP_RW__USER_SECONDERY_ID : (($len == 1) ? "0" . $secondery_id : substr($secondery_id, 0, 2));
        $urid = ($id + 1) . $secondery_id . "4";

        if (RWLogger::IsOn()){
            RWLogger::Log("user-id", $id);
            RWLogger::Log("user-secondery-id", $secondery_id);
            RWLogger::Log("user-urid", $urid);
        }
        
        return $urid;
    }
    
    public static function Urid2UserId($pUrid)
    {
        return self::Urid2Id($pUrid, 3);
    }
    
/* Plugin Options.
--------------------------------------------------------------------------------------------*/
    
    protected $_OPTIONS_DEFAULTS;
    protected function LoadDefaultOptions()
    {
        RWLogger::LogEnterence("LoadDefaultOptions");

        if (isset($this->_OPTIONS_DEFAULTS))
            return;
        
        $star = (object)array(
            'type' => 'star',
            'size' => 'medium',
            'theme' => 'star_flat_yellow'
        );
        
        $star_small = (object)array(
            'type' => 'star',
            'size' => 'small',
            'theme' => 'star_flat_yellow'
        );
        
        $readonly_star = clone $star;
        $readonly_star->readOnly = true;
        
        $thumbs = (object)array(
            'type' => 'nero',
            'theme' => 'thumbs_1',
        );
        
        $bp_thumbs = (object)array(
            'type' => 'nero',
            'theme' => 'thumbs_bp1',
        );

        $bp_like = (object)array(
            'type' => 'nero',
            'theme' => 'thumbs_bp1',
            'advanced' => array(
                'nero' => array(
                    'showDislike' => false,
                )
            )
        );

        $small_bp_like = clone $bp_like;
        $small_bp_like->size = 'small';
        
        $readonly_bp_thumbs = clone $bp_thumbs;
        $readonly_bp_thumbs->readOnly = true;
        
        $top_left = (object)array('ver' => 'top', 'hor' => 'left');
        $bottom_left = (object)array('ver' => 'bottom', 'hor' => 'left');

        $this->_OPTIONS_DEFAULTS = array(
            WP_RW__DB_OPTION_SITE_PUBLIC_KEY => false,
            WP_RW__DB_OPTION_SITE_ID => false,
            WP_RW__DB_OPTION_SITE_SECRET_KEY => false,
            
            WP_RW__LOGGER => false,
            WP_RW__DB_OPTION_TRACKING => true,
            WP_RW__IS_ACCUMULATED_USER_RATING => true,
            
            WP_RW__IDENTIFY_BY => 'laccount',
            WP_RW__FLASH_DEPENDENCY => true,
            WP_RW__SHOW_ON_MOBILE => true,
            
            WP_RW__SHOW_ON_ARCHIVE => true,
            WP_RW__SHOW_ON_CATEGORY => true,
            WP_RW__SHOW_ON_SEARCH => true,
            WP_RW__SHOW_ON_EXCERPT => true,
            
            
            WP_RW__FRONT_POSTS_ALIGN => $top_left,
            WP_RW__FRONT_POSTS_OPTIONS => $star,
            
            WP_RW__BLOG_POSTS_ALIGN => $bottom_left,
            WP_RW__BLOG_POSTS_OPTIONS => $star,
            
            WP_RW__COMMENTS_ALIGN => $bottom_left,
            WP_RW__COMMENTS_OPTIONS => $thumbs,
            
            WP_RW__PAGES_ALIGN => $bottom_left,
            WP_RW__PAGES_OPTIONS => $star,

            // BuddyPress
                WP_RW__ACTIVITY_BLOG_POSTS_ALIGN => $bottom_left,
                WP_RW__ACTIVITY_BLOG_POSTS_OPTIONS => $star_small,

                WP_RW__ACTIVITY_BLOG_COMMENTS_ALIGN => $bottom_left,
                WP_RW__ACTIVITY_BLOG_COMMENTS_OPTIONS => $bp_thumbs,

                WP_RW__ACTIVITY_UPDATES_ALIGN => $bottom_left,
                WP_RW__ACTIVITY_UPDATES_OPTIONS => $small_bp_like,

                WP_RW__ACTIVITY_COMMENTS_ALIGN => $bottom_left,
                WP_RW__ACTIVITY_COMMENTS_OPTIONS => $small_bp_like,
            
            // bbPress
                /*WP_RW__FORUM_TOPICS_ALIGN => $bottom_left,
                WP_RW__FORUM_TOPICS_OPTIONS => '{"type": "nero", "theme": "thumbs_bp1", "advanced": {"css": {"container": "background: #F4F4F4; padding: 4px 8px 1px 8px; margin-bottom: 2px; border-right: 1px solid #DDD; border-bottom: 1px solid #DDD; border-radius: 4px; -moz-border-radius: 4px; -webkit-border-radius: 4px;"}}}',*/
                WP_RW__FORUM_POSTS_ALIGN => $bottom_left,
                WP_RW__FORUM_POSTS_OPTIONS => $bp_thumbs,
                
                /*WP_RW__ACTIVITY_FORUM_TOPICS_ALIGN => $bottom_left,
                WP_RW__ACTIVITY_FORUM_TOPICS_OPTIONS => '{"type": "nero", "theme": "thumbs_bp1", "advanced": {"css": {"container": "background: #F4F4F4; padding: 4px 8px 1px 8px; margin-bottom: 2px; border-right: 1px solid #DDD; border-bottom: 1px solid #DDD; border-radius: 4px; -moz-border-radius: 4px; -webkit-border-radius: 4px;"}}}',*/

                WP_RW__ACTIVITY_FORUM_POSTS_ALIGN => $bottom_left,
                WP_RW__ACTIVITY_FORUM_POSTS_OPTIONS => $bp_thumbs,
            // User
                WP_RW__USERS_ALIGN => $bottom_left,
                WP_RW__USERS_OPTIONS => $star_small,
                // Posts
                WP_RW__USERS_POSTS_ALIGN => $bottom_left,
                WP_RW__USERS_POSTS_OPTIONS => $readonly_star,
                // Pages
                WP_RW__USERS_PAGES_ALIGN => $bottom_left,
                WP_RW__USERS_PAGES_OPTIONS => $readonly_star,
                // Comments
                WP_RW__USERS_COMMENTS_ALIGN => $bottom_left,
                WP_RW__USERS_COMMENTS_OPTIONS => $readonly_star,
                // Activity-Updates
                WP_RW__USERS_ACTIVITY_UPDATES_ALIGN => $bottom_left,
                WP_RW__USERS_ACTIVITY_UPDATES_OPTIONS => $readonly_star,
                // Avtivity-Comments
                WP_RW__USERS_ACTIVITY_COMMENTS_ALIGN => $bottom_left,
                WP_RW__USERS_ACTIVITY_COMMENTS_OPTIONS => $readonly_bp_thumbs,
                // Forum-Posts
                WP_RW__USERS_FORUM_POSTS_ALIGN => $bottom_left,
                WP_RW__USERS_FORUM_POSTS_OPTIONS => $readonly_bp_thumbs,
            
            WP_RW__VISIBILITY_SETTINGS => new stdClass(),
            // By default, disable all activity ratings for un-logged users.
            WP_RW__AVAILABILITY_SETTINGS => (object)array(
                "activity-update" => 1,
                "activity-comment" => 1,
                "forum-post" => 1,
                "forum-reply" => 1,
                "new-forum-post" => 1,
                "user" => 1,
                "user-post" => 1,
                "user-comment" => 1,
                "user-page" => 1,
                "user-activity-update" => 1,
                "user-activity-comment" => 1,
                "user-forum-post" => 1
            ),
            WP_RW__CATEGORIES_AVAILABILITY_SETTINGS => new stdClass(),
            
            WP_RW__CUSTOM_SETTINGS_ENABLED => new stdClass(),
            WP_RW__CUSTOM_SETTINGS => new stdClass(),
        );

        RWLogger::LogDeparture("LoadDefaultOptions");
    }
    
    private $_OPTIONS_CACHE;

    function MigrateOptions()
    {
        RWLogger::LogEnterence("MigrateOptions");

        $this->ClearOptions();
        
        $site_public_key = get_option(WP_RW__DB_OPTION_SITE_PUBLIC_KEY);

        // Only migrate if there's a public key,
        // otherwise new user without options.
        if (!is_string($site_public_key))
            return;
        
        $options = array_keys($this->_OPTIONS_DEFAULTS);
        foreach ($options as $o)
        {
            $v = get_option($o);
            
            if (false !== $v)
            {
                if (0 === strpos($v, '{'))
                    $this->_OPTIONS_CACHE->{$o} = json_decode($v);
                else if ('true' == $v)
                    $this->_OPTIONS_CACHE->{$o} = true;
                else if ('false' == $v)
                    $this->_OPTIONS_CACHE->{$o} = false;
                else
                    $this->_OPTIONS_CACHE->{$o} = $v;
            }
        }
        
        // Save to new unified options record.
        $this->StoreOptions();
        
        // Delete old options.
//        foreach ($this->_OPTIONS_CACHE as $o => $v)
//            delete_option($o);

        RWLogger::LogDeparture("MigrateOptions");
    }
    
    function LoadOptions($pFlush = false)
    {
        RWLogger::LogEnterence("LoadOptions");

        if ($pFlush || !isset($this->_OPTIONS_CACHE))
        {
            $this->_OPTIONS_CACHE = wp_cache_get(WP_RW__OPTIONS, WP_RW__ID);
            
            if (is_array($this->_OPTIONS_CACHE))
                $this->ClearOptions();
            
            $cached = true;
            if (false === $this->_OPTIONS_CACHE)
            {
                $this->_OPTIONS_CACHE = get_option(WP_RW__OPTIONS);
                
                if (false !== $this->_OPTIONS_CACHE)
                    $this->_OPTIONS_CACHE = json_decode($this->_OPTIONS_CACHE);
                
                if (is_array($this->_OPTIONS_CACHE))
                    $this->ClearOptions();
                    
                $cached = false;
            }
            
            // Not cached and option doesn't exist
            // in the DB.
            if (false === $this->_OPTIONS_CACHE)
                $this->MigrateOptions();
            
            if (!$cached)
                // Set non encoded cache.
                wp_cache_set(WP_RW__OPTIONS, $this->_OPTIONS_CACHE, WP_RW__ID);
        }

        RWLogger::LogDeparture("LoadOptions");
    }
    
    function ClearOptions()
    {
        $this->_OPTIONS_CACHE = new stdClass();
    }
    
    function GetOption($pOption, $pFlush = false, $pDefault = null)
    {
        if (null === $pDefault)
            $pDefault = isset($this->_OPTIONS_DEFAULTS[$pOption]) ? $this->_OPTIONS_DEFAULTS[$pOption] : false;
        
        return isset($this->_OPTIONS_CACHE->{$pOption}) ? $this->_OPTIONS_CACHE->{$pOption} : $pDefault;
    }
    
    function UnsetOption($pOption)
    {
        if (!isset($this->_OPTIONS_CACHE->{$pOption}))
            return;
        
        unset($this->_OPTIONS_CACHE->{$pOption});
    }
    
    function SetOption($pOption, $pValue)
    {
        // Update cache.
        $this->_OPTIONS_CACHE->{$pOption} = $pValue;
    }
    
    function StoreOptions()
    {
        RWLogger::LogEnterence("StoreOptions");
        
        // Update DB.
        update_option(WP_RW__OPTIONS, json_encode($this->_OPTIONS_CACHE));
        // Update cache.
        wp_cache_set(WP_RW__OPTIONS, $this->_OPTIONS_CACHE, WP_RW__ID);
    }

/* API.
--------------------------------------------------------------------------------------------*/
    function GenerateToken($pTimestamp, $pServerCall = false)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("GenerateToken", $params, true); }

        $ip = (!$pServerCall) ? WP_RW__CLIENT_ADDR : WP_RW__SERVER_ADDR;

        if ($pServerCall)
        {
            if (RWLogger::IsOn()){ 
                RWLogger::Log("ServerToken", "ServerToken");
                RWLogger::Log("ServerIP", $ip);
            }
        }
        else
        {
            if (RWLogger::IsOn()){
                RWLogger::Log("ClientToken", "ClientToken"); 
                RWLogger::Log("ClientIP", $ip);
            }
        }
        
        $token = md5($pTimestamp . WP_RW__SITE_SECRET_KEY);
        
        if (RWLogger::IsOn()){ RWLogger::Log("TOKEN", $token); }
        
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogDeparture("GenerateToken", $token); }
        
        return $token;
    }

    function AddToken(&$pData, $pServerCall = false)
    {
        RWLogger::LogEnterence("AddToken");
        
        $timestamp = time();
        $token = $this->GenerateToken($timestamp, $pServerCall);
        $pData["timestamp"] = $timestamp;
        $pData["token"] = $token;
        
        return $pData;
    }
    
    function ApiCall($pPath, $pMethod = 'GET', $pParams = array(), $pExpiration = false)
    {
        RWLogger::LogEnterence("ApiCall");
        
        if (false === WP_RW__CACHING_ON)
            // No caching on debug mode.
            $pExpiration = false;

        $transient = '';
        $cached = false;
        $result = false;
        if (false !== $pExpiration)
        {
            $transient = 'rw_' . md5($pMethod . $pPath . var_export($pParams, true));
            
            // Try to get cached item.
            $cached = get_transient($transient);
            
            if (RWLogger::IsOn())
            {
                RWLogger::Log("ApiCall", "TRANSIENT_KEY - " . $transient);
                RWLogger::Log("ApiCall", "TRANSIENT_VAL - " . $cached);
            }

            // If found returned cached value.
            if (false !== $cached)
            {
                if (RWLogger::IsOn())
                    RWLogger::Log('ApiCall', 'IS_CACHED: TRUE');
                    
                $result = $cached;
            }
        }

        if (false === $cached)
        {
            $result = rwapi()->Api($pPath, $pMethod, $pParams);

            if (RWLogger::IsOn())
                RWLogger::Log("ApiCall", 'Result: ' . var_export($result, true));

            if (false !== $pExpiration)
                set_transient($transient, $result, $pExpiration);
        }
        
        if (isset($result->error))
        {
            if (RWLogger::IsOn())
                RWLogger::Log("ApiCall", 'API Error: ' . var_export($result, true));
                
            if ($this->_inDashboard)
                $this->errors->add('rw_api_error', 'Unexpected RatingWidget API error: ' . $result->message);
        }
        
        return $result;
    }
    
    function RemoteCall($pPage, &$pData, $pExpiration = false)
    {
        if (RWLogger::IsOn())
        { 
            $params = func_get_args(); RWLogger::LogEnterence("RemoteCall", $params, true);
            RWLogger::Log("RemoteCall", 'Address: ' . WP_RW__ADDRESS . "/{$pPage}");
        }
        
        if (false === WP_RW__CACHING_ON)
            // No caching on debug mode.
            $pExpiration = false;

        $cacheKey = '';
        if (false !== $pExpiration)
        {
            // Calc cache index key.
            $cacheKey = 'rw_' . md5(var_export($pData, true));
            
            // Try to get cached item.
            $value = get_transient($cacheKey);
            
            if (RWLogger::IsOn())
            {
                RWLogger::Log("RemoteCall", "TRANSIENT_KEY - " . $cacheKey);
                RWLogger::Log("RemoteCall", "TRANSIENT_VAL - " . $value);
            }

            // If found returned cached value.
            if (false !== $value)
            {
                if (RWLogger::IsOn())
                    RWLogger::Log('RemoteCall', 'IS_CACHED: TRUE');
                
                return $value;
            }
        }

        if ($this->_eccbc87e4b5ce2fe28308fd9f2a7baf3())
        {
            if (RWLogger::IsOn())
                RWLogger::Log("RemoteCall", "SECURE");
            
            $this->AddToken($pData, true);
        }

        if (RWLogger::IsOn())
        {
            RWLogger::Log('REMOTE_CALL_DATA', 'IS_CACHED: FALSE');
            RWLogger::Log("RemoteCall", 'REMOTE_CALL_DATA: ' . var_export($pData, true));
            RWLogger::Log("RemoteCall", 'Query: "' . WP_RW__ADDRESS . "/{$pPage}?" . http_build_query($pData) . '"');
        }
            
        if (RWLogger::IsOn())
            RWLogger::Log("wp_remote_post", "exist");
        
        $rw_ret_obj = wp_remote_post(WP_RW__ADDRESS . "/{$pPage}", array('body' => $pData));
        
        if (is_wp_error($rw_ret_obj))
        {
            $this->errors = $rw_ret_obj;
            
            if (RWLogger::IsOn()){ RWLogger::Log("ret_object", var_export($rw_ret_obj, true)); }
            
            return false;
        }
        
        $rw_ret_obj = wp_remote_retrieve_body($rw_ret_obj);
        
        if (RWLogger::IsOn())
            RWLogger::Log("ret_object", var_export($rw_ret_obj, true));

        if (false !== $pExpiration && !empty($cacheKey))
            set_transient($cacheKey, $rw_ret_obj, $pExpiration);
        
        return $rw_ret_obj;
    }

    function QueueRatingData($urid, $title, $permalink, $rclass)
    {
        if (isset(self::$ratings[$urid])){ return; }
        
        $title_short = (mb_strlen($title) > 256) ? trim(mb_substr($title, 0, 256)) . '...' : $title;
        $permalink = (mb_strlen($permalink) > 512) ? trim(mb_substr($permalink, 0, 512)) . '...' : $permalink;
        self::$ratings[$urid] = array("title" => $title, "permalink" => $permalink, "rclass" => $rclass);
    }

/* Messages.
--------------------------------------------------------------------------------------------*/
    private function _printMessages($messages, $class)
    {
        if (!$codes = $messages->get_error_codes()){ return; }
        
?>
<div class="<?php echo $class;?>">
<?php
        foreach ($codes as $code) :
            foreach ($messages->get_error_messages($code) as $message) :
?>
    <p><?php
        if ($code === "connect" || strtolower($message) == "couldn't connect to host")
        {
            echo "Couldn't connect to host. <b>If you keep getting this message over and over again, a workaround can be found <a href=\"". 
                 WP_RW__ADDRESS . rw_get_blog_url('solution-for-wordpress-plugin-couldnt-connect-to-host') . "\" targe=\"_blank\">here</a>.</b>";
        }
        else
        {
            echo $messages->get_error_data($code) ? $message : esc_html($message);
        } 
    ?></p>
<?php
            endforeach;
        endforeach;
        $messages = new WP_Error();
?>
</div>
<br class="clear" />
<?php        
    }
    
    private function _printErrors()
    {
        $this->_printMessages($this->errors, "error");
    }

    private function _printSuccess()
    {
        $this->_printMessages($this->success, "updated");
    }
    
    /* Public Static
    -------------------------------------------------*/
    static $TOP_RATED_WIDGET_LOADED = false;
    static function TopRatedWidgetLoaded()
    {
        self::$TOP_RATED_WIDGET_LOADED = true;
    }
    
    /* Admin Page Settings
    ---------------------------------------------------------------------------------------------------------------*/
    function rw_admin_menu_icon_css()
    {
        global $bp;
    ?>
        <style type="text/css">
            ul#adminmenu li.toplevel_page_<?php echo WP_RW__ADMIN_MENU_SLUG;?> .wp-menu-image a
            { background-image: url( <?php echo WP_RW__PLUGIN_URL . 'icons.png' ?> ) !important; background-position: -1px -32px; }
            ul#adminmenu li.toplevel_page_<?php echo WP_RW__ADMIN_MENU_SLUG;?>:hover .wp-menu-image a,
            ul#adminmenu li.toplevel_page_<?php echo WP_RW__ADMIN_MENU_SLUG;?>.wp-has-current-submenu .wp-menu-image a,
            ul#adminmenu li.toplevel_page_<?php echo WP_RW__ADMIN_MENU_SLUG;?>.current .wp-menu-image a
            { background-position: -1px 0; }
            ul#adminmenu li.toplevel_page_<?php echo WP_RW__ADMIN_MENU_SLUG;?> .wp-menu-image a img { display: none; }
        </style>

    <?php
    }
    
    function GoogleAnalytics()
    {
?>
<script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-20070413-1']);
    _gaq.push(['_setAllowLinker', true]);
    _gaq.push(['_setDomainName', 'none']);
    _gaq.push(['_trackPageview']);
<?php if (!$this->_isRegistered) : ?>
    _gaq.push(['_trackEvent', 'signup', 'wordpress']);
<?php endif; ?>
    
    (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
</script>        
<?php        
    }
    
    function InitScriptsAndStyles()
    {
//        wp_enqueue_script( 'rw-test', "/wp-admin/js/rw-test.js", array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), false, 1 );
        rw_enqueue_style('rw_wp_admin', 'wordpress/admin.css');
        rw_enqueue_script('rw_wp_admin', 'wordpress/admin.js');
        
        if (!$this->_inDashboard)
            return;

        // Enqueue JS.
        wp_enqueue_script('jquery');
        wp_enqueue_script('json2');
        
        // Enqueue CSS stylesheets.
        rw_enqueue_style('rw_wp_style', 'wordpress/style.css');
//        rw_enqueue_style('rw', 'settings.php');
        rw_enqueue_style('rw_fonts', add_query_arg(array('family' => 'Noto+Sans:400,700,400italic,700italic'), WP_RW__PROTOCOL . '://fonts.googleapis.com/css'));

        rw_register_script('rw', 'index.php');
        
        if (!$this->_isRegistered)
        {
            // Account activation page includes.
            rw_enqueue_script('rw_wp_validation', 'rw/validation.js');
            rw_enqueue_script('rw');
//            rw_enqueue_script('rw_wp_signup', 'wordpress/signup.php');
            wp_enqueue_script('jquery-postmessage', plugins_url('resources/js/jquery.ba-postmessage.min.js' ,__FILE__ ));
        }
        else
        {
            // Settings page includes.
            rw_enqueue_script('rw_cp', 'vendors/colorpicker.js');
            rw_enqueue_script('rw_cp_eye', 'vendors/eye.js');
            rw_enqueue_script('rw_cp_utils', 'vendors/utils.js');
            rw_enqueue_script('rw');
            rw_enqueue_script('rw_wp', 'wordpress/settings.js');
            
            // Reports includes.
            rw_enqueue_style('rw_cp', 'colorpicker.php');
            rw_enqueue_script('jquery-ui-datepicker', 'vendors/jquery-ui-1.8.9.custom.min.js');
            rw_enqueue_style('jquery-theme-smoothness', 'vendors/jquery/smoothness/jquery.smoothness.css');
            rw_enqueue_style('rw_external', 'style.css?all=t');
            rw_enqueue_style('rw_wp_reports', 'wordpress/reports.php');
        }
    }
    
    function ActivationNotice()
    {
        $this->Notice('<a href="edit.php?page=' . WP_RW__ADMIN_MENU_SLUG . '">Activate your account now</a> to start seeing the ratings.');
    }
    
    function admin_menu()
    {
        $this->is_admin = (bool)current_user_can('manage_options');
        
        if (!$this->is_admin)
            return;
        
        $pageLoaderFunction = 'SettingsPage';
        if (!$this->_isRegistered)
        {
            $pageLoaderFunction = 'rw_user_key_page';
            
            if (empty($_GET['page']) || WP_RW__ADMIN_MENU_SLUG != $_GET['page'])
                add_action('all_admin_notices', array(&$this, 'ActivationNotice'));
        }
        
        if ($this->_isRegistered && !WP_RW__OWNER_ID)
        {
            if (!$this->_inDashboard || !$this->TryToConfirmEmail())
                add_action('all_admin_notices', array(&$this, 'ConfirmationNotice'));
        }

        add_options_page(__('Rating-Widget Settings', WP_RW__ID), __('Ratings', WP_RW__ID), 'edit_posts', WP_RW__ADMIN_MENU_SLUG, array(&$this, $pageLoaderFunction));
        
        if ( function_exists('add_object_page') ) // WP 2.7+
            $hook = add_object_page(__('Rating-Widget Settings', WP_RW__ID), __('Ratings', WP_RW__ID), 'edit_posts', WP_RW__ADMIN_MENU_SLUG, array(&$this, $pageLoaderFunction), WP_RW__PLUGIN_URL . "icon.png" );
        else
            $hook = add_management_page(__( 'Rating-Widget Settings', WP_RW__ID ), __( 'Ratings', WP_RW__ID ), 'edit_posts', WP_RW__ADMIN_MENU_SLUG, array(&$this, $pageLoaderFunction) );
        

        if (!$this->_isRegistered)
            add_action("load-$hook", array(&$this, 'SignUpPageLoad'));
        else
            // Setup menu items.
            $this->SetupMenuItems();
    }

    function _cfcd208495d565ef66e7dff9f98764da()
    {
        return eval(base64_decode('DQogICAgICAgIHJldHVybiAoJ3RyaWFsJyA9PT0gV1BfUldfX1NJVEVfUExBTik7DQogICAgICAgIA=='));
    }
    
    function _c4ca4238a0b923820dcc509a6f75849b()
    {
        return (!$this->_cfcd208495d565ef66e7dff9f98764da() && !$this->_c81e728d9d4c2f636f067f89cc14862c());
    }
    
    function _c81e728d9d4c2f636f067f89cc14862c()
    {
        return eval(base64_decode('DQogICAgICAgIHJldHVybiAoIWlzX3N0cmluZyhXUF9SV19fU0lURV9TRUNSRVRfS0VZKSB8fCAnZnJlZScgPT09IFdQX1JXX19TSVRFX1BMQU4gfHwgJ2Jhc2ljJyA9PT0gV1BfUldfX1NJVEVfUExBTik7DQogICAgICAgIA=='));
    }
    
    function _eccbc87e4b5ce2fe28308fd9f2a7baf3()
    {
        return eval(base64_decode('DQogICAgICAgIGlmICgkdGhpcy0+X2NmY2QyMDg0OTVkNTY1ZWY2NmU3ZGZmOWY5ODc2NGRhKCkpDQogICAgICAgICAgICByZXR1cm4gdHJ1ZTsNCiAgICAgICAgICAgIA0KICAgICAgICByZXR1cm4gKGlzX3N0cmluZyhXUF9SV19fU0lURV9TRUNSRVRfS0VZKSAmJiAoJ3Byb2Zlc3Npb25hbCcgPT09IFdQX1JXX19TSVRFX1BMQU4gfHwgJ3ByZW1pdW0nID09PSBXUF9SV19fU0lURV9QTEFOIHx8ICdidXNpbmVzcycgPT09IFdQX1JXX19TSVRFX1BMQU4pKTsNCiAgICAgICAg'));
    }
    
    function SetupMenuItems()
    {
        RWLogger::LogEnterence("SetupMenuItems");
        
        $submenu = array();

        // Basic settings.
        $submenu[] = array(
            'menu_title' => 'Settings',
            'function' => 'SettingsPage',
            'slug' => '',
        );
        
        if ($this->IsBuddyPressInstalled())
            // BuddyPress settings.
            $submenu[] = array(
                'menu_title' => 'BuddyPress',
                'function' => 'SettingsPage',
            );

        if ($this->IsBBPressInstalled())
            // bbPress settings.
            $submenu[] = array(
                'menu_title' => 'bbPress',
                'function' => 'SettingsPage',
            );
        
        if (false === is_active_widget(false, false, strtolower('RatingWidgetPlugin_TopRatedWidget'), true))
            // Top-Rated Promotion Page.
            $submenu[] = array(
                'menu_title' => 'Top-Rated Widget',
                'function' => 'TopRatedSettingsPageRender',
                'load_function' => 'TopRatedSettingsPageLoad',
                'slug' => 'toprated',
            );

        $user_label = $this->IsBBPressInstalled() ? "User" : "Author";
         
        // Reports.
        $submenu[] = array(
            'menu_title' => 'Reports',
            'function' => 'ReportsPageRender',
        );
        
        // Advanced settings.
        $submenu[] = array(
            'menu_title' => 'Advanced',
            'function' => 'AdvancedSettingsPageRender',
            'load_function' => 'AdvancedSettingsPageLoad',
        );

        $submenu[] = array(
            'menu_title' => 'Account',
            'function' => 'AccountPageRender',
            'load_function' => 'UpdateSecret',
        );
        
        if ($this->_eccbc87e4b5ce2fe28308fd9f2a7baf3() && !$this->_cfcd208495d565ef66e7dff9f98764da())
            // Boosting.
            $submenu[] = array(
                'menu_title' => 'Boost',
                'function' => 'BoostPageRender',
                'load_function' => 'BoostPageLoad',
            );
        
        $submenu[] = array(
            'menu_title' => 'FAQ',
            'function' => '',
        );

        $submenu[] = array(
            'menu_title' => 'Support Forum',
            'slug' => 'help',
            'function' => '',
        );

        if (!$this->_c4ca4238a0b923820dcc509a6f75849b())
            // Upgrade link.
            $submenu[] = array(
                'menu_title' => '&#9733; Upgrade &#9733;',
                'slug' => 'upgrade',
                'function' => '',
            );

        foreach ($submenu as $item)
        {
            
            $hook = add_submenu_page(
                WP_RW__ADMIN_MENU_SLUG, 
                __(isset($item['page_title']) ? $item['page_title'] : ('Ratings &ndash; ' . $item['menu_title']), WP_RW__ID),
                __($item['menu_title'], WP_RW__ID), 
                (isset($item['capability']) ? $item['capability'] : 'edit_posts'),
                $this->GetMenuSlug(isset($item['slug']) ? $item['slug'] : strtolower($item['menu_title'])),
                array(&$this, $item['function']));
                
            if (isset($item['load_function']) && !empty($item['load_function']))
                add_action("load-$hook", array( &$this, $item['load_function']));
        }        
    }
    
    function SignUpPageLoad()
    {
        if ($this->_isRegistered)
            return;
        
        if ('post' === strtolower($_SERVER['REQUEST_METHOD']) && isset($_POST['action']) && 'account' === $_POST['action'])
        {
            $this->SetOption(WP_RW__DB_OPTION_OWNER_ID, $_POST['user_id']);
            $this->SetOption(WP_RW__DB_OPTION_OWNER_EMAIL, $_POST['user_email']);
            $this->SetOption(WP_RW__DB_OPTION_SITE_ID, $_POST['site_id']);
            $this->SetOption(WP_RW__DB_OPTION_SITE_PUBLIC_KEY, $_POST['public_key']);
            $this->SetOption(WP_RW__DB_OPTION_SITE_SECRET_KEY, $_POST['secret_key']);

            $this->SetOption(WP_RW__DB_OPTION_TRACKING, (isset($_POST['tracking']) && '1' == $_POST['tracking']));

            $this->StoreOptions();

            // Reload the page with the keys.
            rw_admin_redirect();
        }
    }
    
    function rw_user_key_page()
    {
        $this->_printErrors();
        rw_require_once_view('userkey_generation.php');
    }

    /* Reports
    ---------------------------------------------------------------------------------------------------------------*/
    private static function _getAddFilterQueryString($pQuery, $pName, $pValue)
    {
        $pos = strpos($pQuery, "{$pName}=");
        if (false !== $pos)
        {
            $end = $pos + strlen("{$pName}=");
            $cur = $end;
            $max = strlen($pQuery);
            while ($cur < $max && $pQuery[$cur] !== "&"){
                $cur++;
            }
            
            $pQuery = substr($pQuery, 0, $end) . urlencode($pValue) . substr($pQuery, $cur);
        }
        else
        {
            $pQuery .= (($pQuery === "") ? "" : "&") . "{$pName}=" . urlencode($pValue);
        }        
        
        return $pQuery;
    }
    
    private static function _getRemoveFilterFromQueryString($pQuery, $pName)
    {
        $pos = strpos($pQuery, "{$pName}=");
        
        if (false === $pos){ return $pQuery; }
        
        $end = $pos + strlen("{$pName}=");
        $cur = $end;
        $max = strlen($pQuery);
        while ($cur < $max && $pQuery[$cur] !== "&"){
            $cur++;
        }
        
        if ($pos > 0 && $pQuery[$pos - 1] === "&"){ $pos--; }
        
        return substr($pQuery, 0, $pos) . substr($pQuery, $cur);
    }
    
    
    function rw_general_report_page()
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_general_report_page", $params); }

        $elements = isset($_REQUEST["elements"]) ? $_REQUEST["elements"] : "posts";
        $orderby = isset($_REQUEST["orderby"]) ? $_REQUEST["orderby"] : "created";
        $order = isset($_REQUEST["order"]) ? $_REQUEST["order"] : "DESC";
        $date_from = isset($_REQUEST["from"]) ? $_REQUEST["from"] : date(WP_RW__DEFAULT_DATE_FORMAT, time() - WP_RW__PERIOD_MONTH);
        $date_to = isset($_REQUEST["to"]) ? $_REQUEST["to"] : date(WP_RW__DEFAULT_DATE_FORMAT);
        $rw_limit = isset($_REQUEST["limit"]) ? max(WP_RW__REPORT_RECORDS_MIN, min(WP_RW__REPORT_RECORDS_MAX, $_REQUEST["limit"])) : WP_RW__REPORT_RECORDS_MIN;
        $rw_offset = isset($_REQUEST["offset"]) ? max(0, (int)$_REQUEST["offset"]) : 0;
        
        switch ($elements)
        {
            case "activity-updates":
                $rating_options = WP_RW__ACTIVITY_UPDATES_OPTIONS;
                $rclass = "activity-update";
                break;
            case "activity-comments":
                $rating_options = WP_RW__ACTIVITY_COMMENTS_OPTIONS;
                $rclass = "activity-comment";
                break;
            case "forum-posts":
                $rating_options = WP_RW__FORUM_POSTS_OPTIONS;
                $rclass = "forum-post,new-forum-post";
                break;
            case "forum-replies":
                $rating_options = WP_RW__FORUM_POSTS_OPTIONS;
                $rclass = "forum-reply";
                break;
            case "users":
                $rating_options = WP_RW__USERS_OPTIONS;
                $rclass = "user";
                break;
            case "comments":
                $rating_options = WP_RW__COMMENTS_OPTIONS;
                $rclass = "comment,new-blog-comment";
                break;
            case "pages":
                $rating_options = WP_RW__PAGES_OPTIONS;
                $rclass = "page";
                break;
            case "posts":
            default:
                $rating_options = WP_RW__BLOG_POSTS_OPTIONS;
                $rclass = "front-post,blog-post,new-blog-post";
                break;
        }
        
        $rating_options = $this->GetOption($rating_options);
        $rating_type = isset($rating_options->type) ? $rating_options->type : 'star';
        $rating_stars = ($rating_type === "star") ? 
                        ((isset($rating_options->advanced) && isset($rating_options->advanced->star) && isset($rating_options->advanced->star->stars)) ? $rating_options->advanced->star->stars : WP_RW__DEF_STARS) :
                        false;
        
        $details = array( 
            "uid" => WP_RW__SITE_PUBLIC_KEY,
            "rclasses" => $rclass,
            "orderby" => $orderby,
            "order" => $order,
            "since_updated" => "{$date_from} 00:00:00",
            "due_updated" => "{$date_to} 23:59:59",
            "limit" => $rw_limit + 1,
            "offset" => $rw_offset,
        );
        
        $rw_ret_obj = $this->RemoteCall("action/report/general.php", $details, WP_RW__CACHE_TIMEOUT_REPORT);

        if (false === $rw_ret_obj){ return false; }
        
        // Decode RW ret object.
        $rw_ret_obj = json_decode($rw_ret_obj);

        if (RWLogger::IsOn()){ RWLogger::Log("ret_object", var_export($rw_ret_obj, true)); }
        
        if (false == $rw_ret_obj->success)
        {
            $this->rw_report_example_page();
            return false;
        }
        
        // Override token to client's call token for iframes.
        $this->AddToken($details, false);
        
        $empty_result = (!isset($rw_ret_obj->data) || !is_array($rw_ret_obj->data) || 0 == count($rw_ret_obj->data));
?>
<div class="wrap rw-dir-ltr rw-report">
    <?php $this->Notice('<strong style="color: red;">Note: data may be delayed 30 minutes.</strong>'); ?>
    <form method="post" action="">
        <div class="tablenav">
            <div>
                <span><?php _e('Date Range', WP_RW__ID) ?>:</span>
                <input type="text" value="<?php echo $date_from;?>" id="rw_date_from" name="rw_date_from" style="width: 90px; text-align: center;" />
                -
                <input type="text" value="<?php echo $date_to;?>" id="rw_date_to" name="rw_date_to" style="width: 90px; text-align: center;" />
                <script type="text/javascript">
                    jQuery.datepicker.setDefaults({
                        dateFormat: "yy-mm-dd"
                    })
                    
                    jQuery("#rw_date_from").datepicker({
                        maxDate: 0,
                        onSelect: function(dateText, inst){
                            jQuery("#rw_date_to").datepicker("option", "minDate", dateText);
                        }
                    });
                    jQuery("#rw_date_from").datepicker("setDate", "<?php echo $date_from;?>");
                    
                    jQuery("#rw_date_to").datepicker({
                        minDate: "<?php echo $date_from;?>",
                        maxDate: 0,
                        onSelect: function(dateText, inst){
                            jQuery("#rw_date_from").datepicker("option", "maxDate", dateText);
                        }
                    });
                    jQuery("#rw_date_to").datepicker("setDate", "<?php echo $date_to;?>");
                </script>
                <span><?php _e('Element', WP_RW__ID) ?>:</span>
                <select id="rw_elements">
                <?php
                    $select = array(
                        __('Posts', WP_RW__ID) => "posts",
                        __('Pages', WP_RW__ID) => "pages",
                        __('Comments', WP_RW__ID) => "comments"
                    );
                    
                    if ($this->IsBuddyPressInstalled())
                    {
                        $select[__('Activity-Updates', WP_RW__ID)] = "activity-updates";
                        $select[__('Activity-Comments', WP_RW__ID)] = "activity-comments";
                        $select[__('Users-Profiles', WP_RW__ID)] = "users";
                        
                        if ($this->IsBBPressInstalled())
                            $select[__('Forum-Posts', WP_RW__ID)] = "forum-posts";
                    }
                    
                    foreach ($select as $option => $value)
                    {
                        $selected = '';
                        if ($value === $elements){ $selected = ' selected="selected"'; }
                ?>
                    <option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $option; ?></option>
                <?php 
                    }
                ?>
                </select>
                <span><?php _e('Order By', WP_RW__ID) ?>:</span>
                <select id="rw_orderby">
                <?php
                    $select = array(
                        "title" => __('Title', WP_RW__ID),
                        "urid" => __('Id', WP_RW__ID),
                        "created" => __('Start Date', WP_RW__ID),
                        "updated" => __('Last Update', WP_RW__ID),
                        "votes" => __('Votes', WP_RW__ID),
                        "avgrate" => __('Average Rate', WP_RW__ID),
                    );
                    foreach ($select as $value => $option)
                    {
                        $selected = '';
                        if ($value == $orderby)
                            $selected = ' selected="selected"';
                ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $option; ?></option>
                <?php
                    }
                ?>
                </select>
                <input class="button-secondary action" type="button" value="<?php _e("Show", WP_RW__ID);?>" onclick="top.location = RWM.enrichQueryString(top.location.href, ['from', 'to', 'orderby', 'elements'], [jQuery('#rw_date_from').val(), jQuery('#rw_date_to').val(), jQuery('#rw_orderby').val(), jQuery('#rw_elements').val()]);" />
            </div>
        </div>
        <br />
        <table class="widefat rw-chart-title">
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php _e('Votes Timeline', WP_RW__ID) ?></th>
                </tr>
            </thead>
        </table>
        <iframe class="rw-chart" src="<?php
            $details["since"] = $details["since_updated"];
            $details["due"] = $details["due_updated"];
            $details["date"] = "updated";
            unset($details["since_updated"], $details["due_updated"]);

            $details["width"] = 950;
            $details["height"] = 200;
            
            $query = "";
            foreach ($details as $key => $value)
            {
                $query .= ($query == "") ? "?" : "&";
                $query .= "{$key}=" . urlencode($value);
            }
            echo WP_RW__ADDRESS . "/action/chart/column.php{$query}";
        ?>" width="<?php echo $details["width"];?>" height="<?php echo ($details["height"] + 4);?>" frameborder="0"></iframe>
        <br /><br />
        <table class="widefat"><?php
        $records_num = $showen_records_num = 0;
        if ($empty_result){ ?>
            <tbody>
                <tr>
                    <td colspan="6"><?php printf(__('No ratings here.', WP_RW__ID), $elements); ?></td>
                </tr>
            </tbody><?php
        }else{  ?>
            <thead>
                <tr>
                    <th scope="col" class="manage-column"></th>
                    <th scope="col" class="manage-column"><?php _e('Title', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Id', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Start Date', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Last Update', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Votes', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Average Rate', WP_RW__ID) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $alternate = true;
                
                $records_num = count($rw_ret_obj->data);
                $showen_records_num = min($records_num, $rw_limit);
                for ($i = 0; $i < $showen_records_num; $i++)
                {
                    $rating = $rw_ret_obj->data[$i];
            ?>
                <tr<?php if ($alternate) echo ' class="alternate"';?>>
                    <td>
                        <a href="<?php
//                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "report", WP_RW__REPORT_RATING);
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "urid", $rating->urid);
                            $query_string = self::_getAddFilterQueryString($query_string, "type", $rating_type);
                            if ("star" === $rating_type){
                                $query_string = self::_getAddFilterQueryString($query_string, "stars", $rating_stars);
                            }
                            
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                        ?>"><img src="<?php echo WP_RW__ADDRESS_IMG;?>rw.pie.icon.png" alt="" title="<?php _e('Rating Report', WP_RW__ID) ?>"></a>
                    </td>
                    <td><strong><a href="<?php echo $rating->url; ?>" target="_blank"><?php
                            echo (mb_strlen($rating->title) > 40) ?
                                  trim(mb_substr($rating->title, 0, 40)) . "..." :
                                  $rating->title;
                        ?></a></strong></td>
                    <td><?php echo $rating->urid;?></td>
                    <td><?php echo $rating->created;?></td>
                    <td><?php echo $rating->updated;?></td>
                    <td><?php echo $rating->votes;?></td>
                    <td>
                        <?php
                            $vars = array(
                                "votes" => $rating->votes,
                                "rate" => $rating->rate * ($rating_stars / WP_RW__DEF_STARS),
                                "dir" => "ltr",
                                "type" => $rating_type,
                                "stars" => $rating_stars,
                            );
                            
                            if ($rating_type == "star")
                            {
                                $vars["style"] = "yellow";
                                rw_require_view('rating.php', $vars);
                            }
                            else
                            {
                                $likes = floor($rating->rate / WP_RW__DEF_STARS);
                                $dislikes = max(0, $rating->votes - $likes);

                                $vars["style"] = "thumbs";
                                $vars["rate"] = 1;
                                rw_require_view('rating.php', $vars);
                                echo '<span style="line-height: 16px; color: darkGreen; padding-right: 5px;">' . $likes . '</span>';
                                $vars["rate"] = -1;
                                rw_require_view('rating.php', $vars);
                                echo '<span style="line-height: 16px; color: darkRed; padding-right: 5px;">' . $dislikes . '</span>';
                            }
                        ?>
                    </td>
                </tr>
            <?php                    
                    $alternate = !$alternate;
                }
            ?>
            </tbody>
        <?php 
        }
        ?>
        </table>
        <?php
            if ($showen_records_num > 0)
            {
        ?>
        <div class="rw-control-bar">
            <div style="float: left;">
                <span style="font-weight: bold; font-size: 12px;"><?php echo ($rw_offset + 1); ?>-<?php echo ($rw_offset + $showen_records_num); ?></span>
            </div>
            <div style="float: right;">
                <span><?php _e('Show rows', WP_RW__ID) ?>:</span>
                <select name="rw_limit" onchange="top.location = RWM.enrichQueryString(top.location.href, ['offset', 'limit'], [0, this.value]);">
                <?php
                    $limits = array(WP_RW__REPORT_RECORDS_MIN, 25, WP_RW__REPORT_RECORDS_MAX);
                    foreach ($limits as $limit)
                    {
                ?>
                    <option value="<?php echo $limit;?>"<?php if ($rw_limit == $limit) echo ' selected="selected"'; ?>><?php echo $limit;?></option>
                <?php
                    }
                ?>
                </select>
                <input type="button"<?php if ($rw_offset == 0) echo ' disabled="disabled"';?> class="button button-secondary action" style="margin-left: 20px;" onclick="top.location = '<?php
                    $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "offset", max(0, $rw_offset - $rw_limit));
                    echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                ?>';" value="Previous" />
                <input type="button"<?php if ($showen_records_num == $records_num) echo ' disabled="disabled"';?> class="button button-secondary action" onclick="top.location = '<?php
                    $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "offset", $rw_offset + $rw_limit);
                    echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                ?>';" value="Next" />
            </div>
        </div>
        <?php
            }
        ?>
    </form>
</div>
<?php        
    }
    
    public static function _isValidPCId($pDeviceID)
    {
        // Length check.
        if (strlen($pDeviceID) !== 36){
            return false;
        }
        
        if ($pDeviceID[8] != "-" ||
            $pDeviceID[13] != "-" ||
            $pDeviceID[18] != "-" ||
            $pDeviceID[23] != "-")
        {
            return false;
        }
        
       
        for ($i = 0; $i < 36; $i++)
        {
            if ($i == 8 || $i == 13 || $i == 18 || $i == 23){ $i++; }
            
            $code = ord($pDeviceID[$i]);
            if ($code < 48 || 
                $code > 70 || 
                ($code > 57 && $code < 65))
            {
                return false;
            }
        }

        return true;            
    }
        
    
    function rw_report_example_page()
    {
        rw_require_view('pages/admin/report-dummy.php');
    }
    
    function rw_explicit_report_page()
    {
        $filters = array(
            "vid" => array(
                "label" => "User Id",
                "validation" => create_function('$val', 'return (is_numeric($val) && $val >= 0);'),
            ),
            "pcid" => array(
                "label" => "PC Id",
                "validation" => create_function('$val', 'return (RatingWidgetPlugin::_isValidPCId($val));'),
            ),
            "ip" => array(
                "label" => "IP",
                "validation" => create_function('$val', 'return (1 === preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $val));'),
            ),
        );
        
        $elements = isset($_REQUEST["elements"]) ? $_REQUEST["elements"] : "posts";
        $orderby = isset($_REQUEST["orderby"]) ? $_REQUEST["orderby"] : "created";
        $order = isset($_REQUEST["order"]) ? $_REQUEST["order"] : "DESC";
        $date_from = isset($_REQUEST["from"]) ? $_REQUEST["from"] : date(WP_RW__DEFAULT_DATE_FORMAT, time() - WP_RW__PERIOD_MONTH);
        $date_to = isset($_REQUEST["to"]) ? $_REQUEST["to"] : date(WP_RW__DEFAULT_DATE_FORMAT);
        $rw_limit = isset($_REQUEST["limit"]) ? max(WP_RW__REPORT_RECORDS_MIN, min(WP_RW__REPORT_RECORDS_MAX, $_REQUEST["limit"])) : WP_RW__REPORT_RECORDS_MIN;
        $rw_offset = isset($_REQUEST["offset"]) ? max(0, (int)$_REQUEST["offset"]) : 0;
        
        switch ($elements)
        {
            case "activity-updates":
                $rating_options = WP_RW__ACTIVITY_UPDATES_OPTIONS;
                $rclass = "activity-update";
                break;
            case "activity-comments":
                $rating_options = WP_RW__ACTIVITY_COMMENTS_OPTIONS;
                $rclass = "activity-comment";
                break;
            case "forum-posts":
                $rating_options = WP_RW__FORUM_POSTS_OPTIONS;
                $rclass = "forum-post,new-forum-post";
                break;
            case "forum-replies":
                $rating_options = WP_RW__FORUM_POSTS_OPTIONS;
                $rclass = "forum-reply";
                break;
            case "users":
                $rating_options = WP_RW__USERS_OPTIONS;
                $rclass = "user";
                break;
            case "comments":
                $rating_options = WP_RW__COMMENTS_OPTIONS;
                $rclass = "comment,new-blog-comment";
                break;
            case "pages":
                $rating_options = WP_RW__PAGES_OPTIONS;
                $rclass = "page";
                break;
            case "posts":
            default:
                $rating_options = WP_RW__BLOG_POSTS_OPTIONS;
                $rclass = "front-post,blog-post,new-blog-post";
                break;
        }
        
        $rating_options = $this->GetOption($rating_options);
        $rating_type = isset($rating_options->type) ? $rating_options->type : 'star';
        $rating_stars = ($rating_type === "star") ? 
                        ((isset($rating_options->advanced) && isset($rating_options->advanced->star) && isset($rating_options->advanced->star->stars)) ? $rating_options->advanced->star->stars : WP_RW__DEF_STARS) :
                        false;
        
        $details = array( 
            "uid" => WP_RW__SITE_PUBLIC_KEY,
            "rclasses" => $rclass,
            "orderby" => $orderby,
            "order" => $order,
            "since_updated" => "{$date_from} 00:00:00",
            "due_updated" => "{$date_to} 23:59:59",
            "limit" => $rw_limit + 1,
            "offset" => $rw_offset,
        );
        
        // Attach filters data.
        foreach ($filters as $filter => $filter_data)
        {
            if (isset($_REQUEST[$filter]) && true === $filter_data["validation"]($_REQUEST[$filter])){
                $details[$filter] = $_REQUEST[$filter];
            }            
        }
        
        $rw_ret_obj = $this->RemoteCall("action/report/explicit.php", $details, WP_RW__CACHE_TIMEOUT_REPORT);

        if (false === $rw_ret_obj){ return false; }
        
        // Decode RW ret object.
        $rw_ret_obj = json_decode($rw_ret_obj);

        if (RWLogger::IsOn()){ RWLogger::Log("ret_object", var_export($rw_ret_obj, true)); }

        if (false == $rw_ret_obj->success)
        {
            $this->rw_report_example_page();
            return false;
        }
        
        // Override token to client's call token for iframes.
        $details["token"] = self::GenerateToken($details["timestamp"], false);

        $empty_result = (!is_array($rw_ret_obj->data) || 0 == count($rw_ret_obj->data));
?>
<div class="wrap rw-dir-ltr rw-report">
    <?php $this->Notice('<strong style="color: red;">Note: data may be delayed 30 minutes.</strong>'); ?>
    <form method="post" action="">
        <div class="tablenav">
            <div>
                <span><?php _e('Date Range', WP_RW__ID) ?>:</span>
                <input type="text" value="<?php echo $date_from;?>" id="rw_date_from" name="rw_date_from" style="width: 90px; text-align: center;" />
                -
                <input type="text" value="<?php echo $date_to;?>" id="rw_date_to" name="rw_date_to" style="width: 90px; text-align: center;" />
                <script type="text/javascript">
                    jQuery.datepicker.setDefaults({
                        dateFormat: "yy-mm-dd"
                    })
                    
                    jQuery("#rw_date_from").datepicker({
                        maxDate: 0,
                        onSelect: function(dateText, inst){
                            jQuery("#rw_date_to").datepicker("option", "minDate", dateText);
                        }
                    });
                    jQuery("#rw_date_from").datepicker("setDate", "<?php echo $date_from;?>");
                    
                    jQuery("#rw_date_to").datepicker({
                        minDate: "<?php echo $date_from;?>",
                        maxDate: 0,
                        onSelect: function(dateText, inst){
                            jQuery("#rw_date_from").datepicker("option", "maxDate", dateText);
                        }
                    });
                    jQuery("#rw_date_to").datepicker("setDate", "<?php echo $date_to;?>");
                </script>
                <span><?php _e('Order By', WP_RW__ID) ?>:</span>
                <select id="rw_orderby">
                <?php
                    $select = array(
                        "rid" => __('Rating Id', WP_RW__ID),
                        "created" => __('Start Date', WP_RW__ID),
                        "updated" => __('Last Update', WP_RW__ID),
                        "rate" => __('Rate', WP_RW__ID),
                        "vid" => __('User Id', WP_RW__ID),
                        "pcid" => __('PC Id', WP_RW__ID),
                        "ip" => __('IP', WP_RW__ID),
                    );
                    foreach ($select as $value => $option)
                    {
                        $selected = '';
                        if ($value == $orderby)
                            $selected = ' selected="selected"';
                ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $option; ?></option>
                <?php
                    }
                ?>
                </select>
                <input class="button-secondary action" type="button" value="<?php _e("Show", WP_RW__ID);?>" onclick="top.location = RWM.enrichQueryString(top.location.href, ['from', 'to', 'orderby'], [jQuery('#rw_date_from').val(), jQuery('#rw_date_to').val(), jQuery('#rw_orderby').val()]);" />
            </div>
        </div>
        <br />
        <div class="rw-filters">
        <?php
            foreach ($filters as $filter => $filter_data)
            {
                if (isset($_REQUEST[$filter]) && true === $filter_data["validation"]($_REQUEST[$filter]))
                {
        ?>
        <div class="rw-ui-report-filter">
            <a class="rw-ui-close" href="<?php
                $query_string = self::_getRemoveFilterFromQueryString($_SERVER["QUERY_STRING"], $filter);
                $query_string = self::_getRemoveFilterFromQueryString($query_string, "offset");
                echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
            ?>">x</a> |
            <span class="rw-ui-defenition"><?php echo $filter_data["label"];?>:</span>
            <span class="rw-ui-value"><?php echo $_REQUEST[$filter];?></span>
        </div>
        <?php
                }
            }
        ?>
        </div>
        <br />
        <br />
        <iframe class="rw-chart" src="<?php
            $details["since"] = $details["since_updated"];
            $details["due"] = $details["due_updated"];
            $details["date"] = "updated";
            unset($details["since_updated"], $details["due_updated"]);

            $details["width"] = 750;
            $details["height"] = 200;
            
            $query = "";
            foreach ($details as $key => $value)
            {
                $query .= ($query == "") ? "?" : "&";
                $query .= "{$key}=" . urlencode($value);
            }
            echo WP_RW__ADDRESS . "/action/chart/column.php{$query}";
        ?>" width="750" height="204" frameborder="0"></iframe>
        <br /><br />
        <table class="widefat"><?php
        $records_num = $showen_records_num = 0;
        if (!is_array($rw_ret_obj->data) || count($rw_ret_obj->data) === 0){ ?>
            <tbody>
                <tr>
                    <td colspan="6"><?php printf(__('No votes here.', WP_RW__ID)); ?></td>
                </tr>
            </tbody><?php
        }else{  ?>
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php _e('Rating Id', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('User Id', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('PC Id', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('IP', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Date', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Rate', WP_RW__ID) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $alternate = true;
                $records_num = count($rw_ret_obj->data);
                $showen_records_num = min($records_num, $rw_limit);
                for ($i = 0; $i < $showen_records_num; $i++)
                {
                    $vote = $rw_ret_obj->data[$i];
                    if ($vote->vid != "0"){
                        $user = get_userdata($vote->vid);
                    }
                    else
                    {
                        $user = new stdClass();
                        $user->user_login = "Anonymous";
                    }
            ?>
                <tr<?php if ($alternate) echo ' class="alternate"';?>>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "urid", $vote->urid);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo $vote->urid;?></a>
                    </td>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "vid", $vote->vid);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo $user->user_login;?></a>
                    </td>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "pcid", $vote->pcid);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo ($vote->pcid != "00000000-0000-0000-0000-000000000000") ? $vote->pcid : "Anonymous";?></a>
                    </td>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "ip", $vote->ip);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo $vote->ip;?></a>
                    </td>
                    <td><?php echo $vote->updated;?></td>
                    <td>
                        <?php
                            $vars = array(
                                "votes" => 1,
                                "rate" => $vote->rate * ($rating_stars / WP_RW__DEF_STARS),
                                "dir" => "ltr",
                                "type" => "star",
                                "stars" => $rating_stars,
                            );
                            
                            if ($rating_type == "star")
                            {
                                $vars["style"] = "yellow";
                                rw_require_view('rating.php', $vars);
                            }
                            else
                            {
                                $vars["type"] = "nero";
                                $vars["style"] = "thumbs";
                                $vars["rate"] = ($vars["rate"] > 0) ? 1 : -1;
                                rw_require_view('rating.php', $vars);
                            }
                        ?>
                    </td>
                </tr>
            <?php                    
                    $alternate = !$alternate;
                }
            ?>
            </tbody>
        <?php 
        }
        ?>
        </table>
        <?php
            if ($showen_records_num > 0)
            {
        ?>
        <div class="rw-control-bar">
            <div style="float: left;">
                <span style="font-weight: bold; font-size: 12px;"><?php echo ($rw_offset + 1); ?>-<?php echo ($rw_offset + $showen_records_num); ?></span>
            </div>
            <div style="float: right;">
                <span><?php _e('Show rows', WP_RW__ID) ?>:</span>
                <select name="rw_limit" onchange="top.location = RWM.enrichQueryString(top.location.href, ['offset', 'limit'], [0, this.value]);">
                <?php
                    $limits = array(WP_RW__REPORT_RECORDS_MIN, 25, WP_RW__REPORT_RECORDS_MAX);
                    foreach ($limits as $limit)
                    {
                ?>
                    <option value="<?php echo $limit;?>"<?php if ($rw_limit == $limit) echo ' selected="selected"'; ?>><?php echo $limit;?></option>
                <?php
                    }
                ?>
                </select>
                <input type="button"<?php if ($rw_offset == 0) echo ' disabled="disabled"';?> class="button button-secondary action" style="margin-left: 20px;" onclick="top.location = '<?php
                    $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "offset", max(0, $rw_offset - $rw_limit));
                    echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                ?>';" value="<?php _e('Previous', WP_RW__ID) ?>" />
                <input type="button"<?php if ($showen_records_num == $records_num) echo ' disabled="disabled"';?> class="button button-secondary action" onclick="top.location = '<?php
                    $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "offset", $rw_offset + $rw_limit);
                    echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                ?>';" value="<?php _e('Next', WP_RW__ID) ?>" />
            </div>
        </div>
        <?php
            }
        ?>
    </form>
</div>
<?php                
    }
    
    function rw_rating_report_page()
    {
        $filters = array(
            "urid" => array(
                "label" => "Rating Id",
                "validation" => create_function('$val', 'return (is_numeric($val) && $val >= 0);'),
            ),
            "vid" => array(
                "label" => "User Id",
                "validation" => create_function('$val', 'return (is_numeric($val) && $val >= 0);'),
            ),
            "pcid" => array(
                "label" => "PC Id",
                "validation" => create_function('$val', 'return (RatingWidgetPlugin::_isValidPCId($val));'),
            ),
            "ip" => array(
                "label" => "IP",
                "validation" => create_function('$val', 'return (1 === preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $val));'),
            ),
        );

        $orderby = isset($_REQUEST["orderby"]) ? $_REQUEST["orderby"] : "created";
        $order = isset($_REQUEST["order"]) ? $_REQUEST["order"] : "DESC";
        $date_from = isset($_REQUEST["from"]) ? $_REQUEST["from"] : date(WP_RW__DEFAULT_DATE_FORMAT, time() - WP_RW__PERIOD_MONTH);
        $date_to = isset($_REQUEST["to"]) ? $_REQUEST["to"] : date(WP_RW__DEFAULT_DATE_FORMAT);
        $rating_type = (isset($_REQUEST["type"]) && in_array($_REQUEST["type"], array("star", "nero"))) ? $_REQUEST["type"] : "star";
        $rating_stars = isset($_REQUEST["stars"]) ? max(WP_RW__MIN_STARS, min(WP_RW__MAX_STARS, (int)$_REQUEST["stars"])) : WP_RW__DEF_STARS;
        
        $rw_limit = isset($_REQUEST["limit"]) ? max(WP_RW__REPORT_RECORDS_MIN, min(WP_RW__REPORT_RECORDS_MAX, $_REQUEST["limit"])) : WP_RW__REPORT_RECORDS_MIN;
        $rw_offset = isset($_REQUEST["offset"]) ? max(0, (int)$_REQUEST["offset"]) : 0;
        
        $details = array( 
            "uid" => WP_RW__SITE_PUBLIC_KEY,
            "orderby" => $orderby,
            "order" => $order,
            "since" => "{$date_from} 00:00:00",
            "due" => "{$date_to} 23:59:59",
            "date" => "updated",
            "limit" => $rw_limit + 1,
            "offset" => $rw_offset,
            "stars" => $rating_stars,
            "type" => $rating_type,
        );
        
        // Attach filters data.
        foreach ($filters as $filter => $filter_data)
        {
            if (isset($_REQUEST[$filter]) && true === $filter_data["validation"]($_REQUEST[$filter])){
                $details[$filter] = $_REQUEST[$filter];
            }            
        }
        
        $rw_ret_obj = $this->RemoteCall("action/report/rating.php", $details, WP_RW__CACHE_TIMEOUT_REPORT);
        if (false === $rw_ret_obj){ return; }
        
        // Decode RW ret object.
        $rw_ret_obj = json_decode($rw_ret_obj);

        if (false == $rw_ret_obj->success)
        {
            $this->rw_report_example_page();
            return false;
        }
        
        $empty_result = (!is_array($rw_ret_obj->data) || 0 == count($rw_ret_obj->data));

        // Override token to client's call token for iframes.
        $details["token"] = self::GenerateToken($details["timestamp"], false);
?>
<div class="wrap rw-dir-ltr rw-report">
    <?php $this->Notice('<strong style="color: red;">Note: data may be delayed 30 minutes.</strong>'); ?>
    <form method="post" action="">
        <div class="tablenav">
            <div>
                <span><?php _e('Date Range', WP_RW__ID) ?>:</span>
                <input type="text" value="<?php echo $date_from;?>" id="rw_date_from" name="rw_date_from" style="width: 90px; text-align: center;" />
                -
                <input type="text" value="<?php echo $date_to;?>" id="rw_date_to" name="rw_date_to" style="width: 90px; text-align: center;" />
                <script type="text/javascript">
                    jQuery.datepicker.setDefaults({
                        dateFormat: "yy-mm-dd"
                    })
                    
                    jQuery("#rw_date_from").datepicker({
                        maxDate: 0,
                        onSelect: function(dateText, inst){
                            jQuery("#rw_date_to").datepicker("option", "minDate", dateText);
                        }
                    });
                    jQuery("#rw_date_from").datepicker("setDate", "<?php echo $date_from;?>");
                    
                    jQuery("#rw_date_to").datepicker({
                        minDate: "<?php echo $date_from;?>",
                        maxDate: 0,
                        onSelect: function(dateText, inst){
                            jQuery("#rw_date_from").datepicker("option", "maxDate", dateText);
                        }
                    });
                    jQuery("#rw_date_to").datepicker("setDate", "<?php echo $date_to;?>");
                </script>
                <span><?php _e('Order By', WP_RW__ID) ?>:</span>
                <select id="rw_orderby">
                <?php
                    $select = array(
                        "rid" => __('Id', WP_RW__ID),
                        "created" => __('Start Date', WP_RW__ID),
                        "updated" => __('Last Update', WP_RW__ID),
                        "rate" => __('Rate', WP_RW__ID),
                        "vid" => __('User Id', WP_RW__ID),
                        "pcid" => __('PC Id', WP_RW__ID),
                        "ip" => __('IP', WP_RW__ID),
                    );
                    foreach ($select as $value => $option)
                    {
                        $selected = '';
                        if ($value == $orderby)
                            $selected = ' selected="selected"';
                ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $option; ?></option>
                <?php
                    }
                ?>
                </select>
                <input class="button-secondary action" type="button" value="<?php _e("Show", WP_RW__ID);?>" onclick="top.location = RWM.enrichQueryString(top.location.href, ['from', 'to', 'orderby'], [jQuery('#rw_date_from').val(), jQuery('#rw_date_to').val(), jQuery('#rw_orderby').val()]);" />
            </div>
        </div>
        <br />
        <div class="rw-filters">
        <?php
            foreach ($filters as $filter => $filter_data)
            {
                if (isset($_REQUEST[$filter]) && true === $filter_data["validation"]($_REQUEST[$filter]))
                {
        ?>
        <div class="rw-ui-report-filter">
            <a class="rw-ui-close" href="<?php
                $query_string = self::_getRemoveFilterFromQueryString($_SERVER["QUERY_STRING"], $filter);
                $query_string = self::_getRemoveFilterFromQueryString($query_string, "offset");
                echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
            ?>">x</a> |
            <span class="rw-ui-defenition"><?php echo $filter_data["label"];?>:</span>
            <span class="rw-ui-value"><?php echo $_REQUEST[$filter];?></span>
        </div>
        <?php
                }
            }
        ?>
        </div>
        <br />
        <br />
        <iframe class="rw-chart" src="<?php
            $details["width"] = (!$empty_result) ? 647 : 950;
            $details["height"] = 200;

            $query = "";
            foreach ($details as $key => $value)
            {
                $query .= ($query == "") ? "?" : "&";
                $query .= "{$key}=" . urlencode($value);
            }
            echo WP_RW__ADDRESS . "/action/chart/column.php{$query}";
        ?>" width="<?php echo $details["width"];?>" height="<?php echo ($details["height"] + 4);?>" frameborder="0"></iframe>
        <?php
            if (!$empty_result)
            {
        ?>
        <iframe class="rw-chart" src="<?php
            $details["width"] = 300;
            $details["height"] = 200;

            $query = "";
            foreach ($details as $key => $value)
            {
                $query .= ($query == "") ? "?" : "&";
                $query .= "{$key}=" . urlencode($value);
            }
            $query .= "&stars={$rating_stars}";
            echo WP_RW__ADDRESS . "/action/chart/pie.php{$query}";
        ?>" width="<?php echo $details["width"];?>" height="<?php echo ($details["height"] + 4);?>" frameborder="0"></iframe>
        <?php
            }
        ?>
        <br /><br />
        <table class="widefat"><?php
        $records_num = $showen_records_num = 0;
        if (!is_array($rw_ret_obj->data) || count($rw_ret_obj->data) === 0){ ?>
            <tbody>
                <tr>
                    <td colspan="6"><?php printf(__('No votes here.', WP_RW__ID)); ?></td>
                </tr>
            </tbody><?php
        }else{  ?>
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php _e('User Id', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('PC Id', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('IP', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Date', WP_RW__ID) ?></th>
                    <th scope="col" class="manage-column"><?php _e('Rate', WP_RW__ID) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $alternate = true;
                $records_num = count($rw_ret_obj->data);
                $showen_records_num = min($records_num, $rw_limit);
                for ($i = 0; $i < $showen_records_num; $i++)
                {
                    $vote = $rw_ret_obj->data[$i];
                    if ($vote->vid != "0"){
                        $user = get_userdata($vote->vid);
                    }
                    else
                    {
                        $user = new stdClass();
                        $user->user_login = "Anonymous";
                    }
            ?>
                <tr<?php if ($alternate) echo ' class="alternate"';?>>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "vid", $vote->vid);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo $user->user_login;?></a>
                    </td>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "pcid", $vote->pcid);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo ($vote->pcid != "00000000-0000-0000-0000-000000000000") ? $vote->pcid : "Anonymous";?></a>
                    </td>
                    <td>
                        <a href="<?php
                            $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "ip", $vote->ip);
                            echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;                        
                        ?>"><?php echo $vote->ip;?></a>
                    <td><?php echo $vote->updated;?></td>
                    <td>
                        <?php
                            $vars = array(
                                "votes" => 1,
                                "rate" => $vote->rate * ($rating_stars / WP_RW__DEF_STARS),
                                "dir" => "ltr",
                                "type" => "star",
                                "stars" => $rating_stars,
                            );
                            
                            if ($rating_type == "star")
                            {
                                $vars["style"] = "yellow";
                                rw_require_view('rating.php', $vars);
                            }
                            else
                            {
                                $vars["type"] = "nero";
                                $vars["style"] = "thumbs";
                                $vars["rate"] = ($vars["rate"] > 0) ? 1 : -1;
                                rw_require_view('rating.php', $vars);
                            }
                        ?>
                    </td>
                </tr>
            <?php                    
                    $alternate = !$alternate;
                }
            ?>
            </tbody>
        <?php 
        }
        ?>
        </table>
        <?php
            if ($showen_records_num > 0)
            {
        ?>
        <div class="rw-control-bar">
            <div style="float: left;">
                <span style="font-weight: bold; font-size: 12px;"><?php echo ($rw_offset + 1); ?>-<?php echo ($rw_offset + $showen_records_num); ?></span>
            </div>
            <div style="float: right;">
                <span><?php _e('Show rows', WP_RW__ID) ?>:</span>
                <select name="rw_limit" onchange="top.location = RWM.enrichQueryString(top.location.href, ['offset', 'limit'], [0, this.value]);">
                <?php
                    $limits = array(WP_RW__REPORT_RECORDS_MIN, 25, WP_RW__REPORT_RECORDS_MAX);
                    foreach ($limits as $limit)
                    {
                ?>
                    <option value="<?php echo $limit;?>"<?php if ($rw_limit == $limit) echo ' selected="selected"'; ?>><?php echo $limit;?></option>
                <?php
                    }
                ?>
                </select>
                <input type="button"<?php if ($rw_offset == 0) echo ' disabled="disabled"';?> class="button button-secondary action" style="margin-left: 20px;" onclick="top.location = '<?php
                    $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "offset", max(0, $rw_offset - $rw_limit));
                    echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                ?>';" value="<?php _e('Previous', WP_RW__ID) ?>" />
                <input type="button"<?php if ($showen_records_num == $records_num) echo ' disabled="disabled"';?> class="button button-secondary action" onclick="top.location = '<?php
                    $query_string = self::_getAddFilterQueryString($_SERVER["QUERY_STRING"], "offset", $rw_offset + $rw_limit);
                    echo $_SERVER["SCRIPT_URI"] . "?" . $query_string;
                ?>';" value="<?php _e('Next', WP_RW__ID) ?>" />
            </div>
        </div>
        <?php
            }
        ?>
    </form>
</div>
<?php                
    }
    
    function AccountPageRender()
    {
        rw_require_once_view('pages/admin/account.php');
    }
    
    function AdvancedSettingsPageLoad()
    {
        $rw_delete_history = (isset($_POST["rw_delete_history"]) && in_array($_POST["rw_delete_history"], array("true", "false"))) ? 
                             $_POST["rw_delete_history"] : 
                             "false";
        
        if ('true' === $rw_delete_history)
        {
            // Delete user-key & secret.
            $this->UnsetOption(WP_RW__DB_OPTION_SITE_PUBLIC_KEY);
            $this->UnsetOption(WP_RW__DB_OPTION_SITE_ID);
            $this->UnsetOption(WP_RW__DB_OPTION_SITE_SECRET_KEY);
            $this->StoreOptions();
            
            // Goto user-key creation page.
            rw_admin_redirect();
        }
    }
    
    /* Advanced Settings
    ---------------------------------------------------------------------------------------------------------------*/
    function AdvancedSettingsPageRender()
    {
        // Variables for the field and option names 
        $rw_form_hidden_field_name = "rw_form_hidden_field_name";

        // Get visitor identification method.
        $rw_identify_by = $this->GetOption(WP_RW__IDENTIFY_BY);

        // Get flash dependency.
        $rw_flash_dependency = $this->GetOption(WP_RW__FLASH_DEPENDENCY);
        
        // Get show on mobile flag.
        $rw_show_on_mobile =  $this->GetOption(WP_RW__SHOW_ON_MOBILE);
        
        if (isset($_POST[$rw_form_hidden_field_name]) && $_POST[$rw_form_hidden_field_name] == 'Y')
        {
            $this->settings->SetSaveMode();
            
            $rw_restore_defaults = (isset($_POST["rw_restore_defaults"]) && in_array($_POST["rw_restore_defaults"], array("true", "false"))) ? 
                                 $_POST["rw_restore_defaults"] : 
                                 "false";

            if ('true' === $rw_restore_defaults)
            {
                // Restore to defaults - clear all settings.
                $this->ClearOptions();
                
                // Re-Load all advanced settings.
                    $rw_identify_by = $this->GetOption(WP_RW__IDENTIFY_BY);
                    $rw_flash_dependency = $this->GetOption(WP_RW__FLASH_DEPENDENCY);
                    $rw_show_on_mobile = $this->GetOption(WP_RW__SHOW_ON_MOBILE);
                    $tracking = $this->GetOption(WP_RW__DB_OPTION_TRACKING);

                    $this->SetOption(WP_RW__IDENTIFY_BY, $rw_identify_by);
                    $this->SetOption(WP_RW__FLASH_DEPENDENCY, $rw_flash_dependency);
                    $this->SetOption(WP_RW__SHOW_ON_MOBILE, $rw_show_on_mobile);
                    $this->SetOption(WP_RW__DB_OPTION_TRACKING, $tracking);
                    
                    // Restore account details.
                    $this->SetOption(WP_RW__DB_OPTION_SITE_PUBLIC_KEY, WP_RW__SITE_PUBLIC_KEY);
                    $this->SetOption(WP_RW__DB_OPTION_SITE_ID, WP_RW__SITE_ID);
                    $this->SetOption(WP_RW__DB_OPTION_SITE_SECRET_KEY, WP_RW__SITE_SECRET_KEY);
                    $this->SetOption(WP_RW__DB_OPTION_OWNER_ID, WP_RW__OWNER_ID);
                    $this->SetOption(WP_RW__DB_OPTION_OWNER_EMAIL, WP_RW__OWNER_EMAIL);
            }
            else
            {
                // Save advanced settings.
                    // Get posted identification method.
                    if (isset($_POST["rw_identify_by"]) && in_array($_POST["rw_identify_by"], array("ip", "laccount")))
                    {
                        $rw_identify_by = $_POST["rw_identify_by"];
                        $this->SetOption(WP_RW__IDENTIFY_BY, $rw_identify_by);
                    }

                    // Get posted flash dependency.
                    if (isset($_POST["rw_flash_dependency"]) && in_array($_POST["rw_flash_dependency"], array("true", "false")))
                    {
                        $rw_flash_dependency = ('true' == $_POST["rw_flash_dependency"]);
                        // Save flash dependency.
                        $this->SetOption(WP_RW__FLASH_DEPENDENCY, $rw_flash_dependency);
                    }

                    // Get mobile flag.
                    if (isset($_POST["rw_show_on_mobile"]) && in_array($_POST["rw_show_on_mobile"], array("true", "false")))
                    {
                        $rw_show_on_mobile = ('true' == $_POST["rw_show_on_mobile"]);
                        // Save show on mobile flag.
                        $this->SetOption(WP_RW__SHOW_ON_MOBILE, $rw_show_on_mobile);
                    }
            }
?>
    <div class="updated"><p><strong><?php _e('Settings successfully saved.', WP_RW__ID ); ?></strong></p></div>
<?php
        }
        
        $this->settings->form_hidden_field_name = $rw_form_hidden_field_name;
        $this->settings->identify_by = $rw_identify_by;
        $this->settings->flash_dependency = $rw_flash_dependency;
        $this->settings->show_on_mobile = $rw_show_on_mobile;
        
        rw_require_once_view('pages/admin/advanced.php');

        // Store options if in save mode.
        if ($this->settings->IsSaveMode())
            $this->StoreOptions();
    }
    
    function TopRatedSettingsPageLoad()
    {
        rw_enqueue_style('rw_toprated', rw_get_plugin_css_path('toprated.css'));
    }
    
    function TopRatedSettingsPageRender()
    {
?>
<div class="wrap rw-dir-ltr rw-wp-container">
    <h2><?php echo __( 'Increase User Retention and Pageviews', WP_RW__ID);?></h2>
    <div>
        <p style="font-weight: bold; font-size: 14px;"><?php _e('With the Top-Rated Sidebar Widget readers will stay on your site for a longer period of time.', WP_RW__ID) ?></p>
        <ul>
            <li>
                <ul id="screenshots">
                    <li>
                        <img src="<?php echo rw_get_plugin_img_path('top-rated/legacy.png');?>" alt="">
                    </li>
                    <li>
                        <img src="<?php echo rw_get_plugin_img_path('top-rated/compact-thumbs.png');?>" alt="">
                    </li>
                    <li>
                        <img src="<?php echo rw_get_plugin_img_path('top-rated/thumbs.png');?>" alt="">
                    </li>
                </ul>
                <div style="clear: both;"> </div>
            </li>
            <li>
                <a href="<?php echo get_admin_url(null, 'widgets.php'); ?>" class="button-primary" style="margin-left: 20px; display: block; text-align: center; width: 720px;"><?php _e('Add Widget Now!', WP_RW__ID) ?></a>
            </li>
            <li>
                <h3><?php _e('How', WP_RW__ID) ?></h3>
                <p><?php _e('Expose your readers to the top rated posts onsite that they might not have otherwise noticed and increase your chance to reduce the bounce rate.', WP_RW__ID) ?></p>
            </li>
            <li>
                <h3><?php _e('What', WP_RW__ID) ?></h3>
                <p><?php _e('The Top-Rated Widget is a beautiful sidebar widget containing the top rated posts on your blog.', WP_RW__ID) ?></p>
            </li>
            <li>
                <h3><?php _e('Install', WP_RW__ID) ?></h3>
                <p><?php _e('Go to', WP_RW__ID) ?> <b><i><a href="<?php echo get_admin_url(null, 'widgets.php'); ?>" class="button-primary">Appearence > Widgets</a></i></b> and simply drag the <b>Rating-Widget: Top Rated</b> widget to your sidebar.</p>
                <img src="<?php echo rw_get_plugin_img_path('top-rated/add-widget.png');?>" alt="">
            </li>
            <li>
                <h3><?php _e('New', WP_RW__ID) ?></h3>
                <p><?php _e('Thumbnails: a beautiful new thumbnail display, for themes which use post thumbnails (featured images).', WP_RW__ID) ?></p>
            </li>
            <li>
                <h3><?php _e('Performance', WP_RW__ID) ?></h3>
                <p><?php _e('The widget is performant, caching the top posts and featured images\' thumbnails as your site is visited.', WP_RW__ID) ?></p>
            </li>
            <li>
                <a href="<?php echo get_admin_url(null, 'widgets.php'); ?>" class="button-primary"><?php _e('Add Widget Now!', WP_RW__ID) ?></a>
            </li>
        </ul>
    </div>
    <br />
</div>
<?php        
    }
    
    function ReportsPageRender()
    {
        if (!$this->_eccbc87e4b5ce2fe28308fd9f2a7baf3())
        {
            $this->rw_report_example_page();
        }
        else if (isset($_GET["urid"]) && is_numeric($_GET["urid"]))
        {
            $this->rw_rating_report_page();
        }
        else if (isset($_GET["ip"]) || isset($_GET["vid"]) || isset($_GET["pcid"]))
        {
            $this->rw_explicit_report_page();
        }
        else
        {
            $this->rw_general_report_page();
        }
    }
    
    private function GetMenuSlug($pSlug = '')
    {
        return WP_RW__ADMIN_MENU_SLUG . (empty($pSlug) ? '' : ('-' . $pSlug));
    }
    
    /**
    * To get a list of all custom user defined posts:
    *   
    *       get_post_types(array('public'=>true,'_builtin' => false))
    */
    function SettingsPage()
    {
        RWLogger::LogEnterence("SettingsPage");

        // Must check that the user has the required capability.
        if (!current_user_can('manage_options'))
            wp_die(__('You do not have sufficient permissions to access this page.', WP_RW__ID));

        global $plugin_page;
        
        // Variables for the field and option names 
        $rw_form_hidden_field_name = "rw_form_hidden_field_name";
        
        if ($plugin_page === $this->GetMenuSlug('buddypress') && $this->IsBuddyPressInstalled())
        {
            $settings_data = array(
                "activity-blog-posts" => array(
                    "tab" => "Activity Blog Posts",
                    "class" => "new-blog-post",
                    "options" => WP_RW__ACTIVITY_BLOG_POSTS_OPTIONS,
                    "align" => WP_RW__ACTIVITY_BLOG_POSTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__ACTIVITY_BLOG_POSTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "activity-blog-comments" => array(
                    "tab" => "Activity Blog Comments",
                    "class" => "new-blog-comment",
                    "options" => WP_RW__ACTIVITY_BLOG_COMMENTS_OPTIONS,
                    "align" => WP_RW__ACTIVITY_BLOG_COMMENTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__ACTIVITY_BLOG_COMMENTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "activity-updates" => array(
                    "tab" => "Activity Updates",
                    "class" => "activity-update",
                    "options" => WP_RW__ACTIVITY_UPDATES_OPTIONS,
                    "align" => WP_RW__ACTIVITY_UPDATES_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__ACTIVITY_UPDATES_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "activity-comments" => array(
                    "tab" => "Activity Comments",
                    "class" => "activity-comment",
                    "options" => WP_RW__ACTIVITY_COMMENTS_OPTIONS,
                    "align" => WP_RW__ACTIVITY_COMMENTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__ACTIVITY_COMMENTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "users" => array(
                    "tab" => "Users Profiles",
                    "class" => "user",
                    "options" => WP_RW__USERS_OPTIONS,
                    "align" => WP_RW__USERS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                ),
            );

            $selected_key = isset($_GET["rating"]) ? $_GET["rating"] : "activity-blog-posts";
            if (!isset($settings_data[$selected_key]))
                $selected_key = "activity-blog-posts";
        }
        else if ($plugin_page === $this->GetMenuSlug('bbpress') && $this->IsBBPressInstalled())
        {
            $settings_data = array(
                /*"forum-topics" => array(
                    "tab" => "Forum Topics",
                    "class" => "forum-topic",
                    "options" => WP_RW__FORUM_TOPICS_OPTIONS,
                    "align" => WP_RW__FORUM_TOPICS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__FORUM_TOPICS_ALIGN],
                    "excerpt" => false,
                ),*/
                "forum-posts" => array(
                    "tab" => "Forum Posts",
                    "class" => "forum-post",
                    "options" => WP_RW__FORUM_POSTS_OPTIONS,
                    "align" => WP_RW__FORUM_POSTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__FORUM_POSTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                /*"activity-forum-topics" => array(
                    "tab" => "Activity Forum Topics",
                    "class" => "new-forum-topic",
                    "options" => WP_RW__ACTIVITY_FORUM_TOPICS_OPTIONS,
                    "align" => WP_RW__ACTIVITY_FORUM_TOPICS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__ACTIVITY_FORUM_TOPICS_ALIGN],
                    "excerpt" => false,
                ),*/
                "activity-forum-posts" => array(
                    "tab" => "Activity Forum Posts",
                    "class" => "new-forum-post",
                    "options" => WP_RW__ACTIVITY_FORUM_POSTS_OPTIONS,
                    "align" => WP_RW__ACTIVITY_FORUM_POSTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__ACTIVITY_FORUM_POSTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "users" => array(
                    "tab" => "Users Profiles",
                    "class" => "user",
                    "options" => WP_RW__USERS_OPTIONS,
                    "align" => WP_RW__USERS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                ),
            );
            
            $selected_key = isset($_GET["rating"]) ? $_GET["rating"] : "forum-posts";
            if (!isset($settings_data[$selected_key]))
                $selected_key = "forum-posts";
        }
        else if ($plugin_page === $this->GetMenuSlug('user'))
        {
            $settings_data = array(
                "users-posts" => array(
                    "tab" => "Posts",
                    "class" => "user-post",
                    "options" => WP_RW__USERS_POSTS_OPTIONS,
                    "align" => WP_RW__USERS_POSTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_POSTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                ),
                "users-pages" => array(
                    "tab" => "Pages",
                    "class" => "user-page",
                    "options" => WP_RW__USERS_PAGES_OPTIONS,
                    "align" => WP_RW__USERS_PAGES_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_PAGES_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                ),
                "users-comments" => array(
                    "tab" => "Comments",
                    "class" => "user-comment",
                    "options" => WP_RW__USERS_COMMENTS_OPTIONS,
                    "align" => WP_RW__USERS_COMMENTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_COMMENTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                ),
            );
            
            if ($this->IsBuddyPressInstalled())
            {
                $settings_data["users-activity-updates"] = array(
                    "tab" => "Activity Updates",
                    "class" => "user-activity-update",
                    "options" => WP_RW__USERS_ACTIVITY_UPDATES_OPTIONS,
                    "align" => WP_RW__USERS_ACTIVITY_UPDATES_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_ACTIVITY_UPDATES_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                );
                $settings_data["users-activity-comments"] = array(
                    "tab" => "Activity Comments",
                    "class" => "user-activity-comment",
                    "options" => WP_RW__USERS_ACTIVITY_COMMENTS_OPTIONS,
                    "align" => WP_RW__USERS_ACTIVITY_COMMENTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_ACTIVITY_COMMENTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => false,
                );
                
                if ($this->IsBBPressInstalled())
                {
                    $settings_data["users-forum-posts"] = array(
                        "tab" => "Forum Posts",
                        "class" => "user-forum-post",
                        "options" => WP_RW__USERS_FORUM_POSTS_OPTIONS,
                        "align" => WP_RW__USERS_FORUM_POSTS_ALIGN,
                        "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__USERS_FORUM_POSTS_ALIGN],
                        "excerpt" => false,
                        "show_align" => false,
                    );
                }
            }

            $selected_key = isset($_GET["rating"]) ? $_GET["rating"] : "users-posts";
            if (!isset($settings_data[$selected_key]))
                $selected_key = "users-posts";
        }
        else
        {
            $settings_data = array(
                "blog-posts" => array(
                    "tab" => "Blog Posts",
                    "class" => "blog-post",
                    "options" => WP_RW__BLOG_POSTS_OPTIONS,
                    "align" => WP_RW__BLOG_POSTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__BLOG_POSTS_ALIGN],
                    "excerpt" => true,
                    "show_align" => true,
                ),
                "front-posts" => array(
                    "tab" => "Front Page Posts",
                    "class" => "front-post",
                    "options" => WP_RW__FRONT_POSTS_OPTIONS,
                    "align" => WP_RW__FRONT_POSTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__FRONT_POSTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "comments" => array(
                    "tab" => "Comments",
                    "class" => "comment",
                    "options" => WP_RW__COMMENTS_OPTIONS,
                    "align" => WP_RW__COMMENTS_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__COMMENTS_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
                "pages" => array(
                    "tab" => "Pages",
                    "class" => "page",
                    "options" => WP_RW__PAGES_OPTIONS,
                    "align" => WP_RW__PAGES_ALIGN,
                    "default_align" => $this->_OPTIONS_DEFAULTS[WP_RW__PAGES_ALIGN],
                    "excerpt" => false,
                    "show_align" => true,
                ),
            );
            
            $selected_key = isset($_GET["rating"]) ? $_GET["rating"] : "blog-posts";
            if (!isset($settings_data[$selected_key]))
                $selected_key = "blog-posts";
        }
        
        $rw_current_settings = $settings_data[$selected_key];

        $is_blog_post = ('blog-post' === $rw_current_settings['class']);
        $item_with_category = in_array($rw_current_settings['class'], array('blog-post', 'front-post', 'comment'));
        
        // Visibility list must be loaded anyway.
        $this->_visibilityList = $this->GetOption(WP_RW__VISIBILITY_SETTINGS);

        if ($item_with_category)
            // Categories Availability list must be loaded anyway.
            $this->categories_list = $this->GetOption(WP_RW__CATEGORIES_AVAILABILITY_SETTINGS);

        // Availability list must be loaded anyway.
        $this->availability_list = $this->GetOption(WP_RW__AVAILABILITY_SETTINGS);

        $this->custom_settings_enabled_list = $this->GetOption(WP_RW__CUSTOM_SETTINGS_ENABLED);
        $this->custom_settings_list = $this->GetOption(WP_RW__CUSTOM_SETTINGS);

        // Accumulated user ratings support.
        if ('users' === $selected_key && $this->IsBBPressInstalled())
            $rw_is_user_accumulated = $this->GetOption(WP_RW__IS_ACCUMULATED_USER_RATING);
        
        // Some alias.
        $rw_class = $rw_current_settings["class"];
        
        // Reset categories.
        $rw_categories = array();
        
        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'
        if (isset($_POST[$rw_form_hidden_field_name]) && $_POST[$rw_form_hidden_field_name] == 'Y')
        {
            // Set settings into save mode.
            $this->settings->SetSaveMode();
            
            /* Widget align options.
            ---------------------------------------------------------------------------------------------------------------*/
            $rw_show_rating = isset($_POST["rw_show"]) ? true : false;
            $rw_align =  (!$rw_show_rating) ? new stdClass() : $rw_current_settings["default_align"];
            if ($rw_show_rating && isset($_POST["rw_align"]))
            {
                $align = explode(" ", $_POST["rw_align"]);
                if (is_array($align) && count($align) == 2)
                {
                    if (in_array($align[0], array("top", "bottom")) &&
                        in_array($align[1], array("left", "center", "right")))
                    {
                        $rw_align->ver = $align[0];
                        $rw_align->hor = $align[1];
                    }
                }
            }
            $this->SetOption($rw_current_settings["align"], $rw_align);
            
            /* Rating-Widget options.
            ---------------------------------------------------------------------------------------------------------------*/
            $rw_options = json_decode(preg_replace('/\%u([0-9A-F]{4})/i', '\\u$1', urldecode(stripslashes($_POST["rw_options"]))));
            if (null !== $rw_options)
                $this->SetOption($rw_current_settings["options"], $rw_options);
            
            /* Availability settings.
            ---------------------------------------------------------------------------------------------------------------*/
            $rw_availability = isset($_POST["rw_availability"]) ? max(0, min(2, (int)$_POST["rw_availability"])) : 0;
            
            $this->availability_list->{$rw_class} = $rw_availability;
            $this->SetOption(WP_RW__AVAILABILITY_SETTINGS, $this->availability_list);
            
            if ($item_with_category)
            {
                /* Categories Availability settings.
                ---------------------------------------------------------------------------------------------------------------*/
                $rw_categories = isset($_POST["rw_categories"]) && is_array($_POST["rw_categories"]) ? $_POST["rw_categories"] : array();
                
                $this->categories_list->{$rw_class} = (in_array("-1", $rw_categories) ? array("-1") : $rw_categories);
                $this->SetOption(WP_RW__CATEGORIES_AVAILABILITY_SETTINGS, $this->categories_list);
            }

            // Accumulated user ratings support.
            if ('users' === $selected_key && $this->IsBBPressInstalled() && isset($_POST['rw_accumulated_user_rating']))
            {
                $rw_is_user_accumulated = ('true' == (in_array($_POST['rw_accumulated_user_rating'], array('true', 'false')) ? $_POST['rw_accumulated_user_rating'] : 'true'));
                $this->SetOption(WP_RW__IS_ACCUMULATED_USER_RATING, $rw_is_user_accumulated);
            }
            
            /* Visibility settings
            ---------------------------------------------------------------------------------------------------------------*/
            $rw_visibility = isset($_POST["rw_visibility"]) ? max(0, min(2, (int)$_POST["rw_visibility"])) : 0;
            $rw_visibility_exclude  = isset($_POST["rw_visibility_exclude"]) ? $_POST["rw_visibility_exclude"] : "";
            $rw_visibility_include  = isset($_POST["rw_visibility_include"]) ? $_POST["rw_visibility_include"] : "";
            
            $rw_custom_settings_enabled = isset($_POST["rw_custom_settings_enabled"]) ? true : false;
            $this->custom_settings_enabled_list->{$rw_class} = $rw_custom_settings_enabled;
            $this->SetOption(WP_RW__CUSTOM_SETTINGS_ENABLED, $this->custom_settings_enabled_list);
            
            $rw_custom_settings = isset($_POST["rw_custom_settings"]) ? $_POST["rw_custom_settings"] : '';
            $this->custom_settings_list->{$rw_class} = $rw_custom_settings;
            $this->SetOption(WP_RW__CUSTOM_SETTINGS, $this->custom_settings_list);
            
            $this->_visibilityList->{$rw_class}->selected = $rw_visibility;
            $this->_visibilityList->{$rw_class}->exclude = self::IDsCollectionToArray($rw_visibility_exclude);
            $this->_visibilityList->{$rw_class}->include = self::IDsCollectionToArray($rw_visibility_include);
            $this->SetOption(WP_RW__VISIBILITY_SETTINGS, $this->_visibilityList);
    ?>
    <div class="updated"><p><strong><?php _e('Settings successfully saved.', WP_RW__ID ); ?></strong></p></div>
    <?php
        }
        else
        {
            /* Get rating alignment.
            ---------------------------------------------------------------------------------------------------------------*/
            $rw_align = $this->GetOption($rw_current_settings["align"]);

            /* Get show on excerpts option.
            ---------------------------------------------------------------------------------------------------------------*/
                // Already loaded.

            /* Get rating options.
            ---------------------------------------------------------------------------------------------------------------*/
            $rw_options = $this->GetOption($rw_current_settings["options"]);
            
            /* Get availability settings.
            ---------------------------------------------------------------------------------------------------------------*/
                // Already loaded.

            /* Get visibility settings
            ---------------------------------------------------------------------------------------------------------------*/
                // Already loaded.
        }
        
            
        $rw_language_str = isset($rw_options->lng) ? $rw_options->lng : WP_RW__DEFAULT_LNG;
        
        if (!isset($this->_visibilityList->{$rw_class}))
        {
            $this->_visibilityList->{$rw_class} = new stdClass();
            $this->_visibilityList->{$rw_class}->selected = 0;
            $this->_visibilityList->{$rw_class}->exclude = "";
            $this->_visibilityList->{$rw_class}->include = "";
        }
        $rw_visibility_settings = $this->_visibilityList->{$rw_class};
        
        if (!isset($this->availability_list->{$rw_class})){
            $this->availability_list->{$rw_class} = 0;
        }
        $rw_availability_settings = $this->availability_list->{$rw_class};

        if ($item_with_category)
        {
            if (!isset($this->categories_list->{$rw_class})){
                $this->categories_list->{$rw_class} = array(-1);
            }
            $rw_categories = $this->categories_list->{$rw_class};
        }
        
        
        if (!isset($this->custom_settings_enabled_list->{$rw_class}))
            $this->custom_settings_enabled_list->{$rw_class} = false;
        $rw_custom_settings_enabled = $this->custom_settings_enabled_list->{$rw_class};

        if (!isset($this->custom_settings_list->{$rw_class}))
            $this->custom_settings_list->{$rw_class} = '';
        $rw_custom_settings = $this->custom_settings_list->{$rw_class};
        
        require_once(WP_RW__PLUGIN_DIR . "/languages/{$rw_language_str}.php");
        require_once(WP_RW__PLUGIN_DIR . "/lib/defaults.php");
        require_once(WP_RW__PLUGIN_DIR . "/lib/def_settings.php");
        
        global $DEFAULT_OPTIONS;
        rw_set_language_options($DEFAULT_OPTIONS, $dictionary, $dir, $hor);
        
        $rating_font_size_set = false;
        $rating_line_height_set = false;
        $theme_font_size_set = false;
        $theme_line_height_set = false;

        $rating_font_size_set = (isset($rw_options->advanced) && isset($rw_options->advanced->font) && isset($rw_options->advanced->font->size));
        $rating_line_height_set = (isset($rw_options->advanced) && isset($rw_options->advanced->layout) && isset($rw_options->advanced->layout->lineHeight));
        
        $def_options = $DEFAULT_OPTIONS;
        if (isset($rw_options->theme) && $rw_options->theme !== "")
        {
            require_once(WP_RW__PLUGIN_DIR . "/themes/dir.php");
            
            global $RW_THEMES;
            
            if (!isset($rw_options->type)){
                $rw_options->type = isset($RW_THEMES["star"][$rw_options->theme]) ? "star" : "nero";
            }
            if (isset($RW_THEMES[$rw_options->type][$rw_options->theme]))
            {
                require(WP_RW__PLUGIN_DIR . "/themes/" . $RW_THEMES[$rw_options->type][$rw_options->theme]["file"]);

                $theme_font_size_set = (isset($theme["options"]->advanced) && isset($theme["options"]->advanced->font) && isset($theme["options"]->advanced->font->size));
                $theme_line_height_set = (isset($theme["options"]->advanced) && isset($theme["options"]->advanced->layout) && isset($theme["options"]->advanced->layout->lineHeight));

                // Enrich theme options with defaults.
                $def_options = rw_enrich_options1($theme["options"], $DEFAULT_OPTIONS);
            }
        }

        // Enrich rating options with calculated default options (with theme reference).
        $rw_options = rw_enrich_options1($rw_options, $def_options);

        // If font size and line height isn't explicitly specified on rating
        // options or rating's theme, updated theme correspondingly
        // to rating size. 
        if (isset($rw_options->size))
        {
            $SIZE = strtoupper($rw_options->size);
            if (!$rating_font_size_set && !$theme_font_size_set)
            {
                global $DEF_FONT_SIZE;
                if (!isset($rw_options->advanced)){ $rw_options->advanced = new stdClass(); }
                if (!isset($rw_options->advanced->font)){ $rw_options->advanced->font = new stdClass(); }
                $rw_options->advanced->font->size = $DEF_FONT_SIZE->$SIZE;
            }
            if (!$rating_line_height_set && !$theme_line_height_set)
            {
                global $DEF_LINE_HEIGHT;
                if (!isset($rw_options->advanced)){ $rw_options->advanced = new stdClass(); }
                if (!isset($rw_options->advanced->layout)){ $rw_options->advanced->layout = new stdClass(); }
                $rw_options->advanced->layout->lineHeight = $DEF_LINE_HEIGHT->$SIZE;
            }
        }
        
        $browser_info = array("browser" => "msie", "version" => "7.0");
        $rw_languages = $this->languages;
        
        $this->settings->rating_type = $selected_key;
        $this->settings->options = $rw_options;
        $this->settings->languages = $rw_languages;
        $this->settings->language_str = $rw_language_str;
        $this->settings->categories = $rw_categories;
        $this->settings->availability = $rw_availability_settings;
        $this->settings->visibility= $rw_visibility_settings;
        $this->settings->form_hidden_field_name = $rw_form_hidden_field_name;
        $this->settings->custom_settings_enabled = $rw_custom_settings_enabled;
        $this->settings->custom_settings = $rw_custom_settings;
        // Accumulated user ratings support.
        if ('users' === $selected_key && $this->IsBBPressInstalled())
            $this->settings->is_user_accumulated = $rw_is_user_accumulated;

?>
<div class="wrap rw-dir-ltr rw-wp-container">
    <h2 class="nav-tab-wrapper rw-nav-tab-wrapper">
    <?php foreach ($settings_data as $key => $settings) : ?>
        <a href="<?php echo esc_url(add_query_arg(array('rating' => $key, 'message' => false)));?>" class="nav-tab<?php if ($settings_data[$key] == $rw_current_settings) echo ' nav-tab-active' ?>"><?php _e($settings["tab"], WP_RW__ID);?></a>
    <?php endforeach; ?>
    </h2>
    
    <form method="post" action="">
        <div id="poststuff">       
            <div id="rw_wp_set">
                <?php rw_require_once_view('preview.php'); ?>
                <div class="has-sidebar has-right-sidebar">
                    <div class="has-sidebar-content">
                        <div class="postbox rw-body">
                            <?php
                                $enabled = isset($rw_align->ver);
                            ?>
                            <div style="padding: 10px;">
                                <label for="rw_show">
                                    <input id="rw_show" type="checkbox" name="rw_show" value="true"<?php if ($enabled) echo ' checked="checked"';?> onclick="RWM_WP.enable(this);" /> Enable for <?php echo $rw_current_settings["tab"];?>
                                </label>
                            <?php
                                if (true === $rw_current_settings["show_align"])
                                {
                            ?>
                                <div class="rw-post-rating-align"<?php if (!$enabled) echo ' style="display: none;"';?>>
                                <?php
                                    $vers = array("top", "bottom");
                                    $hors = array("left", "center", "right");
                                    
                                    foreach ($vers as $ver)
                                    {
                                ?>
                                    <div style="height: 89px; padding: 5px;">
                                <?php
                                        foreach ($hors as $hor)
                                        {
                                            $checked = false;
                                            if ($enabled){
                                                $checked = ($ver == $rw_align->ver && $hor == $rw_align->hor);
                                            }
                                ?>
                                        <div class="rw-ui-img-radio<?php if ($checked) echo ' rw-selected';?>">
                                            <i class="rw-ui-holder"><i class="rw-ui-sprite rw-ui-post-<?php echo $ver . $hor;?>"></i></i>
                                            <span><?php echo ucwords($ver) . ucwords($hor);?></span>
                                            <input type="radio" name="rw_align" value="<?php echo $ver . " " . $hor;?>"<?php if ($checked) echo ' checked="checked"';?> />
                                        </div>
                                <?php
                                        }
                                ?>
                                    </div>
                                <?php
                                    }
                                ?>
                                </div>
                            <?php
                                }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                    if ('users' === $selected_key)
                        rw_require_once_view('user_rating_type_options.php');
                
                    rw_require_once_view('options.php');
                    rw_require_once_view('availability_options.php');
                    rw_require_once_view('visibility_options.php');
                    
                    if ($is_blog_post)
                        rw_require_once_view('post_views_visibility.php');
                    
                    if ($item_with_category)
                        rw_require_once_view('categories_availability_options.php');
                    
                    rw_require_once_view('settings/frequency.php');
                    rw_require_once_view('powerusers.php'); 
                ?>
            </div>
            <div id="rw_wp_set_widgets">
                <?php 
                    if (!$this->_c4ca4238a0b923820dcc509a6f75849b())
                    {
                        // Show random.
                        if (0 == rand(0, 1))
                            rw_require_once_view('rich-snippets.php');
                        else
                            rw_require_once_view('upgrade.php');
                    }
                ?>
                <?php //rw_require_once_view('fb.php'); ?>
                <?php //rw_require_once_view('twitter.php'); ?>
            </div>
        </div>
    </form>
    <div class="rw-body">
    <?php rw_include_once_view('settings/custom_color.php'); ?>
    </div>
</div>

<?php
        
        // Store options if in save mode.
        if ($this->settings->IsSaveMode())
            $this->StoreOptions();
    }
    
    /* Posts/Pages & Comments Support
    ---------------------------------------------------------------------------------------------------------------*/
    var $post_align = false;
    var $post_class = "";
    var $comment_align = false;
    var $activity_align = array();
    var $forum_post_align = false;
    /**
    * This action invoked when WP starts looping over
    * the posts/pages. This function checks if Rating-Widgets
    * on posts/pages and/or comments are enabled, and saved
    * the settings alignment.
    */
    function rw_before_loop_start()
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_before_loop_start", $params); }

        // Check if shown on search results.
        if (is_search() && false === $this->GetOption(WP_RW__SHOW_ON_SEARCH))
            return;
            
        // Checks if category.
        if (is_category() && false === $this->GetOption(WP_RW__SHOW_ON_CATEGORY))
            return;

        // Checks if shown on archive.
        if (is_archive() && !is_category() && false === $this->GetOption(WP_RW__SHOW_ON_ARCHIVE))
            return;

        if ($this->InBuddyPressPage())
            return;
            
        if ($this->InBBPressPage())
            return;
        
        $comment_align = $this->GetRatingAlignByType(WP_RW__COMMENTS_ALIGN);
        $comment_enabled = (isset($comment_align) && isset($comment_align->hor));
        if (false !== $comment_align && !$this->IsHiddenRatingByType('comment'))
        {
            $this->comment_align = $comment_align;
            
            // Hook comment rating showup.
            add_action('comment_text', array(&$this, 'AddCommentRating'));
        }

        $postType = get_post_type();
        if (RWLogger::IsOn())
            RWLogger::Log("rw_before_loop_start", 'Post Type = ' . $postType);

        if (in_array($postType, array('forum', 'topic', 'reply')))
            return;
                    
        if (is_page())
        {
            // Get rating pages alignment.
            $post_align = $this->GetRatingAlignByType(WP_RW__PAGES_ALIGN);
            $post_class = "page";
        }
        else if (is_home())
        {
            // Get rating front posts alignment.
            $post_align = $this->GetRatingAlignByType(WP_RW__FRONT_POSTS_ALIGN);
            $post_class = "front-post";
        }
        else
        {
            // Get rating blog posts alignment.
            $post_align = $this->GetRatingAlignByType(WP_RW__BLOG_POSTS_ALIGN);
            $post_class = "blog-post";
        }
        
        if (false !== $post_align && !$this->IsHiddenRatingByType($post_class))
        {
            $this->post_align = $post_align;
            $this->post_class = $post_class;

            // Hook post rating showup.
            add_action('the_content', array(&$this, 'AddPostRating'));
//            add_action('the_title', array(&$this, "rw_add_title_metadata"));
//            add_action('post_class', array(&$this, "rw_add_article_metadata"));
            
            if (false !== $this->GetOption(WP_RW__SHOW_ON_EXCERPT))
                // Hook post excerpt rating showup.
                add_action('the_excerpt', array(&$this, 'AddPostRating'));
        }
        
        if (RWLogger::IsOn())
            RWLogger::LogDeparture("rw_before_loop_start");
    }
    
    static function IDsCollectionToArray(&$pIds)
    {
        if (null == $pIds || (is_string($pIds) && empty($pIds)))
            return array();

        if (!is_string($pIds) && is_array($pIds))
            return $pIds;
        
        $ids = explode(",", $pIds);
        $filtered = array();
        foreach ($ids as $id)
        {
            $id = trim($id);
            
            if (is_numeric($id))
                $filtered[] = $id;
        }
        
        return array_unique($filtered);
    }

    function rw_validate_category_availability($pId, $pClass)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_validate_category_availability", $params); }

        if (!isset($this->categories_list))
        {
            $this->categories_list = $this->GetOption(WP_RW__CATEGORIES_AVAILABILITY_SETTINGS);

            if (RWLogger::IsOn())
                RWLogger::Log("categories_list", var_export($this->categories_list, true));
        }
        
        if (!isset($this->categories_list->{$pClass}) ||
            empty($this->categories_list->{$pClass}))
            return true;
        
        // Alias.
        $categories = $this->categories_list->{$pClass};
        
        // Check if all categories.
        if (!is_array($categories) || in_array("-1", $categories))
            return true;
        
        // No category selected.
        if (count($categories) == 0)
            return false;
        
        // Get post categories.
        $post_categories = get_the_category($pId);
        
        $post_categories_ids = array();
        
        if (is_array($post_categories) && count($post_categories) > 0)
        {
            foreach ($post_categories as $category)
            {
                $post_categories_ids[] = $category->cat_ID;
            }
        }

        $common_categories = array_intersect($categories, $post_categories_ids);

        return (is_array($common_categories) && count($common_categories) > 0);
    }
        
    function rw_validate_visibility($pId, $pClasses = false)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_validate_visibility", $params); }
        
        if (!isset($this->_visibilityList))
        {
            $this->_visibilityList = $this->GetOption(WP_RW__VISIBILITY_SETTINGS);
            
            if (RWLogger::IsOn())
                RWLogger::Log("_visibilityList", var_export($this->_visibilityList, true));
        }
        
        if (is_string($pClasses))
        {
            $pClasses = array($pClasses);
        }
        else if (false === $pClasses)
        {
            foreach ($this->_visibilityList as $class => $val)
            {
                $pClasses[] = $class;
            }
        }
        
        foreach ($pClasses as $class)
        {
            if (!isset($this->_visibilityList->{$class}))
                continue;
            
            // Alias.
            $visibility_list = $this->_visibilityList->{$class};
            
            // All visible.
            if ($visibility_list->selected === WP_RW__VISIBILITY_ALL_VISIBLE)
                continue;

            $visibility_list->exclude = self::IDsCollectionToArray($visibility_list->exclude);
            $visibility_list->include = self::IDsCollectionToArray($visibility_list->include);

            if (($visibility_list->selected === WP_RW__VISIBILITY_EXCLUDE && in_array($pId, $visibility_list->exclude)) ||
                ($visibility_list->selected === WP_RW__VISIBILITY_INCLUDE && !in_array($pId, $visibility_list->include)))
            {
                return false;
            }
        }
        
        return true;
    }
    
    function AddToVisibility($pId, $pClasses, $pIsVisible = true)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("AddToVisibility", $params, true); }
        
        if (!isset($this->_visibilityList))
            $this->_visibilityList = $this->GetOption(WP_RW__VISIBILITY_SETTINGS);

        if (is_string($pClasses))
        {
            $pClasses = array($pClasses);
        }
        else if (!is_array($pClasses) || 0 == count($pClasses))
        {
            return;
        }
        
        foreach ($pClasses as $class)
        {
            if (RWLogger::IsOn()){ RWLogger::Log("AddToVisibility", "CurrentClass = ". $class); }
            
            if (!isset($this->_visibilityList->{$class}))
            {
                $this->_visibilityList->{$class} = new stdClass();
                $this->_visibilityList->{$class}->selected = WP_RW__VISIBILITY_ALL_VISIBLE;
            }
            
            $visibility_list = $this->_visibilityList->{$class};
            
            if (!isset($visibility_list->include) || empty($visibility_list->include))
                $visibility_list->include = array();
            
            $visibility_list->include = self::IDsCollectionToArray($visibility_list->include);
                
            if (!isset($visibility_list->exclude) || empty($visibility_list->exclude))
                $visibility_list->exclude = array();
                
            $visibility_list->exclude = self::IDsCollectionToArray($visibility_list->exclude);
                
            if ($visibility_list->selected == WP_RW__VISIBILITY_ALL_VISIBLE)
            {
                if (RWLogger::IsOn()){ RWLogger::Log("AddToVisibility", "Currently All-Visible for {$class}"); }
                
                if (true == $pIsVisible)
                {
                    // Already all visible so just ignore this.
                }
                else
                {
                    // If all visible, and selected to hide this post - exclude specified post/page.
                    $visibility_list->selected = WP_RW__VISIBILITY_EXCLUDE;
                    $visibility_list->exclude[] = $pId;
                }
            }
            else
            {
                // If not all visible, move post id from one list to another (exclude/include).

                if (RWLogger::IsOn()){ RWLogger::Log("AddToVisibility", "Currently NOT All-Visible for {$class}"); }
                
                $remove_from = ($pIsVisible ? "exclude" : "include");
                $add_to = ($pIsVisible ? "include" : "exclude");

                if (RWLogger::IsOn()){ RWLogger::Log("AddToVisibility", "Remove {$pId} from {$class}'s " . strtoupper(($pIsVisible ? "exclude" : "include")) . "list."); }
                if (RWLogger::IsOn()){ RWLogger::Log("AddToVisibility", "Add {$pId} to {$class}'s " . strtoupper((!$pIsVisible ? "exclude" : "include")) . "list."); }

                if (!in_array($pId, $visibility_list->{$add_to}))
                    // Add to include list.
                    $visibility_list->{$add_to}[] = $pId;

                if (($key = array_search($pId, $visibility_list->{$remove_from})) !== false)
                    // Remove from exclude list.
                    $remove_from = array_splice($visibility_list->{$remove_from}, $key, 1);
                    
                if (WP_RW__VISIBILITY_EXCLUDE == $visibility_list->selected && 0 === count($visibility_list->exclude))
                    $visibility_list->selected = WP_RW__VISIBILITY_ALL_VISIBLE;
            }
        }
        
        if (RWLogger::IsOn()){ RWLogger::LogDeparture("AddToVisibility"); }
    }
    
    var $is_user_logged_in;
    function rw_validate_availability($pClass)
    {
        if (!isset($this->is_user_logged_in))
        {
            // Check if user logged in for availability check.
            $this->is_user_logged_in = is_user_logged_in();

            $this->availability_list = $this->GetOption(WP_RW__AVAILABILITY_SETTINGS);
        }
        
        if (true === $this->is_user_logged_in ||
            !isset($this->availability_list->{$pClass}))
        {
            return WP_RW__AVAILABILITY_ACTIVE;
        }
        
        return $this->availability_list->{$pClass};
    }
    
    function GetCustomSettings($pClass)
    {
        $this->custom_settings_enabled_list = $this->GetOption(WP_RW__CUSTOM_SETTINGS_ENABLED);
        
        if (!isset($this->custom_settings_enabled_list->{$pClass}) || false === $this->custom_settings_enabled_list->{$pClass})
            return '';
        
        $this->custom_settings_list = $this->GetOption(WP_RW__CUSTOM_SETTINGS);
        
        return isset($this->custom_settings_list->{$pClass}) ? stripslashes($this->custom_settings_list->{$pClass}) : '';
    }
    
    function IsVisibleRating($pElementID, $pClass, $pValidateCategory = true, $pValidateVisibility = true)
    {
        // Check if post category is selected.
        if ($pValidateCategory && false === $this->rw_validate_category_availability($pElementID, $pClass))
            return false;
        // Checks if item isn't specificaly excluded.
        if ($pValidateVisibility && false === $this->rw_validate_visibility($pElementID, $pClass))
            return false;
            
        return true;
    }
    
    function IsVisibleCommentRating($pComment)
    {
        /**
        * Check if comment category is selected.
        * 
        *   NOTE: 
        *       $pComment->comment_post_ID IS NOT A MISTAKE
        *       We transfer the comment parent post id because the availability
        *       method loads the element categories by get_the_category() which only
        *       works on post ids.
        */
        if (false === $this->rw_validate_category_availability($pComment->comment_post_ID, 'comment'))
            return false;
        // Checks if item isn't specificaly excluded.
        if (false === $this->rw_validate_visibility($pComment->comment_ID, 'comment'))
            return false;
            
        return true;
    }

    function GetPostImage($pPost, $pExpiration = false)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("GetPostImage", $params); }

        $cacheKey = 'rw_post_thumb_' . $pPost->ID;
        $img = false;
        if (false !== $pExpiration)
        {
            // Try to get cached item.
            $img = get_transient($cacheKey);
            
            if (RWLogger::IsOn())
                RWLogger::Log('IS_CACHED', (false !== $img) ? 'true' : 'false');
        }
        
        if (false === $img)
        {
            if (function_exists('has_post_thumbnail') && has_post_thumbnail($pPost->ID))
            {
                $img = wp_get_attachment_image_src(get_post_thumbnail_id($pPost->ID), 'single-post-thumbnail');
                
                if (RWLogger::IsOn())
                    RWLogger::Log('GetPostImage', 'Featured Image = ' . $img[0]);
                    
                $img = $img[0];
            }
            else
            {
                ob_start();
                ob_end_clean();

                $images = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $pPost->post_content, $matches);

                if($images > 0)
                {
                    if (RWLogger::IsOn())
                        RWLogger::Log('GetPostImage', 'Extracted post image = ' . $matches[1][0]);
                        
                    // Return first image out of post's content.
                    $img = $matches[1][0];
                }
                else
                {
                    if (RWLogger::IsOn())
                        RWLogger::Log('GetPostImage', 'No post image');

                    $img = '';
                }
            }

            if (false !== $pExpiration && !empty($cacheKey))
                set_transient($cacheKey, $img, $pExpiration);
        }
            
        return !empty($img) ? $img : false;
    }
    
    /**
    * If Rating-Widget enabled for Posts, attach it
    * html container to the post content.
    * 
    * @param {string} $content
    */
    function AddPostRating($content)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("AddPostRating", $params); }
        
        if ($this->InBuddyPressPage())
        {
            if (RWLogger::IsOn())
                RWLogger::LogDeparture("AddPostRating");
                
            return;
        }
        
        global $post;

        $ratingHtml = $this->EmbedRatingIfVisibleByPost($post, $this->post_class, true, $this->post_align->hor, false);
        
        return ('top' === $this->post_align->ver) ?
                $ratingHtml . $content :
                $content . $ratingHtml;
    }
    
    /**
    * If Rating-Widget enabled for Comments, attach it
    * html container to the comment content.
    * 
    * @param {string} $content
    */
    function AddCommentRating($content)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence('AddCommentRating', $params); }
        
        global $comment;

        if (!$this->IsVisibleCommentRating($comment))
            return $content;

        $ratingHtml = $this->EmbedRatingByComment($comment, 'comment', $this->comment_align->hor);
        
        return ('top' === $this->comment_align->ver) ?
                $ratingHtml . $content :
                $content . $ratingHtml;
    }
    
    /**
    * Return rating's html.
    * 
    * @param {serial} $pUrid User rating id.
    * @param {string} $pElementClass Rating element class.
    * 
    * @version 1.3.3
    * 
    */
    private function GetRatingHtml($pUrid, $pElementClass, $pAddSchema = false, $pTitle = "", $pOptions = array())
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("GetRatingHtml", $params); }
        
        $ratingData = '';
        foreach ($pOptions as $key => $val)
        {
            if (is_string($val) && '' !== trim($val))
                $ratingData .= ' data-' . $key . '="' . esc_attr(trim($val)) . '"';
        }
        
        $rating_html = '<div class="rw-ui-container rw-class-' . $pElementClass . ' rw-urid-' . $pUrid . '"' . $ratingData;
        
        eval(base64_decode('DQogICAgICAgIGlmICh0cnVlID09PSAkcEFkZFNjaGVtYSAmJiAnZnJvbnQtcG9zdCcgIT09ICR0aGlzLT5wb3N0X2NsYXNzKQ0KICAgICAgICB7DQogICAgICAgICAgICAkZGF0YSA9ICR0aGlzLT5HZXRSYXRpbmdEYXRhQnlSYXRpbmdJRCgkcFVyaWQsIDIpOw0KICAgICAgICAgICAgDQogICAgICAgICAgICBpZiAoZmFsc2UgIT09ICRkYXRhICYmICRkYXRhWyd2b3RlcyddID4gMCkNCiAgICAgICAgICAgIHsNCiAgICAgICAgICAgICAgICAgICAgJHRpdGxlID0gbWJfY29udmVydF90b191dGY4KHRyaW0oJHBUaXRsZSkpOw0KICAgICAgICAgICAgICAgICAgICAkcmF0aW5nX2h0bWwgLj0gJyBpdGVtc2NvcGUgaXRlbXByb3A9ImJsb2dQb3N0IiBpdGVtdHlwZT0iaHR0cDovL3NjaGVtYS5vcmcvQmxvZ1Bvc3RpbmciPg0KICAgIDxzcGFuIGl0ZW1wcm9wPSJoZWFkbGluZSIgc3R5bGU9InBvc2l0aW9uOiBmaXhlZDsgdG9wOiAxMDAlOyI+JyAuIGVzY19odG1sKCRwVGl0bGUpIC4gJzwvc3Bhbj4NCiAgICA8ZGl2IGl0ZW1wcm9wPSJhZ2dyZWdhdGVSYXRpbmciIGl0ZW1zY29wZSBpdGVtdHlwZT0iaHR0cDovL3NjaGVtYS5vcmcvQWdncmVnYXRlUmF0aW5nIj4NCiAgICAgICAgPG1ldGEgaXRlbXByb3A9IndvcnN0UmF0aW5nIiBjb250ZW50PSIwIiAvPg0KICAgICAgICA8bWV0YSBpdGVtcHJvcD0iYmVzdFJhdGluZyIgY29udGVudD0iNSIgLz4NCiAgICAgICAgPG1ldGEgaXRlbXByb3A9InJhdGluZ1ZhbHVlIiBjb250ZW50PSInIC4gJGRhdGFbJ3JhdGUnXSAuICciIC8+DQogICAgICAgIDxtZXRhIGl0ZW1wcm9wPSJyYXRpbmdDb3VudCIgY29udGVudD0iJyAuICRkYXRhWyd2b3RlcyddIC4gJyIgLz4NCiAgICA8L2Rpdic7DQogICAgICAgICAgICB9DQogICAgICAgIH0NCiAgICAgICAg'));
        
        $rating_html .= '></div>';
        
        return $rating_html;
    }
    
    function InBuddyPressPage()
    {
        if (!$this->IsBuddyPressInstalled())
            return;
        
        if (!isset($this->_inBuddyPress))
        {
            $this->_inBuddyPress = false;
            
            if (function_exists('bp_is_blog_page'))
                $this->_inBuddyPress =  !bp_is_blog_page();
            /*if (function_exists('bp_is_activity_front_page'))
                $this->_inBuddyPress =  $this->_inBuddyPress || bp_is_blog_page();
            if (function_exists('bp_is_current_component'))
                $this->_inBuddyPress =  $this->_inBuddyPress || bp_is_current_component($bp->current_component);*/
            
            if (RWLogger::IsOn())
                RWLogger::Log("InBuddyPressPage", ($this->_inBuddyPress ? 'TRUE' : 'FALSE'));
        }

        return $this->_inBuddyPress;
    }
    
    function InBBPressPage()
    {
        if (!$this->IsBBPressInstalled())
            return false;
            
        if (!isset($this->_inBBPress))
        {
            $this->_inBBPress = false;
            if (function_exists('bbp_is_forum'))
            {
//                $this->_inBBPress = $this->_inBBPress || ('' !== bb_get_location());
                $this->_inBBPress = $this->_inBBPress || bbp_is_forum(get_the_ID());
                $this->_inBBPress = $this->_inBBPress || bbp_is_single_user();
//                bbp_is_user
//                $this->_inBBPress = $this->_inBBPress || bb_is_feed();
            }

            if (RWLogger::IsOn())
                RWLogger::Log("InBBPressPage", ($this->_inBuddyPress ? 'TRUE' : 'FALSE'));
        }        

        return $this->_inBBPress;
    }
    
    function IsBBPressInstalled()
    {
        if (!defined('WP_RW__BBP_INSTALLED'))
            define('WP_RW__BBP_INSTALLED', false);
        
        return WP_RW__BBP_INSTALLED;// && (!function_exists('is_plugin_active') || is_plugin_active(WP_RW__BP_CORE_FILE));
    }
    
    function IsBuddyPressInstalled()
    {
        return (defined('WP_RW__BP_INSTALLED') && WP_RW__BP_INSTALLED && (!function_exists('is_plugin_active') || is_plugin_active(WP_RW__BP_CORE_FILE)));
    }
    
    /* BuddyPress Support Actions
    ---------------------------------------------------------------------------------------------------------------*/
    function BuddyPressBeforeActivityLoop($has_activities)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("BuddyPressBeforeActivityLoop", $params); }
        
        $this->_inBuddyPress = true;
        
        /**
        * New BuddyPress versions activity is loaded as part of regular post,
        * thus we want to remove standard post rating because it's useless.
        */
        remove_action('the_content', array(&$this, 'AddPostRating'));
        remove_action('the_excerpt', array(&$this, 'AddPostRating'));
        
        if (!$has_activities)
            return false;
        
        $items = array(
            "activity-update" => array(
                "align_key" => WP_RW__ACTIVITY_UPDATES_ALIGN,
                "enabled" => false,
            ),
            "activity-comment" => array(
                "align_key" => WP_RW__ACTIVITY_COMMENTS_ALIGN,
                "enabled" => false,
            ),
            "new-blog-post" => array(
                "align_key" => WP_RW__ACTIVITY_BLOG_POSTS_ALIGN,
                "enabled" => false,
            ),
            "new-blog-comment" => array(
                "align_key" => WP_RW__ACTIVITY_BLOG_COMMENTS_ALIGN,
                "enabled" => false,
            ),
            /*"new-forum-topic" => array(
                "align_key" => WP_RW__ACTIVITY_FORUM_TOPICS_ALIGN,
                "enabled" => false,
            ),*/
            "new-forum-post" => array(
                "align_key" => WP_RW__ACTIVITY_FORUM_POSTS_ALIGN,
                "enabled" => false,
            ),
        );
        
        $ver_top = false;
        $ver_bottom = false;
        foreach ($items as $key => &$item)
        {
            $align = $this->GetRatingAlignByType($item["align_key"]);
            $item["enabled"] = (false !== $align);
            
            if (!$item["enabled"] || $this->IsHiddenRatingByType($key))
                continue;
        
            $this->activity_align[$key] = $align;
            
            if ($align->ver === "top")
                $ver_top = true;
            else
                $ver_bottom = true;
            
        }
        
        if ($ver_top)
            // Hook activity TOP rating.
            add_filter("bp_get_activity_action", array(&$this, "rw_display_activity_rating_top"));
        if ($ver_bottom)
            // Hook activity BOTTOM rating.
            add_action("bp_activity_entry_meta", array(&$this, "rw_display_activity_rating_bottom"));
        
        if (true === $items["activity-comment"]["enabled"])
            // Hook activity-comment rating showup.
            add_filter("bp_get_activity_content", array(&$this, "rw_display_activity_comment_rating"));
        
        return true;
    }

    private function GetBuddyPressRating($ver, $horAlign = true)
    {
        RWLogger::LogEnterence("GetBuddyPressRating");
        
        global $activities_template;
        
        // Set current activity-comment to current activity update (recursive comments).
        $this->current_comment = $activities_template->activity;
        
        $rclass = str_replace("_", "-", bp_get_activity_type());
        
        $is_forum_topic = ($rclass === "new-forum-topic");

        if ($is_forum_topic && !$this->IsBBPressInstalled())
            return false;

        if (!in_array($rclass, array('forum-post', 'forum-reply', 'new-forum-post', 'user-forum-post', 'user', 'activity-update', 'user-activity-update', 'activity-comment', 'user-activity-comment')))
            // If unknown activity type, change it to activity update.
            $rclass = 'activity-update';

        if ($is_forum_topic)
            $rclass = "new-forum-post";

        // Check if item rating is top positioned.
        if (!isset($this->activity_align[$rclass]) || $ver !== $this->activity_align[$rclass]->ver)
            return false;
        
        // Get item id.
        $item_id = ("activity-update" === $rclass || "activity-comment" === $rclass) ?
                    bp_get_activity_id() :
                    bp_get_activity_secondary_item_id();
        
        if ($is_forum_topic)
        {
            // If forum topic, then we must extract post id
            // from forum posts table, because secondary_item_id holds
            // topic id.
            if (function_exists("bb_get_first_post"))
            {
                $post = bb_get_first_post($item_id);
            }
            else
            {
                // Extract post id straight from the BB DB.
                    global $bb_table_prefix;
                    // Load bbPress config file.
                    @include_once(WP_RW__BBP_CONFIG_LOCATION);
                    
                    // Failed loading config file.
                    if (!defined("BBDB_NAME"))
                        return false;
                    
                    $connection = null;
                    if (!$connection = mysql_connect(BBDB_HOST, BBDB_USER, BBDB_PASSWORD, true)){ return false; }
                    if (!mysql_selectdb(BBDB_NAME, $connection)){ return false; }
                    $results = mysql_query("SELECT * FROM {$bb_table_prefix}posts WHERE topic_id={$item_id} AND post_position=1", $connection);
                    $post = mysql_fetch_object($results);
            }
            
            if (!isset($post->post_id) && empty($post->post_id))
                return false;
            
            $item_id = $post->post_id;
        }
        
        // If the item is post, queue rating with post title.
        $title = ("new-blog-post" === $rclass) ?
                  get_the_title($item_id) :
                  bp_get_activity_content_body();// $activities_template->activity->content;
        
        $options = array();

        $owner_id = bp_get_activity_user_id();
        
        // Add accumulator id if user accumulated rating.
        if ($this->IsUserAccumulatedRating())
            $options['uarid'] = $this->_getUserRatingGuid($owner_id);
                                 
        return $this->EmbedRatingIfVisible(
            $item_id,
            $owner_id,
            strip_tags($title),
            bp_activity_get_permalink(bp_get_activity_id()),
            $rclass,
            false,
            ($horAlign ? $this->activity_align[$rclass]->hor : false),
            false,
            $options,
            false   // Don't validate category - there's no category for bp items
        );
        /*
        // Queue activity rating.
        $this->QueueRatingData($urid, strip_tags($title), bp_activity_get_permalink($activities_template->activity->id), $rclass);

        // Return rating html container.
        return '<div class="rw-ui-container rw-class-' . $rclass . ' rw-urid-' . $urid . '"></div>';*/
    }
    
    // Activity item top rating.
    function rw_display_activity_rating_top($action)
    {
        RWLogger::LogEnterence("rw_display_activity_rating_top");
        
        $rating_html = $this->GetBuddyPressRating("top");
        
        return $action . ((false === $rating_html) ? '' : $rating_html);
    }
    
    // Activity item bottom rating.
    function rw_display_activity_rating_bottom($id = "", $type = "")
    {
        RWLogger::LogEnterence("rw_display_activity_rating_bottom");
        
        $rating_html = $this->GetBuddyPressRating("bottom", false);

        if (false !== $rating_html)
            // Echo rating html container on bottom actions line.
            echo $rating_html;
    }

    /*var $current_comment;
    function rw_get_current_activity_comment($action)
    {
        global $activities_template;
        
        // Set current activity-comment to current activity update (recursive comments).
        $this->current_comment = $activities_template->activity;
        
        return $action;
    }*/

    // Activity-comment.
    function rw_display_activity_comment_rating($comment_content)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_display_activity_comment_rating", $params); }
        
        if (!isset($this->current_comment) || null === $this->current_comment)
        {
            if (RWLogger::IsOn()){ RWLogger::Log("rw_display_activity_comment_rating", "Current comment is not set."); }
            
            return $comment_content;
        }
        
        // Find current comment.
        while (!$this->current_comment->children || false === current($this->current_comment->children))
        {
            $this->current_comment = $this->current_comment->parent;
            next($this->current_comment->children);
        }
        
        $parent = $this->current_comment;
        $this->current_comment = current($this->current_comment->children);
        $this->current_comment->parent = $parent;
        
        /*
        // Check if comment rating isn't specifically excluded.
        if (false === $this->rw_validate_visibility($this->current_comment->id, "activity-comment"))
            return $comment_content;

        // Get activity comment user-rating-id.
        $comment_urid = $this->_getActivityRatingGuid($this->current_comment->id);
        
        // Queue activity-comment rating.
        $this->QueueRatingData($comment_urid, strip_tags($this->current_comment->content), bp_activity_get_permalink($this->current_comment->id), "activity-comment");
        
        $rw = '<div class="rw-' . $this->activity_align["activity-comment"]->hor . '"><div class="rw-ui-container rw-class-activity-comment rw-urid-' . $comment_urid . '"></div></div><p></p>';
        */
        
        $options = array();

        // Add accumulator id if user accumulated rating.
        if ($this->IsUserAccumulatedRating())
            $options['uarid'] = $this->_getUserRatingGuid($this->current_comment->user_id);
        
        $rw = $this->EmbedRatingIfVisible(
            $this->current_comment->id,
            $this->current_comment->user_id,
            strip_tags($this->current_comment->content),
            bp_activity_get_permalink($this->current_comment->id),
            'activity-comment',
            false,
            $this->activity_align['activity-comment']->hor,
            false,
            $options,
            false
        );
        
        // Attach rating html container.
        return ($this->activity_align["activity-comment"]->ver == "top") ?
                $rw . $comment_content :
                $comment_content . $rw;
    }

    private function GetRatingAlignByType($pType)
    {
        $align = $this->GetOption($pType);
        
        return (isset($align) && isset($align->hor)) ? $align : false;
    }
    
    private function IsHiddenRatingByType($pType)
    {
        return (WP_RW__AVAILABILITY_HIDDEN === $this->rw_validate_availability($pType));
    }
    
    // User profile.
    function rw_display_user_profile_rating()
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_display_user_profile_rating", $params); }
        
        $align = $this->GetRatingAlignByType(WP_RW__USERS_ALIGN);
        
        if (false === $align || $this->IsHiddenRatingByType('user'))
            return;
            
        $ratingHtml = $this->EmbedRatingIfVisibleByUser(buddypress()->displayed_user, 'user', 'display: block;');
        
        echo $ratingHtml;
    }
    
/* BuddyPress && bbPress.
--------------------------------------------------------------------------------------------*/
    function SetupBuddyPress()
    {
        if (RWLogger::IsOn())
            RWLogger::LogEnterence("SetupBuddyPress");

        if (function_exists('bp_activity_get_specific'))
        {
            // BuddyPress earlier than v.1.5
            $this->InitBuddyPress();
        }
        else
        {
            // BuddyPress v.1.5 and latter.
            add_action("bp_include", array(&$this, "InitBuddyPress"));
        }
    }
    
    function SetupBBPress()
    {
        eval(base64_decode('DQogICAgICAgIGlmIChSV0xvZ2dlcjo6SXNPbigpKQ0KICAgICAgICAgICAgUldMb2dnZXI6OkxvZ0VudGVyZW5jZSgiU2V0dXBCQlByZXNzIik7DQoNCiAgICAgICAgaWYgKCEkdGhpcy0+X2VjY2JjODdlNGI1Y2UyZmUyODMwOGZkOWYyYTdiYWYzKCkpDQogICAgICAgIHsNCiAgICAgICAgICAgIGRlZmluZSgnV1BfUldfX0JCUF9JTlNUQUxMRUQnLCBmYWxzZSk7DQogICAgICAgIH0NCiAgICAgICAgZWxzZQ0KICAgICAgICB7DQogICAgICAgICAgICBkZWZpbmUoJ1dQX1JXX19CQlBfQ09ORklHX0xPQ0FUSU9OJywgZ2V0X3NpdGVfb3B0aW9uKCdiYi1jb25maWctbG9jYXRpb24nLCAnJykpOw0KICAgICAgICAgICAgDQogICAgICAgICAgICBpZiAoIWRlZmluZWQoJ1dQX1JXX19CQlBfSU5TVEFMTEVEJykpDQogICAgICAgICAgICB7DQogICAgICAgICAgICAgICAgaWYgKCcnICE9PSBXUF9SV19fQkJQX0NPTkZJR19MT0NBVElPTikNCiAgICAgICAgICAgICAgICAgICAgZGVmaW5lKCdXUF9SV19fQkJQX0lOU1RBTExFRCcsIHRydWUpOw0KICAgICAgICAgICAgICAgIGVsc2UNCiAgICAgICAgICAgICAgICB7DQogICAgICAgICAgICAgICAgICAgIGluY2x1ZGVfb25jZShBQlNQQVRIIC4gJ3dwLWFkbWluL2luY2x1ZGVzL3BsdWdpbi5waHAnKTsNCiAgICAgICAgICAgICAgICAgICAgZGVmaW5lKCdXUF9SV19fQkJQX0lOU1RBTExFRCcsIGlzX3BsdWdpbl9hY3RpdmUoJ2JicHJlc3MvYmJwcmVzcy5waHAnKSk7DQogICAgICAgICAgICAgICAgfQ0KICAgICAgICAgICAgfQ0KICAgICAgICB9DQoNCiAgICAgICAgaWYgKFdQX1JXX19CQlBfSU5TVEFMTEVEICYmICFpc19hZG1pbigpIC8qICYmIGlzX2JicHJlc3MoKSovKQ0KICAgICAgICAgICAgJHRoaXMtPlNldHVwQkJQcmVzc0FjdGlvbnMoKTsNCiAgICAgICAg'));
    }
    
    function SetupBBPressActions()
    {
        add_filter('bbp_has_replies', array(&$this, 'SetupBBPressTopicActions'));        
        add_action('bbp_template_after_user_profile', array(&$this, 'AddBBPressUserProfileRating'));
    }
    
    function SetupBBPressTopicActions($has_replies)
    {
        if (RWLogger::IsOn())
            RWLogger::LogEnterence("SetupBBPressActions");

        $align = $this->GetRatingAlignByType(WP_RW__FORUM_POSTS_ALIGN);

        // If set to hidden, break.
        if (false !== $align && !$this->IsHiddenRatingByType('forum-post'))
        {
            $this->forum_post_align = $align;
            
            if ('bottom' === $align->ver)
            {
                // If verticaly bottom aligned.
                add_filter('bbp_get_reply_content', array(&$this, 'AddBBPressBottomRating'));
            }
            else
            {
                // If vertically top aligned.
                if ('center' === $align->hor)
                    // If horizontal align is center.
                    add_action('bbp_theme_after_reply_admin_links', array(&$this, 'AddBBPressTopCenterRating'));
                else
                    // If horizontal align is left or right.
                    add_action('bbp_theme_before_reply_admin_links', array(&$this, 'AddBBPressTopLeftOrRightRating'));
        //        add_filter('bbp_get_topic_content', array(&$this, 'bbp_get_reply_content'));
            }
        }
                
        if (false !== $this->GetRatingAlignByType(WP_RW__USERS_ALIGN) && !$this->IsHiddenRatingByType('user'))
            // Add user ratings into forum threads.
            add_filter('bbp_get_reply_author_link', array(&$this, 'AddBBPressForumThreadUserRating'));
        
        return $has_replies;
    }
    
    function AddBBPressUserProfileRating()
    {
        if ($this->IsHiddenRatingByType('user'))
            return;

        echo $this->EmbedRatingIfVisibleByUser(bbpress()->displayed_user, 'user');
    }
    
    function AddBBPressForumThreadUserRating($author_link)
    {
        global $post;
        
        $reply_id = bbp_get_reply_id($post->ID);
        
        if (bbp_is_reply_anonymous($reply_id))
            return $author_link;
        
        $options = array('show-info' => 'false');
        // If accumulated user rating, then make sure it can not be directly rated.
        if ($this->IsUserAccumulatedRating())
        {
            $options['read-only'] = 'true';
            $options['show-report'] = 'false';
        }
        
        $author_id = bbp_get_reply_author_id($reply_id);
        
        return $author_link . $this->EmbedRatingIfVisible(
            $author_id,
            $author_id,
            bbp_get_reply_author_display_name($reply_id),
            bbp_get_reply_author_url($reply_id),
            'user',
            false,
            false,
            false,
            $options
        );
    }
    
    /**
    * Add bbPress bottom ratings.
    * Invoked on bbp_get_reply_content
    * 
    * @param mixed $content
    * @param mixed $reply_id
    */
    function AddBBPressBottomRating($content, $reply_id = 0)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence('AddBBPressBottomRating', $params); }
        
        $forum_item = bbp_get_reply(bbp_get_reply_id());
        
        $is_reply = is_object($forum_item);
        
        if (!$is_reply)
            $forum_item = bbp_get_topic(bbp_get_topic_id());
        
        $class = ($is_reply ? 'forum-reply' : 'forum-post');
        
        if (RWLogger::IsOn())
            RWLogger::Log('AddBBPressBottomRating', $class . ': ' . var_export($forum_item, true));
        
        $ratingHtml = $this->EmbedRatingIfVisibleByPost($forum_item, $class, false, $this->forum_post_align->hor);
        
        return $content . $ratingHtml;
    }

    /**
    * Add bbPress top center rating - just before metadata.
    * Invoked on bbp_theme_after_reply_admin_links
    */
    function AddBBPressTopCenterRating()
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence('AddBBPressTopCenterRating', $params); }
        
        $forum_item = bbp_get_reply(bbp_get_reply_id());
        
        $is_reply = is_object($forum_item);
        
        if (!$is_reply)
            $forum_item = bbp_get_topic(bbp_get_topic_id());
        
        $class = ($is_reply ? 'forum-reply' : 'forum-post');
        
        if (RWLogger::IsOn())
            RWLogger::Log('AddBBPressTopCenterRating', $class . ': ' . var_export($forum_item, true));

        $ratingHtml = $this->EmbedRatingIfVisibleByPost($forum_item, $class, false, 'fright', 'display: inline; margin-right: 10px;');
        
        echo $ratingHtml;
    }
    
    /**
    * Add bbPress top left & right ratings.
    * Invoked on bbp_theme_before_reply_admin_links.
    */
    function AddBBPressTopLeftOrRightRating()
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence('AddBBPressTopLeftOrRightRating', $params); }
        
        $forum_item = bbp_get_reply(bbp_get_reply_id());
        
        $is_reply = is_object($forum_item);
        
        if (!$is_reply)
            $forum_item = bbp_get_topic(bbp_get_topic_id());
        
        $class = ($is_reply ? 'forum-reply' : 'forum-post');
        
        if (RWLogger::IsOn())
            RWLogger::Log('AddBBPressTopLeftOrRightRating', $class . ': ' . var_export($forum_item, true));

        $ratingHtml = $this->EmbedRatingIfVisibleByPost($forum_item, $class, false, 'f' . $this->forum_post_align->hor, 'display: inline; margin-' . ('left' === $this->forum_post_align->hor ? 'right' : 'left') . ': 10px;');
        
        echo $ratingHtml;
    }

    function InitBuddyPress()
    {
        if (RWLogger::IsOn())
            RWLogger::LogEnterence("InitBuddyPress");

        if (!defined('WP_RW__BP_INSTALLED'))
            define('WP_RW__BP_INSTALLED', true);
        
        if (!is_admin())
        {
            // Activity page.
            add_action("bp_has_activities", array(&$this, "BuddyPressBeforeActivityLoop"));
            
            // Forum topic page.
            add_filter("bp_has_topic_posts", array(&$this, "rw_before_forum_loop"));
            
            // User profile page.
            add_action("bp_before_member_header_meta", array(&$this, "rw_display_user_profile_rating"));
        }        
    }

    var $forum_align = array();
    function rw_before_forum_loop($has_posts)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_before_forum_loop", $params); }

        if (!$has_posts){ return false; }
        
        $items = array(
            /*"forum-topic" => array(
                "align_key" => WP_RW__FORUM_TOPICS_ALIGN,
                "enabled" => false,
            ),*/
            "forum-post" => array(
                "align_key" => WP_RW__FORUM_POSTS_ALIGN,
                "enabled" => false,
            ),
        );
        
        $hook = false;
        foreach ($items as $key => &$item)
        {
            $align = $this->GetRatingAlignByType($item["align_key"]);
            $item["enabled"] = (false !== $align);
            
            if (!$item["enabled"] || $this->IsHiddenRatingByType($key))
                continue;

            $this->forum_align[$key] = $align;
            $hook = true;
        }

        if ($hook)
            // Hook forum posts.
            add_filter("bp_get_the_topic_post_content", array(&$this, "rw_display_forum_post_rating"));

        return true;
    }
    
    /**
    * Add bbPress forum post ratings. This method is for old versions of bbPress & BuddyPress bundle.
    * 
    * @param mixed $content
    */
    function rw_display_forum_post_rating($content)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_display_forum_post_rating", $params); }
        
        $rclass = "forum-post";

        // Check if item rating is top positioned.
        if (!isset($this->forum_align[$rclass]))
            return $content;
        
        $post_id = bp_get_the_topic_post_id();
        
        /*
        // Validate that item isn't explicitly excluded.
        if (false === $this->rw_validate_visibility($post_id, $rclass))
            return $content;

        // Get forum-post user-rating-id.
        $post_urid = $this->_getForumPostRatingGuid($post_id);
        
        // Queue activity-comment rating.
        $this->QueueRatingData($post_urid, strip_tags($topic_template->post->post_text), bp_get_the_topic_permalink() . "#post-" . $post_id, $rclass);
        
        $rw = '<div class="rw-' . $this->forum_align[$rclass]->hor . '"><div class="rw-ui-container rw-class-' . $rclass . ' rw-urid-' . $post_urid . '"></div></div>';
        */
        
        global $topic_template;
            
        // Add accumulator id if user accumulated rating.
        if ($this->IsUserAccumulatedRating())
            $options['uarid'] = $this->_getUserRatingGuid($topic_template->post->poster_id);

        $rw = $this->EmbedRatingIfVisible(
            $post_id,
            $topic_template->post->poster_id, 
            strip_tags(bp_get_the_topic_post_content()),
            bp_get_the_topic_permalink() . "#post-" . $post_id,
            $rclass,
            false,
            $this->forum_align[$rclass]->hor,
            false,
            $options,
            false);
        
        
        // Attach rating html container.
        return ($this->forum_align[$rclass]->ver == "top") ?
                $rw . $content :
                $content . $rw;
    }
    
    /* Final Rating-Widget JS attach (before </body>)
    ---------------------------------------------------------------------------------------------------------------*/
    function rw_attach_rating_js($pElement = false)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("rw_attach_rating_js", $params); }

        $rw_settings = array(
            "blog-post" => array("options" => WP_RW__BLOG_POSTS_OPTIONS),
            "front-post" => array("options" => WP_RW__FRONT_POSTS_OPTIONS),
            "comment" => array("options" => WP_RW__COMMENTS_OPTIONS),
            "page" => array("options" => WP_RW__PAGES_OPTIONS),

            "activity-update" => array("options" => WP_RW__ACTIVITY_UPDATES_OPTIONS),
            "activity-comment" => array("options" => WP_RW__ACTIVITY_COMMENTS_OPTIONS),
//            "new-forum-topic" => array("options" => WP_RW__ACTIVITY_FORUM_TOPICS_OPTIONS),
            "new-forum-post" => array("options" => WP_RW__ACTIVITY_FORUM_POSTS_OPTIONS),
            "new-blog-post" => array("options" => WP_RW__ACTIVITY_BLOG_POSTS_OPTIONS),
            "new-blog-comment" => array("options" => WP_RW__ACTIVITY_BLOG_COMMENTS_OPTIONS),
            
//            "forum-topic" => array("options" => WP_RW__ACTIVITY_FORUM_TOPICS_OPTIONS),
            "forum-post" => array("options" => WP_RW__ACTIVITY_FORUM_POSTS_OPTIONS),
            "forum-reply" => array("options" => WP_RW__ACTIVITY_FORUM_POSTS_OPTIONS),

            "user" => array("options" => WP_RW__USERS_OPTIONS),
            "user-post" => array("options" => WP_RW__USERS_POSTS_OPTIONS),
            "user-page" => array("options" => WP_RW__USERS_PAGES_OPTIONS),
            "user-comment" => array("options" => WP_RW__USERS_COMMENTS_OPTIONS),
            "user-activity-update" => array("options" => WP_RW__USERS_ACTIVITY_UPDATES_OPTIONS),
            "user-activity-comment" => array("options" => WP_RW__USERS_ACTIVITY_COMMENTS_OPTIONS),
            "user-forum-post" => array("options" => WP_RW__USERS_FORUM_POSTS_OPTIONS),
        );
        
        $attach_js = false;
        
        $is_logged = is_user_logged_in();
        if (is_array(self::$ratings) && count(self::$ratings) > 0)
        {
            foreach (self::$ratings as $urid => $data)
            {
                $rclass = $data["rclass"];
                if (isset($rw_settings[$rclass]) && !isset($rw_settings[$rclass]["enabled"]))
                {
                    // Forum reply should have exact same settings as forum post.
                    $alias = ('forum-reply' === $rclass) ? 'forum-post' : $rclass;
                    
                    $rw_settings[$rclass]["enabled"] = true;

                    // Get rating front posts settings.
                    $rw_settings[$rclass]["options"] = $this->GetOption($rw_settings[$rclass]["options"]);

                    if (WP_RW__AVAILABILITY_DISABLED === $this->rw_validate_availability($alias))
                    {
                        // Disable ratings (set them to be readOnly).
                        $rw_settings[$rclass]["options"]->readOnly = true;
                    }

                    $attach_js = true;
                }
            }
        }

        if ($attach_js || self::$TOP_RATED_WIDGET_LOADED)
        {
?>
        <div class="rw-js-container">
            <script type="text/javascript">
                // Initialize ratings.
                function RW_Async_Init(){
                    RW.init({<?php 
                        // User key (uid).
                        echo 'uid: "' . WP_RW__SITE_PUBLIC_KEY . '"';
                        
                        // User id (huid).
                        if (defined('WP_RW__SITE_ID') && is_numeric(WP_RW__SITE_ID))
                            echo ', huid: "' . WP_RW__SITE_ID . '"';
                        
                        $user = wp_get_current_user();
                        if ($user->ID !== 0)
                        {
                            // User logged-in.
                            $vid = $user->ID;
                            // Set voter id to logged user id.
                            echo ", vid: {$vid}";
                        }
                    ?>,
                        source: "wordpress",
                        options: {
                            <?php if (eval(base64_decode('cmV0dXJuICgkdGhpcy0+X2VjY2JjODdlNGI1Y2UyZmUyODMwOGZkOWYyYTdiYWYzKCkgJiYgZGVmaW5lZCgnSUNMX0xBTkdVQUdFX0NPREUnKSAmJiBpc3NldCgkdGhpcy0+bGFuZ3VhZ2VzW0lDTF9MQU5HVUFHRV9DT0RFXSkpOw=='))) : ?>
                            lng: "<?php echo ICL_LANGUAGE_CODE; ?>"
                            <?php endif; ?> 
                        },
                        identifyBy: "<?php echo $this->GetOption(WP_RW__IDENTIFY_BY) ?>"
                    });
                    <?php
                        foreach ($rw_settings as $rclass => $options)
                        {
                            if (isset($rw_settings[$rclass]["enabled"]) && (true === $rw_settings[$rclass]["enabled"]))
                            {
                    ?>
                    var options = <?php echo !empty($rw_settings[$alias]["options"]) ? json_encode($rw_settings[$rclass]["options"]) : '{}'; ?>;
                    <?php echo $this->GetCustomSettings(('forum-reply' === $rclass) ? 'forum-post' : $rclass); ?>
                    RW.initClass("<?php echo $rclass; ?>", options);
                    <?php
                            }
                        }
                        
                        foreach (self::$ratings as $urid => $data)
                        {
                            echo 'RW.initRating("' . $urid . '", {title: "' . esc_js(json_encode($data["title"])) . '", url: "' . esc_js($data["permalink"]) . '"' .
                                  (isset($data["img"]) ? 'img: "' . esc_js($data["img"]) . '"' : '')  . '});';
                        }
                    ?>
                    RW.render(null, <?php
                        echo (!self::$TOP_RATED_WIDGET_LOADED) ? "true" : "false";
                    ?>);
                }

                
                RW_Advanced_Options = {
                    blockFlash: !(<?php
                        $flash = $this->GetOption(WP_RW__FLASH_DEPENDENCY, true);
                        echo in_array($flash, array('true', 'false')) ? $flash : ((false === $flash) ? 'false' : 'true');
                    ?>)
                };
                
                // Append RW JS lib.
                if (typeof(RW) == "undefined"){ 
                    (function(){
                        var rw = document.createElement("script"); 
                        rw.type = "text/javascript"; rw.async = true;
                        rw.src = "<?php echo rw_get_js_url('external' . (!WP_RW__DEBUG ? '.min' : '') . '.php');?>?wp=<?php echo WP_RW__VERSION;?>";
                        var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(rw, s);
                    })();
                }
            </script>
        </div> 
<?php
        }
    }
    
    /* Boosting page
    ---------------------------------------------------------------------------------------------------------------*/
    function BoostPageLoad()
    {
        if ('post' != strtolower($_SERVER['REQUEST_METHOD']) ||
            $_POST["rw_boost_posted"] != "Y")
        {
            return;
        }
        
        $element = (isset($_POST["rw_element"]) && in_array($_POST["rw_element"], array("post", "comment", "activity", "forum", "user"))) ?
                    $_POST["rw_element"] :
                    false;
        if (false === $element){ $this->errors->add('rating_widget_boost', __("Invalid element selection.", WP_RW__ID)); return; }

        $id = (isset($_POST["rw_id"]) && is_numeric($_POST["rw_id"]) && $_POST["rw_id"] >= 0) ?
               (int)$_POST["rw_id"] :
               false;
        if (false === $id){ $this->errors->add('rating_widget_boost', __("Invalid element id.", WP_RW__ID)); return; }
               
        $votes = (isset($_POST["rw_votes"]) && is_numeric($_POST["rw_votes"])) ?
                  (int)$_POST["rw_votes"] : 
                  false;
        if (false === $votes){ $this->errors->add('rating_widget_boost', __("Invalid votes number.", WP_RW__ID)); return; }

        $rate = (isset($_POST["rw_rate"]) && is_numeric($_POST["rw_rate"])) ?
                 (float)$_POST["rw_rate"] : 
                 false;
        if (false === $rate){ $this->errors->add('rating_widget_boost', __("Invalid votes rate.", WP_RW__ID)); return; }
        
        $urid = false;
        switch ($element)
        {
            case "post":
                $urid = $this->_getPostRatingGuid($id);
                break;
            case "comment":
                $urid = $this->_getCommentRatingGuid($id);
                break;
            case "activity":
                $urid = $this->_getActivityRatingGuid($id);
                break;
            case "forum":
                $urid = $this->_getForumPostRatingGuid($id);
                break;
            case "user":
                $urid = $this->_getUserRatingGuid($id);
                break;
        }
        
        $details = array(
            "uid" => WP_RW__SITE_PUBLIC_KEY,
            "urid" => $urid,
            "votes" => $votes,
            "rate" => $rate,
        );
        
        $rw_ret_obj = $this->RemoteCall("action/api/boost.php", $details);
        if (false === $rw_ret_obj){ return; }
        
        // Decode RW ret object.
        $rw_ret_obj = json_decode($rw_ret_obj);

        if (false == $rw_ret_obj->success)
            $this->errors->add('rating_widget_boost', __($rw_ret_obj->msg, WP_RW__ID));
        else
            $this->success->add('rating_widget_boost', __($rw_ret_obj->msg, WP_RW__ID));
    }
    
    function BoostPageRender()
    {
//        $this->rw_boost_page_load();

        $this->_printErrors();
        $this->_printSuccess();
?>
<div class="wrap rw-dir-ltr">
    <h2><?php _e( 'Rating-Widget Boosting', WP_RW__ID ); ?></h2>

    <p>
        <?php _e('Here you can boost your ratings.', WP_RW__ID) ?><br /><br />
        <b style="color: red;"><?php _e('Note: This action impact the rating record directly - it\'s on your own responsibility!', WP_RW__ID) ?></b><br /><br />
        <?php _e('Example:', WP_RW__ID) ?><br />
        <b><?php _e('Element', WP_RW__ID) ?>:</b> <i><?php _e('Post', WP_RW__ID) ?></i>; <b><?php _e('Id:', WP_RW__ID) ?></b> <i>2</i>; <b><?php _e('Votes', WP_RW__ID) ?>:</b> <i>3</i>; <b><?php _e('Rate:', WP_RW__ID) ?></b> <i>4</i>;<br />
        <?php _e('This will add 3 votes with the rate of 4 stars to Post with Id=2.', WP_RW__ID) ?>
    </p>

    <form action="" method="post">
        <input type="hidden" name="rw_boost_posted" value="Y" />
        <label for="rw_element"><?php _e('Element', WP_RW__ID) ?>:
            <select id="rw_element" name="rw_element">
                <option value="post" selected="selected"><?php _e('Post/Page', WP_RW__ID) ?></option>
                <option value="comment"><?php _e('Comment', WP_RW__ID) ?></option>
                <option value="activity"><?php _e('Activity Update', WP_RW__ID) ?></option>
                <option value="forum"><?php _e('Forum Post', WP_RW__ID) ?></option>
                <option value="user"><?php _e('User', WP_RW__ID) ?></option>
            </select>
        </label>
        <br /><br />
        <label for="rw_id"><?php _e('Id:', WP_RW__ID) ?> <input type="text" id="rw_id" name="rw_id" value="" /></label>
        <br /><br />
        <label for="rw_votes"><?php _e('Votes', WP_RW__ID) ?>: <input type="text" id="rw_votes" name="rw_votes" value="" /></label>
        <br /><br />
        <label for="rw_rate"><?php _e('Rate', WP_RW__ID) ?>: <input type="text" id="rw_rate" name="rw_rate" value="" /></label>
        <br />
        <b style="font-size: 10px;"><?php _e('Note: Rate must be a number between -5 to 5.', WP_RW__ID) ?></b>
        <br /><br />
        <input type="submit" value="Boost" />
    </form>
</div>
<?php        
    }
    
    /**
    * Modifies post for Rich Snippets Compliance.
    * 
    */
    function rw_add_title_metadata($title, $id = '')
    {
        return '<mark itemprop="name" style="background: none; color: inherit;">' . $title . '</mark>';
    }
    
    function rw_add_article_metadata($classes, $class = '', $post_id = '')
    {
        $classes[] = '"';
        $classes[] = 'itemscope';
        $classes[] = 'itemtype="http://schema.org/Product';
        return $classes;
    }
    
/* wp_footer() execution validation
 * Inspired by http://paste.sivel.net/24
 --------------------------------------------------------------------------------------------------------------*/
    function test_footer_init() 
    {
        // Hook in at admin_init to perform the check for wp_head and wp_footer
        add_action('admin_init', array(&$this, 'check_head_footer'));
     
        // If test-footer query var exists hook into wp_footer
        if (isset( $_GET['test-footer']))
            add_action('wp_footer', array(&$this, 'test_footer'), 99999); // Some obscene priority, make sure we run last
    }
     
    // Echo a string that we can search for later into the footer of the document
    // This should end up appearing directly before </body>
    function test_footer() 
    {
        echo '<!--wp_footer-->';
    }
 
    // Check for the existence of the strings where wp_head and wp_footer should have been called from
    function check_head_footer() 
    {
        // NOTE: uses home_url and thus requires WordPress 3.0
        if (!function_exists('home_url'))
            return;
        
        // Build the url to call, 
        $url = add_query_arg(array('test-footer' => ''), home_url());
        
        // Perform the HTTP GET ignoring SSL errors
        $response = wp_remote_get($url, array('sslverify' => false));
        
        // Grab the response code and make sure the request was sucessful
        $code = (int)wp_remote_retrieve_response_code($response);
        
        if ($code == 200) 
        {
            // Strip all tabs, line feeds, carriage returns and spaces
            $html = preg_replace('/[\t\r\n\s]/', '', wp_remote_retrieve_body($response));
            
            // Check to see if we found the existence of wp_footer
            if (!strstr($html, '<!--wp_footer-->'))
            {
                add_action('admin_notices', array(&$this, 'test_head_footer_notices'));
            }
        }
    }
 
    // Output the notices
    function test_head_footer_notices() 
    {
        // If we made it here it is because there were errors, lets loop through and state them all
        echo '<div class="updated highlight"><p><strong>' . 
              esc_html('If the Rating-Widget\'s ratings don\'t show up on your blog it\'s probably because your active theme is missing the call to <?php wp_footer(); ?> which should appear directly before </body>.').
              '</strong> '.
              'For more details check out our <a href="' . WP_RW__ADDRESS . '/faq/" target="_blank">FAQ</a>.</p></div>';
    }
    
    /* Post/Page Exclude Checkbox
    ---------------------------------------------------------------------------------------------------------------*/
    function AddPostMetaBox()
    {
        // Make sure only admin can exclude ratings.
        if (!(bool)current_user_can('manage_options'))
            return;
            
        //add the meta box for posts/pages
        add_meta_box('rw-post-meta-box', __('Rating-Widget Exclude Option', WP_RW__ID), array(&$this, 'ShowPostMetaBox'), 'post', 'side', 'high');
        add_meta_box('rw-post-meta-box', __('Rating-Widget Exclude Option', WP_RW__ID), array(&$this, 'ShowPostMetaBox'), 'page', 'side', 'high');
    }
    
    // Callback function to show fields in meta box.
    function ShowPostMetaBox() 
    {
        global $post;
         
        // Use nonce for verification
        echo '<input type="hidden" name="rw_post_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

        $postType = get_post_type($post);
        
        // get whether current post is excluded or not
        $excluded_post = ('page' == $postType) ?
                            (false === $this->rw_validate_visibility($post->ID, 'page')) :
                            (false === $this->rw_validate_visibility($post->ID, 'front-post') && false === $this->rw_validate_visibility($post->ID, 'blog-post'));
        
        $checked = $excluded_post ? '' : 'checked="checked"';

        echo '<p>';
        echo '<label for="rw_include_post"><input type="checkbox" name="rw_include_post" id="rw_include_post" value="1" ', $checked, ' /> ';
        echo __('Show Rating (Uncheck to Hide)', WP_RW__ID);
        echo '</label>';
        echo '</p>';
    }

    // Save data from meta box.
    function SavePostData($post_id)
    {    
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("SavePostData", $params, true); }
        
        // Verify nonce.
        if (!isset($_POST['rw_post_meta_box_nonce']) || !wp_verify_nonce($_POST['rw_post_meta_box_nonce'], basename(__FILE__)))
            return $post_id;

        // Check autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;

        if (RWLogger::IsOn()){ RWLogger::Log("post_type", $_POST['post_type']); }
        
        // Check permissions.
        if ('page' == $_POST['post_type']) 
        {
            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        }
        else if (!current_user_can('edit_post', $post_id)) 
        {
            return $post_id;
        }

        //check whether this post/page is to be excluded
        $includePost = (isset($_POST['rw_include_post']) && "1" == $_POST['rw_include_post']);

        $this->AddToVisibility(
            $_POST['ID'], 
            (('page' == $_POST['post_type']) ? array('page') : array('front-post', 'blog-post')),
            $includePost);
        
        $this->SetOption(WP_RW__VISIBILITY_SETTINGS, $this->_visibilityList);
        
        $this->StoreOptions();
        
        if (RWLogger::IsOn()){ RWLogger::LogDeparture("SavePostData"); }
    }
    
    function PurgePostFeaturedImageTransient($meta_id = 0, $post_id = 0, $meta_key = '', $_meta_value = '')
    {
        if ('_thumbnail_id' === $meta_key)
            delete_transient('post_thumb_' . $post_id);
    }
    
    function DumpLog($pElement = false)
    {
        if (RWLogger::IsOn())
        {
            echo "\n<!-- RATING-WIDGET LOG START\n\n";
            RWLogger::Output("    ");
            echo "\n RATING-WIDGET LOG END-->\n";
        }
    }
    
    function GetPostExcerpt($pPost, $pWords = 15)
    {
        if (!empty($pPost->post_excerpt))
            return trim(strip_tags($pPost->post_excerpt));
        
        $strippedContent = trim(strip_tags($pPost->post_content));
        $excerpt = implode(' ', array_slice(explode(' ', $strippedContent), 0, $pWords));
        
        return (mb_strlen($strippedContent) !== mb_strlen($excerpt)) ?
            $excerpt . "..." :
            $strippedContent;
    }
    
    function GetPostFeaturedImage($pPostID)
    {
        if (!has_post_thumbnail($pPostID))
            return '';

        $exceprt = var_export($image, true) . '<br>' . $exceprt;
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($pPostID), 'single-post-thumbnail');
        return $image[0];
    }
    
    function GetTopRatedData($pTypes = array(), $pLimit = 5, $pOffset = 0, $pMinVotes = 1, $pInclude = false, $pShowOrder = false, $pOrderBy = 'avgrate', $pOrder = 'DESC')
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("GetTopRatedData", $params); }
        
        if (!is_array($pTypes) || count($pTypes) == 0)
            return false;
            
        $types = array(
            "posts" => array(
                "rclass" => "blog-post", 
                "classes" => "front-post,blog-post,new-blog-post,user-post",
                "options" => WP_RW__BLOG_POSTS_OPTIONS,
            ),
            "pages" => array(
                "rclass" => "page", 
                "classes" => "page,user-page",
                "options" => WP_RW__PAGES_OPTIONS,
            ),
            "comments" => array(
                "rclass" => "comment",
                "classes" => "comment,new-blog-comment,user-comment",
                "options" => WP_RW__COMMENTS_OPTIONS,
            ),
            "activity_updates" => array(
                "rclass" => "activity-update",
                "classes" => "activity-update,user-activity-update",
                "options" => WP_RW__ACTIVITY_UPDATES_OPTIONS,
            ),
            "activity_comments" => array(
                "rclass" => "activity-comment",
                "classes" => "activity-comment,user-activity-comment",
                "options" => WP_RW__ACTIVITY_COMMENTS_OPTIONS,
            ),
            "forum_posts" => array(
                "rclass" => "forum-post",
                "classes" => "forum-post,new-forum-post,user-forum-post",
                "options" => WP_RW__FORUM_POSTS_OPTIONS,
            ),
            "users" => array(
                "rclass" => "user",
                "classes" => "user",
                "options" => WP_RW__USERS_OPTIONS,
            ),
        );
        
        $typesKeys = array_keys($types);
        
        $availableTypes = array_intersect($typesKeys, $pTypes);
        
        if (!is_array($availableTypes) || count($availableTypes) == 0)
            return false;

        $details = array( 
            "uid" => WP_RW__SITE_PUBLIC_KEY,
        );

        $queries = array();
       
        foreach ($availableTypes as $type)
        {
                $options = ratingwidget()->GetOption($types[$type]["options"]);

                $queries[$type] = array(
                    "rclasses" => $types[$type]["classes"],
                    "votes" => $pMinVotes,
                    "orderby" => $pOrderBy,
                    "order" => $pOrder,
                    "show_order" => ($pShowOrder ? "true" : "false"),
                    "offset" => $pOffset,
                    "limit" => $pLimit,
                    "types" => isset($options->type) ? $options->type : "star",
                );
                
                if (is_array($pInclude) && count($pInclude) > 0)
                    $queries[$type]['urids'] = implode(',', $pInclude);
        }

        $details["queries"] = urlencode(json_encode($queries));
        
        $rw_ret_obj = ratingwidget()->RemoteCall("action/query/ratings.php", $details, WP_RW__CACHE_TIMEOUT_TOP_RATED);
        
        if (false === $rw_ret_obj)
            return false;
        
        $rw_ret_obj = json_decode($rw_ret_obj);
        
        if (null === $rw_ret_obj || true !== $rw_ret_obj->success)
            return false;
        
        return $rw_ret_obj;
    }
    
    function GetTopRated()
    {
        $rw_ret_obj = $this->GetTopRatedData(array('posts', 'pages'));
        
        if (false === $rw_ret_obj || count($rw_ret_obj->data) == 0)
            return '';
        
        $html = '<div id="rw_top_rated_page">';
        foreach($rw_ret_obj->data as $type => $ratings)
        {                    
            if (is_array($ratings) && count($ratings) > 0)
            {
                $html .= '<div id="rw_top_rated_page_' . $type . '" class="rw-wp-ui-top-rated-list-container">';
                if ($instance["show_{$type}_title"])
                {
                    $instance["{$type}_title"] = empty($instance["{$type}_title"]) ? ucwords($type) : $instance["{$type}_title"];
                    $html .= '<p style="margin: 0;">' . $instance["{$type}_title"] . '</p>';
                }
                $html .= '<ul class="rw-wp-ui-top-rated-list">';

                $count = 1;
                foreach ($ratings as $rating)
                {
                    $urid = $rating->urid;
                    $rclass = $types[$type]["rclass"];
                    $thumbnail = '';
                    ratingwidget()->QueueRatingData($urid, "", "", $rclass);

                    switch ($type)
                    {
                        case "posts":
                        case "pages":
                            $id = RatingWidgetPlugin::Urid2PostId($urid);
                            $post = get_post($id);
                            $title = trim(strip_tags($post->post_title));
                            $excerpt = $this->GetPostExcerpt($post, 15);
                            $permalink = get_permalink($post->ID);
                            $thumbnail = $this->GetPostFeaturedImage($post->ID);
                            break;
                        case "comments":
                            $id = RatingWidgetPlugin::Urid2CommentId($urid);
                            $comment = get_comment($id);
                            $title = trim(strip_tags($comment->comment_content));
                            $permalink = get_permalink($comment->comment_post_ID) . '#comment-' . $comment->comment_ID;
                            break;
                        case "activity_updates":
                        case "activity_comments":
                            $id = RatingWidgetPlugin::Urid2ActivityId($urid);
                            $activity = new bp_activity_activity($id);
                            $title = trim(strip_tags($activity->content));
                            $permalink = bp_activity_get_permalink($id);
                            break;
                        case "users":
                            $id = RatingWidgetPlugin::Urid2UserId($urid);
                            $title = trim(strip_tags(bp_core_get_user_displayname($id)));
                            $permalink = bp_core_get_user_domain($id);
                            break;
                        case "forum_posts":
                            $id = RatingWidgetPlugin::Urid2ForumPostId($urid);
                            $forum_post = bp_forums_get_post($id);
                            $title = trim(strip_tags($forum_post->post_text));
                            $page = bb_get_page_number($forum_post->post_position);
                            $permalink = get_topic_link($id, $page) . "#post-{$id}";
                            break;
                    }
                    $short = (mb_strlen($title) > 30) ? trim(mb_substr($title, 0, 30)) . "..." : $title;
                    
                    $html .= '
<li class="rw-wp-ui-top-rated-list-item">
    <div>
        <b class="rw-wp-ui-top-rated-list-count">' . $count . '</b>
        <img class="rw-wp-ui-top-rated-list-item-thumbnail" src="' . $thumbnail . '" alt="" />
        <div class="rw-wp-ui-top-rated-list-item-data">
            <div>
                <a class="rw-wp-ui-top-rated-list-item-title" href="' . $permalink . '" title="' . $title . '">' . $short . '</a>
                <div class="rw-ui-container rw-class-' . $rclass . ' rw-urid-' . $urid . ' rw-size-small rw-prop-readOnly-true"></div>
            </div>
            <p class="rw-wp-ui-top-rated-list-item-excerpt">' . $excerpt . '</p>
        </div>
    </div>
</li>';
                    $count++;
                }
                $html .= "</ul>";
                $html .= "</div>";
            }
        }
        
        // Set a flag that the widget is loaded.
        RatingWidgetPlugin::TopRatedWidgetLoaded();
        
        ob_start();
?>
<script type="text/javascript">
    // Hook render widget.
    if (typeof(RW_HOOK_READY) === "undefined"){ RW_HOOK_READY = []; }
    RW_HOOK_READY.push(function(){
        RW._foreach(RW._getByClassName("rw-wp-ui-top-rated-list", "ul"), function(list){
            RW._foreach(RW._getByClassName("rw-ui-container", "div", list), function(rating){
                // Deactivate rating.
                RW._Class.remove(rating, "rw-active");
                var i = (RW._getByClassName("rw-report-link", "a", rating))[0];
                if (RW._is(i)){ i.parentNode.removeChild(i); }
            });
        });
    });
</script>
<?php
        $html .= ob_get_clean();
        $html .= '</div>';
        return $html;                
    }

    
    /**
    * Queue rating data for footer JS hook and return rating's html.
    * 
    * @param {serial} $pUrid User rating id.
    * @param {string} $pTitle Element's title (for top-rated widget).
    * @param {string} $pPermalink Corresponding rating's element url.
    * @param {string} $pElementClass Rating element class.
    * 
    * @uses GetRatingHtml
    * @version 1.3.3
    * 
    */
    function EmbedRating(
        $pElementID,
        $pOwnerID, 
        $pTitle, 
        $pPermalink, 
        $pElementClass, 
        $pAddSchema = false, 
        $pHorAlign = false, 
        $pCustomStyle = false, 
        $pOptions = array(),
        $pValidateVisibility = false,
        $pValidateCategory = true)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("EmbedRating", $params); }

        $result = apply_filters('rw_filter_embed_rating', $pElementID, $pOwnerID);
        
        if (false === $result)
            return '';

        if ($pValidateVisibility && !$this->IsVisibleRating($pElementID, $pElementClass, $pValidateCategory))
            return '';
            
        switch ($pElementClass)
        {
            case 'blog-post':
            case 'front-post':
            case 'page':
            case 'user-page':
            case 'new-blog-post':
            case 'user-post':
//                $post = get_post($pElementID);
//                $owner_id = $post->post_author;
                $urid = $this->_getPostRatingGuid($pElementID);
                break;
            case 'comment':
            case 'new-blog-comment':
            case 'user-comment':
//                $comment = get_comment($pElementID);
//                $owner_id = $comment->user_id;
                $urid = $this->_getCommentRatingGuid($pElementID);
                break;
            case 'forum-post':
            case 'forum-reply':
            case 'new-forum-post':
            case 'user-forum-post':
                $urid = $this->_getForumPostRatingGuid($pElementID);
                break;
            case 'user':
//                $owner_id = $pElementID;
                $urid = $this->_getUserRatingGuid($pElementID);
                break;
            case 'activity-update':
            case 'user-activity-update':
            case 'activity-comment':
            case 'user-activity-comment':
//                $activities = bp_activity_get_specific(array('activity_ids' => $pElementID));
//                $owner_id = $activities['activities'][0]->user_id;
                $urid = $this->_getActivityRatingGuid($pElementID);
                break;
        }

        $this->QueueRatingData($urid, $pTitle, $pPermalink, $pElementClass);
        
        $html = $this->GetRatingHtml($urid, $pElementClass, $pAddSchema, $pTitle, $pOptions);
        
        if (false !== ($pHorAlign || $pCustomStyle))
            $html = '<div' . 
                (false !== $pCustomStyle ? ' style="' . $pCustomStyle . '"' : '') . 
                (false !== $pHorAlign ? ' class="rw-' . $pHorAlign . '"' : '') . '>' 
                    . $html . 
                '</div>';
        
        return $html;
    }
    
    function EmbedRatingIfVisible($pElementID, $pOwnerID, $pTitle, $pPermalink, $pElementClass, $pAddSchema = false, $pHorAlign = false, $pCustomStyle = false, $pOptions = array(), $pValidateCategory = true)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("EmbedRatingIfVisible", $params); }
                
        return $this->EmbedRating($pElementID, $pOwnerID, $pTitle, $pPermalink, $pElementClass, $pAddSchema, $pHorAlign, $pCustomStyle, $pOptions, true, $pValidateCategory);
    }
    
    function EmbedRatingByPost($pPost, $pClass = 'blog-post', $pAddSchema = false, $pHorAlign = false, $pCustomStyle = false, $pOptions = array(), $pValidateVisibility = false)
    {
        $postImg = $this->GetPostImage($pPost);
        if (false !== $postImg) 
            $pOptions['img'] = $postImg;

        // Add accumulator id if user accumulated rating.
        if ($this->IsUserAccumulatedRating())
            $pOptions['uarid'] = $this->_getUserRatingGuid($pPost->post_author);
        
        return $this->EmbedRating(
            $pPost->ID,
            $pPost->post_author, 
            $pPost->post_title, 
            get_permalink($pPost->ID), 
            $pClass, 
            $pAddSchema, 
            $pHorAlign,
            $pCustomStyle,
            $pOptions,
            $pValidateVisibility);
    }
    
    function EmbedRatingIfVisibleByPost($pPost, $pClass = 'blog-post', $pAddSchema = false, $pHorAlign = false, $pCustomStyle = false, $pOptions = array())
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("EmbedRatingIfVisibleByPost", $params); }
        
        return $this->EmbedRatingByPost(
            $pPost,
            $pClass,
            $pAddSchema,
            $pHorAlign,
            $pCustomStyle,
            $pOptions,
            true
        );
    }
    
    function EmbedRatingByUser($pUser, $pClass = 'user', $pCustomStyle = false, $pOptions = array(), $pValidateVisibility = false)
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("EmbedRatingByUser", $params); }

        // If accumulated user rating, then make sure it can not be directly rated.
        if ($this->IsUserAccumulatedRating())
        {
            $pOptions['read-only'] = 'true';
            $pOptions['show-report'] = 'false';
        }
            
        return $this->EmbedRating(
            $pUser->id,
            $pUser->id,
            $pUser->fullname, 
            $pUser->domain,
            $pClass, 
            false,
            false,
            $pCustomStyle,
            $pOptions,
            $pValidateVisibility,
            false);
    }
    
    function EmbedRatingIfVisibleByUser($pUser, $pClass = 'user', $pCustomStyle = false, $pOptions = array())
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence("EmbedRatingIfVisibleByUser", $params); }
            
        return $this->EmbedRatingByUser(
            $pUser,
            $pClass, 
            $pCustomStyle, 
            $pOptions,
            true);
    }
    
    function EmbedRatingByComment($pComment, $pClass = 'comment', $pHorAlign = false, $pCustomStyle = false, $pOptions = array())
    {
        if (RWLogger::IsOn()){ $params = func_get_args(); RWLogger::LogEnterence('EmbedRatingByComment', $params); }

        // Add accumulator id if user accumulated rating.
        if ($this->IsUserAccumulatedRating() && (int)$pComment->user_id > 0)
            $pOptions['uarid'] = $this->_getUserRatingGuid($pComment->user_id);
        
        return $this->EmbedRating(
            $pComment->comment_ID,
            (int)$pComment->user_id,
            strip_tags($pComment->comment_content), 
            get_permalink($pComment->comment_post_ID ) . '#comment-' . $pComment->comment_ID, 
            $pClass, 
            false, 
            $pHorAlign,
            $pCustomStyle,
            $pOptions);
    }
    
    function IsUserAccumulatedRating()
    {
        if (!$this->IsBBPressInstalled())
            return false;
        
        return $this->GetOption(WP_RW__IS_ACCUMULATED_USER_RATING);
    }
    
    function GetRatingDataByRatingID($pRatingID, $pAccuracy = false)
    {
        if (!$this->_eccbc87e4b5ce2fe28308fd9f2a7baf3())
            return false;
        
        $rating = $this->ApiCall(
            '/ratings/' . $pRatingID . '.json?is_external=true&fields=id,approved_count,avg_rate', 
            'GET', 
            array(), 
            WP_RW__CACHE_TIMEOUT_RICH_SNIPPETS
        );
        
        if (isset($rating->error))
            return false;

        $avg_rate = (float)$rating->avg_rate;
        $votes = (int)$rating->approved_count;
        $rate = $votes * $avg_rate;

        if (is_numeric($pAccuracy))
        {
            $pAccuracy = (int)$pAccuracy;
            $avg_rate = (float)sprintf("%.{$pAccuracy}f", $avg_rate);
            $rate = (float)sprintf("%.{$pAccuracy}f", $rate);
        }
        
        return array(
            'votes' => $votes,
            'totalRate' => $rate,
            'rate' => $avg_rate,
        );
    }
    
    function RegisterShortcodes()
    {
        add_shortcode('ratingwidget', 'rw_the_post_shortcode');
    }
    
    function GetUpgradeUrl($pImmediate = false, $pPeriod = 'annually', $pPlan = 'professional')
    {
        if (!WP_RW__SITE_SECRET_KEY || !defined('WP_RW__SITE_ID'))
        {
            // Backwards competability //////////////////////////////////////
            $params = array(
                'uid' => WP_RW__SITE_PUBLIC_KEY
            );
            
            if (!$pImmediate)
                $relative = '/get-the-word-press-plugin/?' . http_build_query($params);
            else
            {
                $params['program'] = 'premium';
                $params['frequency'] = ('annually' === $pPeriod) ? 12 : 1;
                $relative = '/get-the-word-press-plugin/subscribe.php?' . http_build_query($params);
            }
        }
        else
        {
            // New pricing page ////////////////////////////////////////////
            $timestamp = time();
            $params = array(
                'context_site' => WP_RW__SITE_ID,
                's_ctx_ts' => $timestamp,
                's_ctx_secure' => md5($timestamp . WP_RW__SITE_ID . WP_RW__SITE_SECRET_KEY . WP_RW__SITE_PUBLIC_KEY . 'upgrade'),
            );
            
            if (!$pImmediate)
                $relative = '/pricing/wordpress/?' . http_build_query($params);
            else
            {
                $params['plan'] = $pPlan;
                $params['period'] = $pPeriod;
                $relative = '/pricing/express-checkout.php?' . http_build_query($params);
            }
        }
        
        return rw_get_site_url($relative);
    }
    
    function ModifyPluginActionLinks($links, $file)
    {
        // Return normal links if not BuddyPress
        if (plugin_basename(WP_RW__PLUGIN_FILE_FULL) != $file)
            return $links;

        // Add a few links to the existing links array
        return array_merge( $links, array(
            'settings' => '<a href="' . rw_get_admin_url() . '">' . esc_html__('Settings', WP_RW__ADMIN_MENU_SLUG) . '</a>',
            'blog'    => '<a href="' . rw_get_site_url('/blog/') . '">' . esc_html__('Blog', WP_RW__ADMIN_MENU_SLUG) . '</a>',
            'upgrade'    => '<a href="' . $this->GetUpgradeUrl() . '" target="_blank">' . esc_html__('Upgrade', WP_RW__ADMIN_MENU_SLUG) . '</a>'
        ) );
    }

    function Notice($pNotice, $pType = 'update-nag')
    {
?>
<div class="<?php echo $pType ?> rw-notice"><span class="rw-slug"><b>rating</b><i>widget</i></span> <b class="rw-sep">&#9733;</b> <?php echo $pNotice ?></div>
<?php
    }

    /* Email Confirmation Handlers
    --------------------------------------------------------------------------------------------------------------------*/

    private function GetEmailConfirmationUrl()
    {
        $uri = rw_get_admin_url();// ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        $timestamp = time();
        
        $params = array(
            'ts' => $timestamp,
            'key' => WP_RW__SITE_PUBLIC_KEY,
            'src' =>  $uri,
        );
        
        $confirmation = $timestamp . WP_RW__SITE_PUBLIC_KEY . $uri;

        $params['s'] = md5($confirmation);
        
        $query = http_build_query($params);
        
        return rw_get_site_url('/signup/wordpress/confirm/') . '?' . $query;
    }
    
    function ConfirmationNotice()
    {
        if (!$this->is_admin)
            return;
        
        $url = $this->GetEmailConfirmationUrl();
        
        $this->Notice('New <i>Weekly Rating Reports</i> feature! <a href="' . $url . '" onclick="_rwwp.openConfirm(\'' . $url . '\'); return false;" target="_blank">Please confirm your email address now</a> to receive your upcoming Monday report.');
    }

    function InvalidEmailConfirmNotice()
    {
        $url = $this->GetEmailConfirmationUrl();
        
        $this->Notice('Email confirmation has failed. One of the confirmation parameters is invalid. <a href="' . $url . '" onclick="_rwwp.openConfirm(\'' . $url . '\'); return false;" target="_blank">Please try to confirm your email again</a>.');
    }
    
    function SuccessfulEmailConfirmNotice()
    {
        $this->Notice('W00t! You have successfully confirmed your email address.', 'update-nag success');
    }
    
    private function TryToConfirmEmail()
    {
        if (!rw_request_is_action('confirm', 'rw_action'))
            return false;
        
        // Remove confirmation notice.
        remove_action('all_admin_notices', array(&$this, 'ConfirmationNotice'));

        $user_id = rw_request_get('user_id');
        $site_id = rw_request_get('site_id');
        $email = rw_request_get('email');
        $timestamp = rw_request_get('ts');
        $secure = rw_request_get('s');

        $is_valid = true;
        
        if ($secure !== md5(strtolower($user_id . $email . $timestamp . WP_RW__SITE_PUBLIC_KEY)))
            $is_valid = false;
        if (!is_numeric($user_id))    
            $is_valid = false;
        if (!is_numeric($timestamp))    
            $is_valid = false;
        if (time() > ($timestamp + 14 * 86400))
            $is_valid = false;
            
        if (!$is_valid)
        {
            add_action('all_admin_notices', array(&$this, 'InvalidEmailConfirmNotice'));
            return true;
        }
        
        $this->SetOption(WP_RW__DB_OPTION_OWNER_ID, $user_id);
        $this->SetOption(WP_RW__DB_OPTION_OWNER_EMAIL, $email);
        $this->SetOption(WP_RW__DB_OPTION_SITE_ID, $site_id);
        
        $this->StoreOptions();
        
        add_action('all_admin_notices', array(&$this, 'SuccessfulEmailConfirmNotice'));
        
        return true;
    }
    
}


require_once(WP_RW__PLUGIN_LIB_DIR . "rw-top-rated-widget.php");

/* Plugin page extra links.
--------------------------------------------------------------------------------------------*/
/**
 * The main function responsible for returning the one true RatingWidgetPlugin Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $rw = ratingwidget(); ?>
 *
 * @return RatingWidgetPlugin The one true RatingWidgetPlugin Instance
 */
function ratingwidget() {
    global $rwp;
    
    if (!isset($rwp))
        $rwp = RatingWidgetPlugin::Instance();
        
    return $rwp;
}

function rwapi()
{
    global $rwapi;
    
    if (!isset($rwapi))
    {
        require_once(WP_RW__PLUGIN_LIB_DIR . 'sdk/ratingwidget.php');
        
        if (!WP_RW__SITE_SECRET_KEY)
        {
            // API can not be accessed without a secret key.
            $rwapi = false;
        }
        else
        {
            // Init API.
            $rwapi = new RatingWidget(
                'site',
                WP_RW__SITE_ID,
                WP_RW__SITE_PUBLIC_KEY,
                WP_RW__SITE_SECRET_KEY
            );
        }
    }
    
    return $rwapi;
};

/**
 * Hook Rating-Widget early onto the 'plugins_loaded' action.
 *
 * This gives all other plugins the chance to load before Rating-Widget, to get
 * their actions, filters, and overrides setup without RatingWidgetPlugin being in the
 * way.
 */
//define('WP_RW___LATE_LOAD', 20);
if (defined('WP_RW___LATE_LOAD'))
    add_action('plugins_loaded', 'ratingwidget', (int)WP_RW___LATE_LOAD);
else
    $GLOBALS['rw'] = ratingwidget();
endif;
?>