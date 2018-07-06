<?php if ( ! defined( 'WPINC' ) ) exit;
/**
 * Enable Beaver Builder custom CSS classes selector
 *
 * Enables CSS classes selector for "CSS class" Beaver Builder settings form field,
 * and adds options for each CSS class specially defined within the CSS code string.
 *
 * Requires Beaver Builder 2.1.4 and newer.
 *
 * @uses  Applies these filter hooks:
 *   - `Custom_CSS_Class_Select/get_variable_prefix`
 *   - `Custom_CSS_Class_Select/get_declaration_shortcode`
 *   - `Custom_CSS_Class_Select/get_classes_array/css_code`
 *   - `Custom_CSS_Class_Select/get_text`
 *   - `Custom_CSS_Class_Select/cache_transient_name`
 *   Tip: Hook names are set as `__CLASS__ . '/' . __FUNCTION__( . '/' . $variable_name)`.
 *   Tip: See code below for hook information.
 *
 * @example  Getting CSS classes from declarations within "Additional CSS" customizer field content:
 *   require_once 'custom-css-class-select.php';
 *   add_filter( 'fl_builder_field_js_config', 'Custom_CSS_Class_Select::set_class_options', 10, 3 );
 *   add_filter( 'Custom_CSS_Class_Select/get_classes_array/css_code', 'wp_get_custom_css' );
 *   add_action( 'customize_save_after', 'Custom_CSS_Class_Select::cache_flush' );
 *
 * @example  CSS class declaration within CSS code string (put this into CSS comment):
 *   [custom_class
 *     class="my-custom-class-name"
 *     label="My custom CSS class label"
 *     scope="global, !rich-text, !html"
 *     /]
 *
 * @see  Readme file for more information.
 *
 *
 * @license    GPL-3.0, https://www.gnu.org/licenses/gpl-3.0.html
 * @copyright  WebMan Design, Oliver Juhas
 * @link       https://www.webmandesign.eu
 * @link       https://github.com/webmandesign/custom-css-class-select/
 *
 * @version  1.0.0
 *
 * Contents:
 *
 * 1) Setters
 * 2) Getters
 * 3) Cache
 */
class Custom_CSS_Class_Select {





	/**
	 * 1) Setters
	 */

		/**
		 * Sets custom CSS class dropdown options for Beaver Builder "CSS class" field.
		 *
		 * Hook this method onto Beaver Builder's `fl_builder_field_js_config` filter hook.
		 *
		 * @todo  Custom optgroup display.
		 * Custom optgroup display currently works for global scoped classes only. If the class
		 * is defined for a specific module(s) only and also custom optgroup is set, the class
		 * is listed for all modules anyway.
		 * Probably will require to rework the `$classes` array structure for more flexible setup.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @param  array  $field      An array of setup data for the field.
		 * @param  string $field_key  The field key.
		 * @param  string $form_key   Module/form key.
		 *
		 * @return  array  Altered array of setup data for the field.
		 */
		public static function set_class_options( $field, $field_key = '', $form_key = '' ) {

			// Processing

				if ( 'class' === $field_key ) {
					$classes = array_filter( array(
						'global'     => (array) self::get_classes_array( 'global' ),
						'module'     => (array) self::get_classes_array( 'module:' . $form_key ),
						'module_not' => array_keys( (array) self::get_classes_array( 'module_not:' . $form_key ) ),
						/**
						 * See @todo above, in method docblock.
						 */
						// 'optgroup'   => (array) self::get_classes_array( 'optgroup' ),
					) );

					if ( ! empty( $classes ) ) {
						$options = array();

						// Removing classes from global scope and optgroups if not for this module.
						if ( isset( $classes['module_not'] ) ) {
							foreach ( $classes['module_not'] as $unset_class ) {
								// Unset from global scope.
								unset( $classes['global'][ $unset_class ] );
								// Unset from any optgroup.
								if ( isset( $classes['optgroup'] ) ) {
									foreach ( $classes['optgroup'] as $optgroup_name => $optgroup_classes ) {
										unset( $classes['optgroup'][ $optgroup_name ][ $unset_class ] );
									}
									$classes['optgroup'] = array_filter( $classes['optgroup'] );
								}
							}
						}

						// Custom optgroup.
						if ( isset( $classes['optgroup'] ) && ! empty( $classes['optgroup'] ) ) {
							foreach ( $classes['optgroup'] as $optgroup_name => $optgroup_classes ) {
								$options += array(
									'optgroup-' . self::get_variable_prefix() . '-' . sanitize_title( $optgroup_name ) => array(
										'label'   => esc_html( $optgroup_name ),
										'options' => (array) $optgroup_classes,
									),
								);

								// Only remove classes from global scope.
								// This may actually cause a class to appear under multiple optgroups, but not global.
								foreach ( $optgroup_classes as $optgroup_class => $optgroup_classes_label ) {
									unset( $classes['global'][ $optgroup_class ] );
								}
							}
						}

						// The above operations may have changed the arrays.
						$classes = array_filter( $classes );

						// Optgroup for global classes.
						if ( isset( $classes['global'] ) && ! empty( $classes['global'] ) ) {
							$options += array(
								'optgroup-' . self::get_variable_prefix() . '-global' => array(
									'label'   => esc_html( self::get_text( 'label-optgroup-global' ) ),
									'options' => (array) $classes['global'],
								),
							);
						}

						// Optgroup for module-specific classes.
						if ( isset( $classes['module'] ) && ! empty( $classes['module'] ) ) {
							$modules = FLBuilderModel::$modules;

							if ( isset( $modules[ $form_key ] ) ) {
								$module_name = $modules[ $form_key ]->name;
							} else if ( 'row' === $form_key ) {
								$module_name = self::get_text( 'label-option-row' );
							} else if ( 'col' === $form_key ) {
								$module_name = self::get_text( 'label-option-column' );
							} else {
								$module_name = $form_key;
							}

							$options += array(
								'optgroup-' . self::get_variable_prefix() . '-' . $form_key => array(
									'label'   => sprintf( esc_html( self::get_text( 'label-optgroup-module' ) ), $module_name ),
									'options' => (array) $classes['module'],
								),
							);
						}

						// Reverse array to display optgroups in this order: Module, Global, Custom.
						$options = array_reverse( $options );

						// Set the field options.
						if ( ! empty( $options ) ) {
							if ( isset( $field['options'] ) ) {
								$field['options'] += $options;
							} else {
								$field['options'] = $options;
							}
						}

						// We need to have an empty option too!
						if ( ! isset( $field['options'][''] ) && ! empty( $options ) ) {
							$field['options'] = array(
								'' => self::get_text( 'label-option-empty' ),
							) + $field['options'];
						}

					}
				}


			// Output

				return $field;

		} // /set_class_options





	/**
	 * 2) Getters
	 */

		/**
		 * Gets variable prefix.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @return  string  Filtered variable prefix.
		 */
		public static function get_variable_prefix() {

			// Output

				/**
				 * Filters the unique prefix used in variables of the code.
				 *
				 * @since  1.0.0
				 *
				 * @param  string $prefix  Prefix used for transient and optgroup name.
				 */
				return sanitize_title( (string) apply_filters(
					__CLASS__ . '/' . __FUNCTION__,
					'custom_class'
				) );

		} // /get_variable_prefix



		/**
		 * Gets CSS class declaration shortcode name.
		 *
		 * Defaults to variable prefix.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @return  string  Filtered declaration shortcode.
		 */
		public static function get_declaration_shortcode() {

			// Output

				/**
				 * Filters declaration shortcode name used CSS code to declare a class.
				 *
				 * @since  1.0.0
				 *
				 * @param  string $name  Declaration shortcode name, defaults to variable prefix.
				 */
				return (string) apply_filters(
					__CLASS__ . '/' . __FUNCTION__,
					self::get_variable_prefix()
				);

		} // /get_declaration_shortcode



		/**
		 * Gets array of defined CSS classes.
		 *
		 * Returns array of `class-name => Class option label` pairs.
		 *
		 * If scope is left empty, the whole classes array is returned.
		 * If scope is set and found, the scope classes array is returned.
		 * If scope is set but not found, empty array is returned.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @param  string $scope  Either `global` or Beaver Builder module ID.
		 *
		 * @return  array  Array of CSS classes obtained from CSS code string.
		 */
		public static function get_classes_array( $scope = '' ) {

			// Helper variables

				$classes = self::cache_get();

				/**
				 * Filters the CSS code string from which we extract CSS class declarations.
				 *
				 * Use this filter to set the CSS code string.
				 *
				 * @since  1.0.0
				 *
				 * @param  string $css  CSS code string.
				 */
				$css_code = (string) apply_filters(
					__CLASS__ . '/' . __FUNCTION__ . '/css_code',
					''
				);


			// Processing

				if ( ! is_array( $classes ) ) {
					$classes = array();

					// Get CSS classes array from definitions in CSS code string.
					if ( ! empty( $css_code ) ) {
						$declaration_shortcode_name = self::get_declaration_shortcode();

						preg_match_all(
							'/(?:\[' . $declaration_shortcode_name . '((?:.*?\r?\n?)*)\])+/',
							$css_code,
							$declarations
						);

						$declarations = ( isset( $declarations[1] ) ) ? ( (array) $declarations[1] ) : ( array() );

						foreach ( $declarations as $declaration ) {
							$atts = self::get_class_atts( $declaration );

							if ( ! empty( $atts ) ) {
								foreach ( $atts['scope'] as $class_scope ) {
									if ( 'global' !== $class_scope ) {
										$class_scope = 'module:' . $class_scope;
									}
									$class_scope = str_replace( ':!', '_not:', $class_scope );
									$classes[ $class_scope ][ $atts['class'] ] = $atts['label'];
								}
								if ( isset( $atts['group'] ) ) {
									$classes['optgroup'][ $atts['group'] ][ $atts['class'] ] = $atts['label'];
								}
							}
						}
					}

					// Cache the CSS classes array.
					self::cache_set( $classes );
				}

				ksort( $classes );


			// Output

				if ( empty( $scope ) ) {
					return (array) $classes;
				} else if ( isset( $classes[ $scope ] ) ) {
					return (array) $classes[ $scope ];
				} else {
					return array();
				}

		} // /get_classes_array



		/**
		 * Gets a single CSS class attributes from declaration string.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @param  string $declaration  CSS declaration string to get attributes from.
		 *
		 * @return  array  Array of specific CSS class declaration attributes.
		 */
		public static function get_class_atts( $declaration ) {

			// Helper variables

				// Remove unwanted characters from the declaration string.
				$declaration = str_replace( array( '/', '*', ';' ), '', $declaration );

				// Extract the declaration attributes into array.
				$atts = array_filter( (array) shortcode_parse_atts( $declaration ) );


			// Requirements check

				// Return early if we do not have a class declared.
				if ( ! isset( $atts['class'] ) ) {
					return array();
				}

				// Remove the dot and trailing space from CSS class name declaration.
				$atts['class'] = trim( $atts['class'], '.' );

				// Again, return early if the class name is empty.
				if ( empty( $atts['class'] ) ) {
					return array();
				}


			// Processing

				// Set fallback label if it's not defined.
				if ( ! isset( $atts['label'] ) || empty( $atts['label'] ) ) {
					$atts['label'] = $atts['class'];
				}

				// Optional optgroup declaration.
				if ( isset( $atts['group'] ) ) {
					$atts['group'] = esc_html( trim( $atts['group'] ) );
				}

				// Set fallback scope if it's not defined.
				if ( ! isset( $atts['scope'] ) || empty( $atts['scope'] ) ) {
					$atts['scope'] = 'global';
				}

				// Convert CSS class scope to array of scopes.
				$atts['scope'] = preg_replace( '/[^a-z,\-!]/', '', $atts['scope'] );
				$atts['scope'] = explode( ',', $atts['scope'] );
				$atts['scope'] = array_filter( $atts['scope'] );

				// Sanitize/escape.
				$atts['class'] = sanitize_html_class( trim( $atts['class'] ) );
				$atts['label'] = esc_html( trim( $atts['label'] ) );
				$atts['scope'] = array_map( 'esc_attr', $atts['scope'] );


			// Output

				return (array) $atts;

		} // /get_class_atts



		/**
		 * Gets strings of text by scope/ID.
		 *
		 * Unfortunately, as the CSS class declaration in CSS code string
		 * is not localization ready, we do not need to use localization
		 * functions here either.
		 *
		 * Alternative approach would be to localize the strings below
		 * and simply use the CSS class name for CSS class label.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @param  string $scope
		 *
		 * @return  string  Filtered string of text for specific scope.
		 */
		public static function get_text( $scope ) {

			// Helper variables

				$string = '';

				$texts = array(
					'label-optgroup-global' => 'Custom global classes:',
					'label-optgroup-module' => 'Custom %s classes:', // %s = Module name/title.
					'label-option-column'   => 'Column',
					'label-option-empty'    => '- Choose a class -',
					'label-option-row'      => 'Row',
				);


			// Processing

				if ( isset( $texts[ $scope ] ) ) {
					$string = $texts[ $scope ];
				}


			// Output

				/**
				 * Filters texts used in the code (for option labels).
				 *
				 * @since  1.0.0
				 *
				 * @param  string $string  Returned text string.
				 * @param  string $scope   Scope of the text/text ID to return.
				 */
				return (string) apply_filters(
					__CLASS__ . '/' . __FUNCTION__,
					$string,
					$scope
				);

		} // /get_text





	/**
	 * 3) Cache
	 */

		/**
		 * Returns cached value.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @return  mixed  Cached value.
		 */
		public static function cache_get() {

			// Output

				return get_transient( self::cache_transient_name() );

		} // /cache_get



		/**
		 * Sets the cache to specific value.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @param  mixed $value  Value to store in the cache.
		 *
		 * @return  void
		 */
		public static function cache_set( $value ) {

			// Processing

				set_transient( self::cache_transient_name(), $value );

		} // /cache_set



		/**
		 * Flushes the cache.
		 *
		 * Hook this method onto appropriate action hook where you want to flush the cache.
		 * Such as on `customize_save_after` hook.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @return  void
		 */
		public static function cache_flush() {

			// Processing

				delete_transient( self::cache_transient_name() );

		} // /cache_flush



		/**
		 * Gets name of the cache transient.
		 *
		 * @since    1.0.0
		 * @version  1.0.0
		 *
		 * @return  string  Filtered transient option name.
		 */
		public static function cache_transient_name() {

			// Output

				/**
				 * Filters cache transient name.
				 *
				 * @since  1.0.0
				 *
				 * @param  string $transient  Transient name/ID.
				 */
				return (string) apply_filters(
					__CLASS__ . '/' . __FUNCTION__,
					self::get_variable_prefix() . '_' . strtolower( __CLASS__ )
				);

		} // /cache_transient_name





} // /Custom_CSS_Class_Select
