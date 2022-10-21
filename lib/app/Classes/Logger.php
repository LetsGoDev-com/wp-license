<?php
namespace LetsGoDev\Classes;

/**
 * Logger Class
 * 
 */
class Logger {
	
	/**
	 * Write a message into log file
	 * Message can be string, number, array or object.
	 *
	 *
	 * @param    mixed   $message   Message will be printed
	 * @param    boolean $var_dump  If you want use var_dump() for print the message
	 * @since    1.0.0
	 * @access   public
	 * @return   void
	 */
	public static function message( $message, $var_dump = false ) {

		if( \apply_filters( __NAMESPACE__ . '/enable', false ) ) {
			return;
		}

		self::createDir();

		$date = sprintf( '[ %s ]', date('d-M-Y H:i:s e') );

		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( $date . print_r( $message, true ), 3, self::destination()  );

		} else {
			if ( $var_dump ) {
				ob_start();
				var_dump($message);
				$result = ob_get_clean();
				error_log( $date . $result, 3, self::destination() );

			} else {
				error_log( $date . $message . "\n", 3, self::destination() );
			}
		}
	}


	/**
	 * Check if the destination directory exists. If not, create it!
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private static function createDir() {
	
		if ( ! is_dir( self::dir() ) ) {
			mkdir( self::dir() );
		}
	}

	/**
	 * Get destination directory
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string
	 */
	private static function dir() {
		return WP_CONTENT_DIR . '/uploads/letsgo-logs/';
	}

	/**
     * Get full destination path. Includes file name.
     *
     * @since    1.0.0
     * @access   private
     * @return   string
     */
    private static function destination() {
        $file = sprintf( 'debug_%s.log', date('Y_m_d') );
        return self::dir() . $file;
    }

}