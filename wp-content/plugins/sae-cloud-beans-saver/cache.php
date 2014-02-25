<?php
$wp_object_cache = new Persistent_Object_Cache();
wp_using_ext_object_cache($wp_object_cache->rw !== NULL);
//wp_using_ext_object_cache(true);
//if cache persistable, using custom cache functions
if(wp_using_ext_object_cache()){
$GLOBALS['wp_object_cache']=$wp_object_cache;

function wp_cache_init() {
    add_action('switch_theme',function(){
        wp_cache_reset();
    });

    if(defined('SAVEQUERIES') ){
        //print cache statics
        add_action('wp_footer',function(){
            if(current_user_can('administrator')){
                global $wpdb;
                global $wp_object_cache;
                // == print db query ==
                echo "<pre style='color: red'>database query executed: <strong>";
                echo count($wpdb->queries) . "</strong>\n";
                if(isset($wpdb->cache_statics)){
                echo "database cache stats:";
                echo "<ul>";
                echo "<li>hits: {$wpdb->cache_statics['hits']} </li>";
                echo "<li>miss: {$wpdb->cache_statics['miss']} </li>";
                echo "<li>changes: {$wpdb->cache_statics['changes']} </li>";
                echo "</ul>";
                }
                echo "executed sqls:\n";
                foreach($wpdb->queries as $i => $q){echo ($i+1).'. '. $q[0] . "\n";}
                echo "</pre>";
                // == print cache status ==
                echo '<div style="color: green">';
                $wp_object_cache->stats();
                echo "</div>";
            }
        });
    }
}
function wp_cache_close() {
    global $wp_object_cache;
    $wp_object_cache->save();
	return true;
}

function wp_cache_get( $key, $group = 'default', $force = false, &$found = null ) {
	global $wp_object_cache;

    $cache = $wp_object_cache->get_group($group);
    $value = $cache->get($key, $found);
    if($found){
        $wp_object_cache->cache_hits += 1;
        return is_object($value) ? clone $value : $value;
    }
    $wp_object_cache->cache_misses += 1;
    return false;
}

function wp_cache_set( $key, $data, $group = 'default', $expire = 0 ) {
	global $wp_object_cache;

    $cache = $wp_object_cache->get_group($group);

    if ( is_object( $data ) ) $data = clone $data;

    return $cache->set($key, $data,(int)$expire);
}

function wp_cache_add( $key, $data, $group = 'default', $expire = 0 ) {
	global $wp_object_cache;
    $cache = $wp_object_cache->get_group($group);
    if($cache->exists($key)){ return false; }

    if ( is_object( $data ) ) $data = clone $data;
    return $cache->set( $key, $data, (int) $expire );

}
function wp_cache_replace( $key, $data, $group = 'default', $expire = 0 ) {
	global $wp_object_cache;
    $cache = $wp_object_cache->get_group($group);
    if(!$cache->exists($key)){ return false; }

    if ( is_object( $data ) ) $data = clone $data;
    return $cache->set( $key, $data, (int) $expire );
}

function wp_cache_incr( $key, $offset = 1, $group = 'default' ) {
	global $wp_object_cache;
    $cache = $wp_object_cache->get_group($group);
    if(!$cache->exists($key)){ return false; }

    if ( ! is_numeric($cache->get( $key ) ) )
        $cache->set( $key ,  0);

    $value = $cache->get($key);
    $offset = (int) $offset;

    $value = $value + $offset;
    if ($value < 0 ) $value =0;

    return $cache->set($key , $value);
}

function wp_cache_decr( $key, $offset = 1, $group = 'default' ) {
    wp_cache_incr($key, -1*$offset, $group);
}

function wp_cache_delete($key, $group = 'default') {
	global $wp_object_cache;
    $cache = $wp_object_cache->get_group($group);
    if(!$cache->exists($key)){ return false; }

    return $cache->delete($key);
}

function wp_cache_add_non_persistent_groups( $groups ) {
    global $wp_object_cache;
    $groups = (array) $groups;
    foreach($groups as $name){
        $wp_object_cache->transient_groups[$name]=true;
    }
}

// == minor methods ==
function wp_cache_flush() {
    wp_cache_reset();
}
function wp_cache_reset() {
	global $wp_object_cache;
	$wp_object_cache->reset();
}
//no support multisite
//function wp_cache_switch_to_blog( $blog_id ) { return false; }

//no support multisite
function wp_cache_add_global_groups( $groups ) {
    return true;
}

}

class Persistent_Object_Cache {

	/**
	 * Holds the cached groups
	 *
	 * @var array
	 * @access private
	 * @since 2.0.0
	 */
	var $groups = array ();

    var $transient_groups = array();

	/**
	 * The amount of times the cache data was already stored in the cache.
	 *
	 * @since 2.5.0
	 * @access private
	 * @var int
	 */
	var $cache_hits = 0;

	/**
	 * Amount of times the cache did not have the request in cache
	 *
	 * @var int
	 * @access public
	 * @since 2.0.0
	 */
	var $cache_misses = 0;

    /**
     * Holds initial serialized string of each group
     *
     * @var array
     * @access public
     */
    var $serialized_data = array();

    /**
     * Holds which cache chunk a group resides
     * @var array
     */
    var $chunk_of_group = array();

    var $rw;

    function __construct(){

        if(class_exists('SaeKV')){
            //running at SAE now.
            $saekv = new SaeKV;
            if($saekv->init()===TRUE){
                //KVDB is ok
                $this->rw = $saekv;
                define('CHUNK_SIZE', 0x300000);//3MB
            }
            if($this->rw === NULL){
                //TODO: try memcache
                //define('CHUNK_SIZE', 0x100000);//1MB
            }
            if($this->rw === NULL && defined('SAE_STORAGE') && SAE_STORAGE){
                //sae storage
                $this->rw = new _SaeStorageCache(SAE_STORAGE);
                define('CHUNK_SIZE', 0x800000);//8MB
            }
        }else{
            //running at localhost?
            $this->rw=new _Wp_File_Cache(ABSPATH . 'wp-content/cache');
            define('CHUNK_SIZE', 0x400000);//4MB
        }
        if($this->rw === NULL)return;
        // == load serialized data ==
        $ser = $this->rw->get('cache.0');
        if($ser===false){
            //read failed, try write
            if($this->rw->set('cache.0',serialize(array()))===false){
                //write failed, rw not work.
                $this->rw = NULL;
            }
            return;
        }
        $data = unserialize($ser);
        if(isset($data['$chunk_of_group'])){
            $this->chunk_of_group = $data['$chunk_of_group'];
            unset($data['$chunk_of_group']);
        }
        $this->serialized_data=$data;
    }
	function save() {
        if($this->rw === NULL)return;
        $data = array();
        $chunk_of_group = array();
        $size = 0;
        $n = 1;
        foreach($this->groups as $key => $value){
            if(isset($this->transient_groups[$key]))continue;
            $ser = serialize($value);
            $size += strlen($ser);
            if($size < CHUNK_SIZE || empty($data)){
                //add serialize strings if size < CHUNK_SIZE or it is the first loop
                $data[$key]=$ser;
            }else{
                $ok = $this->rw->set('cache.' . $n, serialize($data));
                if(!$ok){
                    //write failed
                    return false;
                }
                foreach(array_keys($data) as $group_name){
                    $chunk_of_group[$group_name]=$n;
                }
                $size = strlen($ser);
                $data = array($key=>$ser);
                $n+=1;
            }
        }
        !empty($data) or die('$data must not be empty array');
        foreach(array_keys($data) as $group_name){
            $chunk_of_group[$group_name]=0;
        }
        $data['$chunk_of_group']=$chunk_of_group;
        return $this->rw->set('cache.0', serialize($data));
	}

    function get_group($name){
        if(isset($this->groups[$name])){
            return $this->groups[$name];
        }
        //group not found, try to load from persistent cache
        if(isset($this->serialized_data[$name])){
            //already loaded
            $ser = $this->serialized_data[$name];
            unset($this->serialized_data[$name]);
        }else{
            //found persistent cache index
            if(isset($this->chunk_of_group[$name])){
                $index = $this->chunk_of_group[$name];
                //load it
                $ser = $this->rw->get('cache.' . $index);
                unset($this->chunk_of_group[$name]);
            }
        }
        if(!isset($ser)){
            //bad luck, not found in the  persistent cache, create new group
            $cg= new _Cache_Group;
            $this->groups[$name]=$cg;
            return $cg;
        }
        $cg=unserialize($ser);
        $this->groups[$name]=$cg;
        return $cg;
    }

    function reset(){
        $this->groups=array();
        $this->serialized_data = array();
        $this->chunk_of_group = array();
    }

	/**
	 * Echoes the stats of the caching.
	 *
	 * Gives the cache hits, and cache misses. Also prints every cached group,
	 * key and the data.
	 *
	 * @since 2.0.0
	 */
	function stats() {
		echo "<p>";
		echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
		echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
		echo "</p>";
		echo '<ul>';
		foreach ($this->groups as $group => $cache) {
			echo "<li><strong>Group:</strong> $group - ( " . number_format( strlen( serialize( $cache ) ) / 1024, 2 ) . 'k )</li>';
		}
		echo '</ul>';
	}
}

class _Cache_Group implements Serializable{
    var $cache=array();
    var $expire=array();

    function get($key,&$found = NULL ){
        $found = isset($this->cache[$key]);
        return $found ? $this->cache[$key] : false;
    }

    function exists($key){
        return isset($this->cache[$key]);
    }

    function delete($key){
        unset($this->cache[$key]);
        unset($this->expire[$key]);
    }

    function set($key,$value, $expire_time=0){
        $this->cache[$key]=$value;
        if($expire_time>0)$this->expire[$key]=$expire_time+time();
        return true;
    }

    function reset(){
        $this->cache=array();
        $this->expire=array();
    }

    public function serialize() {
        //clear out expired items
        $now = time();
        foreach($this->expire as $key => $timestamp){
            if($now>=$timestamp){
                unset($this->$cache[$key]);
            }
        }
        $ser =serialize(array($this->expire,$this->cache));
        $gz_flag = '0';
        if(strlen($ser) >= CHUNK_SIZE){
            $gz_flag = '1';
            $ser = gzcompress($ser);
        }
        return $gz_flag . $ser;
    }
    public function unserialize($data) {
        $gz_flag = $data[0];
        if($gz_flag === '0' ){
            list($this->expire,$this->cache)=unserialize(substr($data,1));
        }else if($gz_flag === '1' ) {
            list($this->expire,$this->cache)=unserialize(gzuncompress(substr($data,1)));
        }else{
            throw Exception('malformat serialize data');
        }
    }
}

class _Wp_File_Cache {
    var $dir;
    public function __construct($directory){
        $this->dir = $directory;
    }
    public function get($filename){
        return @file_get_contents( $this->dir . '/' . $filename);
    }
    public function set($filename, $stringValue){
        return @file_put_contents( $this->dir . '/' . $filename, $stringValue);
    }
}

class _SaeStorageCache {
    var $domain;
    var $stor;
    public function __construct($domain){
        $this->domain=$domain;
        $this->stor = new SaeStorage;
    }

    public function get($filename){
        return $this->stor->read($this->domain, $filename);
    }
    public function set($filename, $stringValue){
        return $this->store->write($this->domain, $filename,$stringValue);
    }
}
class _SaeMemcache {
    var $mmc;

    public function __construct(){
        $this->mmc = memcache_init();
    }
}
unset($wp_object_cache);
