# Advanced Shortcode & Snippet Engine

A production-ready WordPress plugin for creating, managing, and executing PHP, CSS, JavaScript, HTML, and JSON snippets with dynamic shortcode support.

## 🚀 Features

- **6 Snippet Types**: PHP, CSS, JavaScript, HTML, JSON, SQL
- **Dynamic Shortcodes**: Auto-generated from snippets
- **Conditional Execution**: Location, user, device, URL, time-based rules
- **Code Editor**: CodeMirror with syntax highlighting
- **Revision History**: Track changes and rollback
- **REST API**: Full API integration
- **Security Framework**: Sanitization, escaping, nonce verification

## 📦 Installation

1. Upload to `/wp-content/plugins/advanced-shortcode-snippet-engine/`
2. Activate via WordPress admin
3. Navigate to "Snippet Engine" menu

## 📖 Documentation

See [readme.txt](readme.txt) for full documentation.

## 🔧 Development

### Requirements
- WordPress 5.6+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.1+

### File Structure

```
advanced-shortcode-snippet-engine/
├── advanced-shortcode-snippet-engine.php  # Main plugin file
├── uninstall.php                          # Cleanup on deletion
├── composer.json                          # Dependencies
├── readme.txt                             # WordPress.org readme
├── README.md                              # This file
├── core/                                  # Core classes
├── admin/                                 # Admin functionality
├── frontend/                              # Frontend rendering
├── snippets/                              # Snippet executors
├── database/                              # Database operations
├── conditions/                            # Condition evaluation
├── api/                                   # REST API
├── security/                              # Security measures
├── includes/                              # Helpers and utilities
├── assets/                                # CSS, JS, images
└── languages/                             # Translations
```

### Coding Standards

This plugin follows WordPress Coding Standards:

```bash
composer cs      # Check code style
composer cs-fix  # Fix code style issues
composer test    # Run tests
```

## 📝 Usage

### Creating a Snippet

1. Go to Snippet Engine → Add New
2. Enter title and code
3. Select type (PHP, CSS, JS, etc.)
4. Set scope (global, shortcode, or both)
5. Configure conditions (optional)
6. Save and activate

### Shortcode Syntax

```
[asse_my_snippet]
[asse_my_snippet attr="value"]
[asse_my_snippet]content[/asse_my_snippet]
```

### Merge Tags

Available in all snippet types:

- `{user_id}`, `{user_name}`, `{user_email}`
- `{post_id}`, `{post_title}`, `{post_content}`
- `{site_url}`, `{site_name}`
- `{date_current}`, `{time_current}`
- `{get_param.name}`, `{cookie_name}`

## 🔒 Security

The plugin includes multiple security layers:

- Nonce verification for all admin actions
- Capability-based access control
- PHP syntax validation
- Dangerous function blocking
- Context-aware output escaping
- Safe mode for emergency recovery

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and code style checks
5. Submit a pull request

## 📄 License

GPL v2 or later - see [LICENSE](LICENSE) for details.

## 👨‍💻 Developer

Minhas Hussain

## 🙏 Credits

Built following WordPress Plugin Boilerplate principles.
