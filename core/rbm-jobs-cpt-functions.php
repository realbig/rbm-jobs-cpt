<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	RBM_Jobs_CPT
 * @subpackage RBM_Jobs_CPT/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		RBM_Jobs_CPT
 */
function RBMJOBSCPT() {
	return RBM_Jobs_CPT::instance();
}