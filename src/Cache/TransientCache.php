<?php
/**
 * Class TransientCache file.
 */

 namespace Slc\SeoLinksCrawler\Cache;

/**
 * Manange cache data.
 **/
class TransientCache {

	/**
	 * Transient key.
	 *
	 * @var string
	 */
	private $transient_key;
	
	/**
	 * TransientCache constructor.
	 *
	 * @param string $transient_key  key using which cache data is stored.
	 */
	// public function __construct( string $transient_key ) {
	// 	$this->prefix = 'slc_';
	// }

	public function set_transient_key( string $transient_key ){
		$prefix = 'slc_';
		$this->transient_key = $prefix . $transient_key;
	}

	/**
	 * Set data in transient cache for 1 hour.
	 *
	 * @param  string $transient_data  Data to be stored in cache.
	 */
	public function add_transient_data( string $transient_data ) {
		set_transient( $this->transient_key, $transient_data, HOUR_IN_SECONDS );
	}

	/**
	 * Get transient cache data.
	 *
	 * @return  string  $transient_data  Store cache data
	 */
	public function receive_transient_data() {
		$transient_data = get_transient( $this->transient_key );
		return $transient_data;
	}

	/**
	 * Clear transient cache data.
	 *
	 * @return boolean $deleted true if deleted otherwise false.
	 */
	public function remove_transient_data() {
		$deleted = delete_transient( $this->transient_key );
		return $deleted;
	}
}
