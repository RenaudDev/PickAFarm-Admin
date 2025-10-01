<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

if (file_exists(__DIR__ . '/.env.local')) {
    // Local development
    define( 'DB_USER', 'root' );
    define('DB_PASSWORD', '');
    define( 'WP_DEBUG', true );
} else {
    // Production
    define( 'DB_USER', 'wordpress' );
    define('DB_PASSWORD', '070b6d9005a520349f1b1b3c73417447e448e1876ea66a58');
    define( 'WP_DEBUG', false );
}

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('SECURE_AUTH_KEY', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('LOGGED_IN_KEY', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('NONCE_KEY', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('AUTH_SALT', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('SECURE_AUTH_SALT', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('LOGGED_IN_SALT', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define('NONCE_SALT', '-!l^LxDo^My4N{7{s._,y,mZ[4?A58ipvw5&w;hy>6XGlCRwvr8J2.@X^{k3lecd');
define( 'WP_CACHE_KEY_SALT', 'G4q%KJoQy:+8$RKB@#RTuIe|.Cy0n:;kXO?(2gj)>)#kk+><u/fYC){;A^E>;)6?' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
/** Add before "That's all, stop editing!" */
header("Access-Control-Allow-Origin: https://pickafarm.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
