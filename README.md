# Facebook Sign-In Plugin for WordPress

A professional WordPress plugin that enables seamless Facebook authentication and user registration through a simple shortcode implementation.

## Overview

This plugin integrates Facebook login functionality into WordPress websites, allowing users to authenticate using their Facebook credentials. It automatically handles user registration, profile data synchronization, and provides a secure authentication workflow.

## Features

- **Facebook OAuth2 Authentication** - Secure login via Facebook's authentication system
- **Automatic User Registration** - Creates WordPress accounts for new Facebook users
- **Profile Data Synchronization** - Imports user information from Facebook profiles
- **Shortcode Integration** - Easy implementation with `[facebook-sign-in]` shortcode
- **WordPress Security** - Built-in nonce protection and CSRF prevention
- **Admin Notifications** - Email alerts for new user registrations
- **Customizable Redirects** - Configure post-login and logout destination URLs
- **WooCommerce Compatible** - Syncs billing information for e-commerce sites

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- SSL certificate (HTTPS required for Facebook authentication)
- Facebook Developer Account and App credentials

## Installation

1. **Download the Plugin**
   ```bash
   git clone https://github.com/Great0s/facebook-pro-sdk-php.git
   ```

2. **Upload to WordPress**
   - Place the plugin folder in `/wp-content/plugins/`
   - Activate the plugin through the WordPress admin panel

3. **Configure Facebook App**
   - Create a Facebook App at [Facebook Developers](https://developers.facebook.com/)
   - Obtain your App ID and App Secret
   - Set OAuth redirect URI to: `https://yourdomain.com/wp-admin/admin-ajax.php`

4. **Plugin Configuration**
   - Open `main.php` and add your Facebook credentials:
   ```php
   $FB = new \Facebook\Facebook([
       'app_id' => 'YOUR_APP_ID',
       'app_secret' => 'YOUR_APP_SECRET',
       'default_graph_version' => 'v2.10'
   ]);
   ```

5. **WordPress Settings**
   - Enable user registration: `Settings > General > Anyone can register`
   - Add the shortcode to any page, post, or widget: `[facebook-sign-in]`

## Usage

### Basic Implementation
Add the Facebook login button to any content area:
```
[facebook-sign-in]
```

### Custom Hooks
Extend functionality with WordPress hooks:
```php
// Custom action after Facebook login
add_action('wp_login', 'custom_facebook_login_handler', 10, 2);
function custom_facebook_login_handler($user_login, $user) {
    // Send welcome email
    wp_mail($user->user_email, 'Welcome!', 'Thank you for registering!');
}
```

### Retrieving Facebook Data
Access synchronized Facebook profile data:
```php
// Get profile photo URL
$profile_photo = get_user_meta($user_id, 'profile_photo', true);

// Get user location
$location = get_user_meta($user_id, 'billing_address_1', true);
```

## Configuration Options

### Facebook Data Fields
Customize which Facebook profile fields to retrieve by modifying the Graph API call in `main.php`:
```php
$response = $FB->get("me?fields=id,first_name,last_name,email,picture.type(large)", $access_token);
```

### User Role Assignment
Configure the default role for new users:
```php
'role' => 'subscriber' // Change to desired role
```

### Custom Redirects
Set custom URLs for post-authentication redirects:
```php
wp_redirect('/custom-welcome-page/');
```

## Security

- **OAuth2 Protocol** - Industry-standard authentication
- **WordPress Nonces** - CSRF attack prevention
- **HTTPS Enforcement** - Secure data transmission
- **Input Validation** - Sanitized user data processing
- **Secure Password Generation** - Random passwords for new accounts

## Troubleshooting

### Common Issues

**Login button not displaying**
- Verify plugin activation
- Check shortcode syntax: `[facebook-sign-in]`
- Review PHP error logs

**User registration failing**
- Enable WordPress user registration
- Ensure Facebook App has email permission
- Verify user's Facebook email is verified

**SSL/HTTPS errors**
- Install valid SSL certificate
- Update WordPress URLs to HTTPS
- Configure Facebook App for HTTPS redirect

## File Structure

```
facebook-pro-sdk-php/
├── main.php                 # Primary plugin file
├── Facebook/                # Facebook PHP SDK library
├── config/                  # Configuration files
├── examples/                # Usage examples
├── docs.md                  # Detailed documentation
└── README.md               # This file
```

## Testing Checklist

- [ ] Facebook App configured with correct credentials
- [ ] WordPress user registration enabled
- [ ] Plugin activated in WordPress admin
- [ ] Shortcode displays login button
- [ ] Facebook authentication workflow functional
- [ ] User data synchronization working
- [ ] Admin notifications being sent
- [ ] HTTPS properly configured

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## Support

- **Documentation**: [Complete Documentation](docs.md)
- **Issues**: [GitHub Issues](https://github.com/Great0s/facebook-pro-sdk-php/issues)
- **Community**: [Discussions](https://github.com/Great0s/facebook-pro-sdk-php/discussions)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- **Author**: [GreatOs](https://github.com/Great0S)
- **Facebook SDK**: [Facebook PHP SDK](https://developers.facebook.com/docs/php/)
- **WordPress Community**: Inspired by WordPress development best practices

---

**If this plugin has been helpful, please consider starring the repository ⭐**
