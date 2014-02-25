<?php
/*
Plugin Name: SAE cloud beans saver
Plugin URI: http://wordpress.org/plugins/sae-cloud-beans-saver/
Description: saving your cloud beans at SAE
Author: shiedman
Author URI: http://profiles.wordpress.org/shiedman/
Version: 0.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Abort plugin loading if WordPress is upgrading
 */
if (defined('WP_INSTALLING') && WP_INSTALLING) return;
if(!defined('_SAE_BEAN_SAVER_')){
    define('_SAE_BEAN_SAVER_', TRUE);
    //wordpress always magic quotes, save the real post data here.
    //@SEE: http://stackoverflow.com/questions/8949768/with-magic-quotes-disabled-why-does-php-wordpress-continue-to-auto-escape-my
    //or remove quote by: $_POST = array_map( 'stripslashes_deep', $_POST );
    //@SEE: http://codex.wordpress.org/Function_Reference/stripslashes_deep
    $_REAL_POST=$_POST;

    WpBeanSaver::register_plugin();
    new WpBeanSaver;
}

class WpBeanSaver {
    static $page_title = "Cloud Beans Saver at SAE";
    static $menu_title = "beans saver";
    static $menu_slug = "cloud-beans-saver";
    static $capability = "manage_options";

    static function _($name){
        return self::$menu_slug . '_' .$name;
    }

    var $options = array();

    public function __construct(){
        $options = unserialize(get_option(self::_('option')));
        if(is_array($options)){
            $this->options = $options;
        }
        add_action('wp_head', array($this, 'add_header'));
        add_action('wp_footer', array($this, 'add_footer'));
        if(is_admin()){
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        if(isset($this->options['loader_src'])){
            add_filter('style_loader_src',array($this,'loader_filter'),99,2);
            add_filter('script_loader_src',array($this,'loader_filter'),99,2);
        }
    }

    public function add_header(){
        //$plugin_uri = plugins_url('',__FILE__);
        $html = $this->options['custom_head'];
        if(!empty($html))
            echo str_replace('%site%',site_url(),$html) . "\n";
    }

    public function add_footer(){
        $html = $this->options['custom_foot'];
        if(!empty($html))
            echo str_replace('%site',site_url(),$html) . "\n";
    }


    /**
     *  copied from wp-includes/script-loader.php
     *  inspired by http://blog.mudy.info/tag/cdn/
     *
     * @param string $src Source URL.
     * @param string $handle resource filename without file suffix
     * @return string URL path to js/css
     */
    public function loader_filter($src, $handle){
        //return str_replace(home_url(),'http://shiedman.gitcafe.com/wordpress',$src);
        return str_replace(home_url(),$this->options['loader_src'],$src);
    }

    public function update_option(){
        global $_REAL_POST;
        if(isset($_REAL_POST['clean_cache'])){
            //clean all cache
            wp_cache_reset();
            return array('status'=>'updated','msg'=>'cache cleaned');
        }
        $url  = trim($_REAL_POST['loader_src']);
        $head = trim($_REAL_POST['custom_head']);
        $foot = trim($_REAL_POST['custom_foot']);
        if(!empty($url) && !@preg_match('|^https?://[\w\.]{6,}|',$url)){
            return array('status'=>'error','msg'=>'url is not valid');
        }else{
            $this->options['loader_src'] = $url;
        }
        $this->options['custom_head'] = $head;
        $this->options['custom_foot'] = $foot;
        $ser = serialize($this->options);
        if(get_option(self::_('option'))===$ser){
            return array('status'=>'updated','msg'=>'nothing changed');
        }else{
            $status =  update_option(self::_('option'),$ser) ? 'updated' : 'error';
            return array(
                'status'=>$status,
                'msg'=> ($status==='updated'?__('Saved.') : __('Save failed'))
            );
        }
    }

    public function option_page(){
        $msg = '';
        $my_action = 'update_option';
        // if check failed,
        // check_admin_referer will automatically print a "failed" page and die.
        // see: https://codex.wordpress.org/Function_Reference/wp_nonce_field
        $form_submit =!empty($_POST) && check_admin_referer($my_action,'_wpnonce');
        if($form_submit){
            //process form data
            $ret = $this->update_option();
            $msg = sprintf('<div id="message" class="%s"><p><strong>%s</strong></p></div>', $ret['status'],$ret['msg']);
        }

        ob_start();
        wp_nonce_field($my_action,'_wpnonce');
        $hidden_field = ob_get_clean();
        $__='__';
        $loader_src = htmlspecialchars($this->options['loader_src']);
        $custom_head = htmlspecialchars($this->options["custom_head"]);
        $custom_foot = htmlspecialchars($this->options["custom_foot"]);
        echo <<<__EOF__
<div class="wrap">
<h2>Cloud Beans Saver at SAE</h2>
{$msg}
<!-- don't set action, leave it default -->
<form method="post">
    {$hidden_field}
    <table class="form-table">
        <tr valign="top">
            <th scope="row">cache status</th>
            <td >
__EOF__;
if(!class_exists('Persistent_Object_Cache')){
    echo '<p style="color: #EA2F53"><b>db.php</b> not found in wp-content, you must copy it there.</p>';
}else if(class_exists('SaeKV')&& !wp_using_ext_object_cache()){
    //running at SAE now, but cache not persistable(not using external object cache)
    $kv_link = 'http://sae.sina.com.cn/?m=kv&app_id=' . SAE_APPNAME;
    echo '<p style="color: #EA2F53">KVDB disabled.  <a href="'. $kv_link . '" class="button button-primary">enable</a></p>';
}else{
        global $wp_object_cache;
        $objcache_size = strlen(serialize($wp_object_cache->groups));
        $objcache_size = number_format($objcache_size/1024,2) . 'k';
echo <<<__EOF__
                <p>cache size: <code>{$objcache_size}</code></p>
                <p class="description" style="color:green">cache is cleaned when theme switched.   Or you can now force to <input type="submit" class="button button-primary" name="clean_cache" value="clean"/></p>
__EOF__;
}
echo <<<__EOF__
            </td>
        </tr>
    </table>
</form>
<form method="post">
    {$hidden_field}
    <table class="form-table">
        <tr valign="top">
            <th scope="row">js/css site_url</th>
            <td>
                <input name="loader_src" type="text" id="loader_src" class="regular-text code" value="{$loader_src}"/>
                <p class="description">
e.g. Set to <b>http://www.example.com/wordpress</b><br/> which suppose wordpress static files is hosted in a directory <b>wordpress</b> for the domain <b>www.example.com</b>.
</p>
                <p class="description">
If you do not have a server, try gitcafe/github page service, they are all free. 
<input type="button" class="button" value="show how &#x25BC;" onclick="jQuery('#gitcafe-help').slideToggle('slow');"/>
</p>
<div id="gitcafe-help" style="color: #657b83; background: #fdf6e3; display:none;">
<p>suppose you have an account <b>bruce</b> at gitcafe, created a repo <b>wordpress</b>.</p>
<pre style="background: #eee8d5">
cd wordpress  <span style="color: #93a1a1"># where all wordpress files is</span>
git init
git remote add origin git@gitcafe.com:bruce/wordpress.git
git checkout -b gitcafe-pages
echo 'hello,worlld' > index.html
git add .
git commit -m 'wordpress files'
git push -u origin gitcafe-pages
</pre>
</p>
That's it. browse to <b>http://bruce.gitcafe.com/wordpress</b>, should give you "hello,world".</p>
<p>
how to create <a href="https://gitcafe.com">gitcafe</a> page: <a href="http://blog.gitcafe.com/116.html">http://blog.gitcafe.com/116.html</a><br />
how to create <a href="https://github.com/">github</a> page: <a href="http://pages.github.com/">http://pages.github.com/</a>
</p>
</div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">custom page head/foot</th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text">header</legend>
                    <p><label for="custom_head">add custom head to page (<em>place before &lt;/head&gt;</em>)</label></p>
                    <p><textarea id="custom_head" name="custom_head" class="large-text code" rows="6">{$custom_head}</textarea></p>
                </fieldset>
                <fieldset>
                    <legend class="screen-reader-text">footer</legend>
                    <p><label for="custom_foot">add custom foot to page (<em>place before &lt;/body&gt;</em>)</label></p>
                    <p><textarea id="custom_foot" name="custom_foot" class="large-text code" rows="6">{$cutom_foot}</textarea></p>
                </fieldset>
            </td>
        </tr>
    </table>

    <p class="submit">
        <input class="button button-primary" type="submit" value="{$__('Save Changes')}" />
    </p>

    </form>
    </div>
__EOF__;
    }

    public function add_admin_menu(){
        add_submenu_page('plugins.php',self::$page_title,self::$menu_title,self::$capability,self::$menu_slug,array($this,'option_page'));
    }


    /* Runs when plugin is activated */
    public static function activate(){
        /* Creates new database field */
        //add_option($name, $value, $deprecated='', $autoload='yes');
        add_option(self::_('option'),'');
    }

    /* Runs on plugin deactivation*/
    public static function deactivate(){
        /* Deletes the database field */
        delete_option(self::_('option'));
    }

    public static function RegisterActionLinks($links) {
        $links[] = sprintf('<a href="%s?page=%s">%s<a/>', admin_url('plugins.php'), self::$menu_slug, __('Settings'));
        return $links;
    }

    public static function register_plugin(){
        register_activation_hook(__FILE__,array(__CLASS__,'activate'));
        register_deactivation_hook(__FILE__,array(__CLASS__,'deactivate'));
		//add_filter('plugin_row_meta', array(__CLASS__, 'RegisterPluginLinks'),10,2);
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'RegisterActionLinks'));
    }
}

