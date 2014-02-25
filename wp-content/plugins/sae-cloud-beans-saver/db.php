<?php
if(!class_exists('wpdb'))require(ABSPATH . 'wp-include/wp-db.php');
require(ABSPATH . 'wp-content/plugins/sae-cloud-beans-saver/cache.php');

if(wp_using_ext_object_cache()){
    //cache is persistable, using custom db settings.
    $wpdb = new SAEwpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
}

class SAEwpdb extends wpdb {

    var $_last_query = false;
    var $last_run_query = false;
    var $cache_statics = array('changes'=>0,'hits'=>0,'miss'=>0);

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $query ) {
		if ( ! $this->ready )
			return false;
		/**
		 * Filter the database query.
		 *
		 * Some queries are made before the plugins have been loaded, and thus cannot be filtered with this method.
		 *
		 * @since 2.1.0
		 * @param string $query Database query.
		 */
		$query = apply_filters( 'query', $query );

		$return_val = 0;
        //save last query before last_query setted to null in flush()
        $this->_last_query = $this->last_query;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

        if($query == 'SELECT FOUND_ROWS()'){
            $item = wp_cache_get(sha1($this->_last_query),'mysql');
            if(isset($item['found_rows'])){
                $this->last_result = $item['found_rows'];
                $this->num_rows = 1;
                $this->cache_statics['hits']++;
                return 1;
            }
        }else{
            $query_sha1 = sha1($query);
            $item = wp_cache_get($query_sha1,'mysql');
            if($item !== false ){
                $this->last_result = $item['result'];
                $this->num_rows=count($this->last_result);
                $this->cache_statics['hits']++;
                return $this->num_rows;
            }
        }

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->timer_start();

		$this->result = @mysql_query( $query, $this->dbh );
		$this->num_queries++;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );

		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $this->dbh ) ) {
			// Clear insert_id on a subsequent failed insert.
			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
				$this->insert_id = 0;

			$this->print_error();
			return false;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
            wp_cache_reset();//clean all cache if database changed
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = mysql_affected_rows( $this->dbh );
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = mysql_insert_id($this->dbh);
			}
            //clean cache if table changed
            global $wp_object_cache;
            $group = $wp_object_cache->get_group('mysql');
            foreach($group->cache as $key => &$c){
                if(strpos($query,$c['table_name'])!==false){
                    $group->delete($key);
                    $this->cache_statics['changes']++;
                }
            }
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;
			while ( $row = @mysql_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}
            // == save query results ==
            //
            if(@preg_match('/FROM\s+(\w+)/',$query,$m)){
                $new_item= array(
                    'sha1' => $query_sha1,
                    'table_name' => $m[1],
                    'query' => $query, 
                    //TODO: array_slice(...) is more safe?
                    'result' => $this->last_result
                );
                wp_cache_set($query_sha1,$new_item,'mysql');
                $this->cache_statics['miss']++;
            }else if($query == 'SELECT FOUND_ROWS()'){
                $this->_last_query == $this->last_run_query or die($this->_last_query . "\nnot equal to\n" . $this->last_run_query);
                $key = sha1($this->last_run_query);
                $value = wp_cache_get($key,'mysql');
                $value['found_rows'] = $this->last_result;
                wp_cache_set($key,$value,'mysql');
            }

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}
        $this->last_run_query = $query;

		return $return_val;
    }

	/**
	 * Load the column metadata from the last query.
     * when cache is enabled, it trys to load_col_info as following
     * 1. when last_run_query==last_query, query database and cache result.
     * 2. when cached col_info exists, return it
     * 3. FIXME: when cached col_info not there, return empty array.
     * it indeed breaks what the original function promised, fix is required.
     * but it seems that this function is not called by outer as far as now.
     * it's waste of time to fix function nobody wants. leave it now.
	 *
	 * @since 3.5.0
	 *
	 * @access protected
	 */
    protected function load_col_info(){
        if($this->last_run_query==$this->last_query){
            parent::load_col_info();
            $key = sha1($this->last_run_query);
            $value = wp_cache_get($key, 'mysql');
            $value['col_info']=$this->col_info;
            wp_cache_set($key, $value,'mysql');
        }else{
            $key = sha1($this->last_run_query);
            $value = wp_cache_get($key, 'mysql');
            if(isset($value['col_info'])){
                $this->col_info = $value['col_info'];
                return;
            }
            //FIXME:no lucky, cached col_info nonexists
            $this->col_info=array();
        }
    }

}

