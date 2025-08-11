# Facebook Sign-In Plugin for WordPress

🚀 **A simple, secure, and feature-rich WordPress plugin that adds Facebook authentication to your site using a shortcode.**

[![GitHub License](https://img.shields.io/github/license/Great0s/facebook-pro-sdk-php)](https://github.com/Great0s/facebook-pro-sdk-php/blob/main/LICENSE)
[![WordPress Compatibility](https://img.shields.io/badge/WordPress-5.0%2B-blue)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple)](https://www.php.net/)
[![Facebook SDK](https://img.shields.io/badge/Facebook%20SDK-Included-green)](https://developers.facebook.com/docs/php/)

## 🎯 **Overview**

This plugin seamlessly integrates Facebook login functionality into your WordPress site. Users can log in or register using their Facebook account with just one click. The plugin automatically handles user registration, data synchronization, and provides a smooth authentication experience.

## ✨ **Key Features**

### 🔐 **Authentication & Security**
- **Facebook OAuth2 Integration** - Secure authentication via Facebook
- **WordPress Nonce Protection** - CSRF attack prevention
- **Automatic Password Generation** - Secure random passwords for new users
- **HTTPS Support** - Full SSL compatibility for production sites

### 👥 **User Management**
- **Automatic Registration** - New users registered automatically
- **Existing User Login** - Seamless login for returning users
- **Role Assignment** - Configurable user roles (default: subscriber)
- **Profile Data Sync** - Import Facebook profile information

### 📊 **Data Synchronization**
- **Profile Information** - Name, email, gender, birthday
- **Profile Photos** - Facebook profile picture integration
- **Location Data** - User location information
- **WooCommerce Compatible** - Billing information sync

### 🎨 **Easy Integration**
- **Shortcode Support** - `[facebook-sign-in]` anywhere on your site
- **AJAX-Powered** - Smooth, non-blocking user experience
- **Custom Redirects** - Configurable post-login/logout URLs
- **Admin Notifications** - Email alerts for new registrations

### 🛠️ **Developer Friendly**
- **WordPress Hooks** - Extensive action and filter hooks
- **Customizable Fields** - Configure which Facebook data to retrieve
- **Error Handling** - Graceful error management and logging
- **Extensible Architecture** - Easy to customize and extend
## 🚀 **Quick Start**

### 1. **Installation**
```bash
# Download or clone the plugin
git clone https://github.com/Great0s/facebook-pro-sdk-php.git

# Move to WordPress plugins directory
mv facebook-pro-sdk-php /path/to/wordpress/wp-content/plugins/
```

### 2. **Facebook App Setup**
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app and get your `App ID` and `App Secret`
3. Set OAuth redirect URI: `https://yourdomain.com/wp-admin/admin-ajax.php`

### 3. **Plugin Configuration**
```php
// Edit main.php - Add your Facebook credentials
$FB = new \Facebook\Facebook([
    'app_id' => 'YOUR_APP_ID',
    'app_secret' => 'YOUR_APP_SECRET',
    'default_graph_version' => 'v2.10'
]);
```

### 4. **WordPress Setup**
1. Activate the plugin in WordPress admin
2. Enable user registration: `Settings > General > Anyone can register`
3. Add the shortcode wherever you want the login button:
   ```
   [facebook-sign-in]
   ```

## 💻 **Usage Examples**

### Basic Implementation
```php
// In any post, page, or widget
[facebook-sign-in]
```

### Custom Hooks
```php
// Custom actions after Facebook login
add_action('wp_login', 'my_facebook_login_handler', 10, 2);
function my_facebook_login_handler($user_login, $user) {
    // Send welcome email
    wp_mail($user->user_email, 'Welcome!', 'Thanks for joining via Facebook!');
    
    // Log the event
    error_log('New Facebook user: ' . $user_login);
}
```

### Retrieve Facebook Data
```php
// Get user's Facebook profile photo
$profile_photo = get_user_meta($user_id, 'profile_photo', true);

// Get user's Facebook location
$location = get_user_meta($user_id, 'billing_address_1', true);
```

## 🔧 **Configuration Options**

### Facebook Data Fields
Customize which data to retrieve from Facebook by editing the Graph API call:
```php
// In main.php - Modify the fields parameter
$response = $FB->get("me?fields=id,first_name,last_name,email,picture.type(large)", $access_token);
```

### User Role Assignment
```php
// Change default role for new users
'role' => 'customer' // Default is 'subscriber'
```

### Custom Redirects
```php
// Redirect after login
wp_redirect('/welcome-page/');

// Redirect after logout  
wp_redirect('/goodbye-page/');
```

## � **File Structure**
```
facebook-pro-sdk-php/
├── main.php                 # Main plugin file
├── Facebook/                # Facebook PHP SDK
│   ├── autoload.php
│   ├── Facebook.php
│   └── ...
├── config/
│   └── production.php
├── examples/
│   └── real_api_test.php
├── docs.md                  # Complete documentation
└── README_SDK.md           # This file
```

## 🔐 **Security Features**

- **OAuth2 Authentication** - Industry-standard secure authentication
- **WordPress Nonces** - CSRF protection for all forms
- **HTTPS Enforcement** - SSL required for production use
- **Secure Password Generation** - Random passwords for new users
- **Input Validation** - All user inputs are sanitized and validated

## 🚨 **Requirements**

- **WordPress:** 5.0 or higher
- **PHP:** 7.2 or higher  
- **SSL Certificate:** Required for Facebook authentication
- **Facebook App:** With valid App ID and Secret

## 🛠️ **Troubleshooting**

### Common Issues

**Login button not appearing?**
- Check plugin is activated
- Verify shortcode spelling: `[facebook-sign-in]`
- Check for PHP errors in logs

**Users not being created?**
- Enable WordPress user registration
- Verify Facebook App permissions include 'email'
- Check Facebook account has verified email

**SSL/HTTPS errors?**
- Facebook requires HTTPS for authentication
- Ensure valid SSL certificate is installed

## 📋 **Testing Checklist**

- [ ] Facebook App created and configured
- [ ] App ID and Secret added to plugin
- [ ] WordPress user registration enabled
- [ ] Plugin activated
- [ ] Shortcode displays login button
- [ ] Login redirects to Facebook
- [ ] User data syncs correctly
- [ ] New users assigned correct role
- [ ] Admin notifications working
- [ ] HTTPS enabled

## 🤝 **Contributing**

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 **Support**

- **Documentation:** [Complete docs](docs.md)
- **Issues:** [GitHub Issues](https://github.com/Great0s/facebook-pro-sdk-php/issues)
- **Community:** [Discussions](https://github.com/Great0s/facebook-pro-sdk-php/discussions)

## 🙏 **Credits**

- Built with the [Facebook PHP SDK](https://developers.facebook.com/docs/php/)
- Created by [GreatOs](https://github.com/Great0S)
- Inspired by the WordPress community

---

**⭐ If this plugin helped you, please star the repository!**
echo $facebook->getDebugToolbar();
```

### API Profiling

```php
use Facebook\Development\ApiProfiler;

$profiler = new ApiProfiler($monitor, $logger);

$result = $profiler->profile('user_fetch', function() use ($facebook) {
    return $facebook->get('/me', $accessToken);
});
```

## 📚 Advanced Features

### Custom Cache Backends

```php
use Facebook\Cache\CacheInterface;

class RedisCacheAdapter implements CacheInterface {
    // Implement Redis caching
}

$facebook = new FacebookSDK([
    'cache.driver' => new RedisCacheAdapter()
]);
```

### Custom Retry Strategies

```php
use Facebook\Http\RetryHandler;

$retryHandler = new RetryHandler(5, 2000, 60000); // 5 retries, 2s base delay, 60s max
$facebook->setRetryHandler($retryHandler);
```

## 🔒 Security Best Practices

1. **Use App Secret Proof** - Always enable app secret proof for advanced security
2. **Validate Webhook Signatures** - Verify all webhook requests using HMAC validation
3. **Secure Token Storage** - Use secure storage for access tokens
4. **Rate Limit Compliance** - Respect Facebook's rate limits to avoid blocking
5. **Input Validation** - Validate all user inputs before API calls

## 📈 Performance Optimization

1. **Enable Caching** - Use appropriate cache TTL values for your use case
2. **Batch Requests** - Combine multiple API calls into batch requests
3. **Async Operations** - Use async requests for non-blocking operations
4. **Monitor Performance** - Use built-in performance monitoring
5. **Optimize Uploads** - Use chunked uploads for large files

## 🤝 Contributing

We welcome contributions! Please read our contributing guidelines and submit pull requests to our repository.

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

- **Documentation**: Check the examples folder for detailed usage examples
- **Issues**: Report bugs and feature requests on GitHub
- **Community**: Join our community discussions

---

**Made with ❤️ for the PHP community**
