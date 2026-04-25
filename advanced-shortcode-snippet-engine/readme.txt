=== Advanced Shortcode & Snippet Engine ===
Contributors: minhas
Tags: shortcode, snippets, code, php, css, javascript, html, json, sql, custom code, code editor
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A production-ready WordPress plugin for creating, managing, and executing PHP, CSS, JavaScript, HTML, and JSON snippets with dynamic shortcode support.

== Description ==

**Advanced Shortcode & Snippet Engine** is a powerful WordPress plugin that allows you to create, manage, and execute code snippets directly from your WordPress admin panel. Add custom functionality to your site without editing theme files or installing multiple plugins.

= Key Features =

* **Multiple Snippet Types**: Support for PHP, CSS, JavaScript, HTML, JSON, and SQL snippets
* **Dynamic Shortcodes**: Automatically generate shortcodes from your snippets
* **Conditional Execution**: Control where and when snippets run based on location, user, device, URL, time, and more
* **Code Editor**: Built-in CodeMirror editor with syntax highlighting for all snippet types
* **Revision History**: Track changes and rollback to previous versions
* **Import/Export**: Backup and share your snippets easily
* **REST API**: Full API support for external integrations
* **Security First**: Input sanitization, output escaping, nonce verification, and dangerous function blocking
* **Performance Optimized**: Caching, minification, and duplicate execution prevention

= Shortcode Examples =

`[asse_my_snippet]` - Basic shortcode
`[asse_my_snippet attr1="value1" attr2="value2"]` - With attributes
`[asse_my_snippet]Nested content[/asse_my_snippet]` - With content

= Merge Tags =

Use smart variables in your snippets:
* `{user_id}`, `{user_name}`, `{user_email}`
* `{post_id}`, `{post_title}`, `{post_content}`
* `{site_url}`, `{site_name}`
* `{date_current}`, `{time_current}`
* `{get_parameter.name}`, `{cookie_name}`

= Security Features =

* Nonce verification for all admin actions
* Capability-based access control
* PHP syntax validation before saving
* Dangerous function blocking (eval, exec, system, etc.)
* Safe mode for emergency recovery
* Context-aware output escaping

= Developer Friendly =

* PSR-4 autoloading
* Modular architecture
* Extensive hooks and filters
* REST API endpoints
* Template override system

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/advanced-shortcode-snippet-engine/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Snippet Engine' in the admin menu
4. Create your first snippet!

== Frequently Asked Questions ==

= Is this plugin safe to use? =

Yes! The plugin includes multiple security layers including input sanitization, output escaping, nonce verification, and dangerous function blocking. However, as with any code execution plugin, only trusted users should have access.

= Can I use this with page builders? =

Yes! Shortcodes work with all major page builders including Elementor, WPBakery, Divi, and Beaver Builder.

= How do I create a shortcode? =

1. Create a new snippet
2. Set the scope to "shortcode" or "both"
3. Use the slug as your shortcode tag (prefixed with `asse_`)
4. For example, a snippet with slug "my-feature" becomes `[asse_my_feature]`

= Can I schedule snippets? =

Yes! You can set time-based conditions to activate/deactivate snippets on specific dates and times.

== Screenshots ==

1. Dashboard with snippet statistics
2. Snippet list table with filtering
3. Code editor with syntax highlighting
4. Condition builder interface
5. Settings page

== Changelog ==

= 1.0.0 =
* Initial release
* Support for 6 snippet types (PHP, CSS, JS, HTML, JSON, SQL)
* Dynamic shortcode generation
* Conditional execution engine
* Revision history
* Import/Export functionality
* REST API integration
* Security framework

== Upgrade Notice ==

= 1.0.0 =
Initial release of Advanced Shortcode & Snippet Engine.
