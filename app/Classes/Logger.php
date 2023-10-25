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
	public static function message( $message, string $slug ) {

		// Save to Log File
		if( ! \apply_filters( \wp_normalize_path( __CLASS__ ) . '/enable', false ) ) {
			return;
		}

		self::createDir();

		$date = \sprintf( '[ %s ]', \date('d-M-Y H:i:s e') );

		\error_log( "--------------- Begin of Message ---------------" . "\n", 3, self::destination( $slug ) );

		if ( \is_array( $message ) || \is_object( $message ) ) {
			\error_log( $date . \print_r( $message, true ), 3, self::destination( $slug )  );

		} else {
			\error_log( $date . $message . "\n", 3, self::destination( $slug ) );
		}


		\error_log( "--------------- End of Message ---------------" . "\n", 3, self::destination( $slug ) );
	}


	/**
	 * Check if the destination directory exists. If not, create it!
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private static function createDir() {
	
		if ( ! \is_dir( self::dir() ) ) {
			\mkdir( self::dir() );
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
    private static function destination( string $slug ) {
        $file = \sprintf(
        	'debug_%s_%s.log',
        	\str_replace( '-', '_', $slug ),
        	\date( 'Y_m_d' )
        );

        return self::dir() . $file;
    }
}