# Enable Beaver Builder custom CSS classes selector

This PHP class makes it easy to enable CSS classes selector for *"CSS class"* Beaver Builder settings form field, and adds options for each CSS class parsed from definitions within the CSS code string.

*Requires at least Beaver Builder v2.1.4.*


## How to load this code?

To load this functionality, copy the `custom-css-class-select.php` file into your theme or plugin and load it using:

```
require_once 'custom-css-class-select.php';
add_filter( 'fl_builder_field_js_config', 'Custom_CSS_Class_Select::set_class_options', 10, 3 );
```

(Surely, adapt the code to your needs, especially the file insertion part.)


## How to set the CSS code string to parse?

Simply hook your function returning the CSS code string onto `Custom_CSS_Class_Select/get_classes_array/css_code` filter hook. Here is an example for parsing the content of *"Additional CSS"* customizer field:

```
add_filter( 'Custom_CSS_Class_Select/get_classes_array/css_code', 'wp_get_custom_css' );
```

As the CSS classes are being cached, do not forget to flush the cache when needed. In our case, we flush the cache on customizer saving:

```
add_action( 'customize_save_after', 'Custom_CSS_Class_Select::cache_flush' );
```


## How to define a CSS class in CSS code string?

For this PHP class to work properly, you need to define your CSS classes in the CSS code string in special way. Add the CSS class definition into a CSS comment:

```
/**
 * My custom CSS class
 *
 * You can describe how the class should be applied
 * and what it does here.
 *
 * Custom CSS class declaration:
 * =============================
 * [custom_class
 *   class="my-custom-css-class"
 *   label="My custom CSS class label"
 *   scope="global, !rich-text, !html"
 *   /]
 */

  .my-custom-css-class {
    ...your CSS styles here...
  }
```

CSS class declaration explained:

1. Write the custom CSS class declarations as parameters of `[custom_class /]` "shortcode" in a CSS comment within your CSS code.
2. Required: declare CSS class name using `class="my-custom-css-class"`.
3. Optional: declare CSS class dropdown option label using `label="My custom CSS class label"`. Falls back to the class name.
4. Optional: declare CSS class scope using `scope="global"`. Falls back to `global`.
  The CSS class scope can be set to specific [Beaver Builder module(s) ID](https://github.com/webmandesign/custom-css-class-select/wiki/Beaver-Builder-modules-reference) only, such as `scope="rich-text, html"`, or to global using `scope="global"`.
  In case you want to remove the CSS class dropdown option from a specific Beaver Builder module(s), set it to global scope and negate the module where you do not want the class to be selectable by prepending the module ID with exclamation mark (`!`), such as `scope="global, !rich-text, !html"`.
  Separate multiple values with comma.


## What hooks are available in the code?

There are several filter hooks available in this code, named using `__CLASS__ . '/' . __FUNCTION__` (`. '/' . $variable_name`) naming convention. For specific hook information please see the code directly.

- `Custom_CSS_Class_Select/cache_transient_name` - For customizing the cache transient option name. (Can be found in `cache_transient_name` method.)
- `Custom_CSS_Class_Select/get_classes_array/css_code` - For passing the CSS code string where the CSS classes are declared. You need to use this filter hook for setup (see "How to set the CSS code string to parse?" above). (Can be found in `get_classes_array` method.)
- `Custom_CSS_Class_Select/get_declaration_shortcode` - For customizing the CSS class declaration shortcode name. Defaults to variable prefix of `custom_class` (so the shortcode used in string of CSS code would become `[custom_class /]`). (Can be found in `get_declaration_shortcode` method.)
- `Custom_CSS_Class_Select/get_text` - For customizing the default strings of text for specific scopes used in the code. See the code for reference. (Can be found in `get_text` method.)
- `Custom_CSS_Class_Select/get_variable_prefix` - For customizing the variable prefix used in the code. Default value is `custom_class`. (Can be found in `get_variable_prefix` method.)

---

More information can be found in [Wiki pages](https://github.com/webmandesign/custom-css-class-select/wiki).

&copy; [WebMan Design, Oliver Juhas](https://www.webmandesign.eu) | Licensed under [GPL v3.0](https://www.gnu.org/licenses/gpl-3.0.html)
