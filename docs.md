
# Facebook Sign-In Plugin Documentation


## Features & Detailed Documentation


### Facebook Login Button via Shortcode
**Description:** Easily add a Facebook login button anywhere on your site using the `[facebook-sign-in]` shortcode.
**How to Use:**
1. Edit any post, page, or widget in WordPress.
2. Insert the shortcode:
   ```
   [facebook-sign-in]
   ```
3. Save and view the page. The Facebook login button will appear.


### Automatic User Registration
**Description:** New users signing in with Facebook are automatically registered in WordPress.
**How to Use:**
- No manual action required. When a user logs in with Facebook and their email does not exist in WordPress, the plugin creates a new user account using their Facebook profile data.


### Existing User Login
**Description:** Existing users can log in with their Facebook account.
**How to Use:**
- No manual action required. If a user logs in with Facebook and their email matches an existing WordPress user, they are logged in automatically.


### User Data Sync
**Description:** Retrieves and stores user profile data from Facebook (ID, first name, last name, gender, email, birthday, location, profile picture).
**How to Use:**
- Data is automatically synced during login/registration.
**How to Customize:**
1. Open `main.php`.
2. Find the line:
   ```php
   $response = $FB->get("me?fields=id,first_name,last_name,gender,email,birthday,location,picture.type(large)", $access_token);
   ```
3. Add or remove fields as needed.


### Secure Authentication
**Description:** Uses Facebook OAuth2 and WordPress nonces for secure, tamper-proof authentication.
**How to Use:**
- No manual action required. Security is built-in and handled by the plugin.


### Admin Notification
**Description:** Sends email notifications to the site admin when a new user registers via Facebook.
**How to Use:**
- No manual action required. Admin receives an email when a new user registers.
**How to Customize:**
- Use WordPress hooks to modify or extend notifications.


### Custom Redirects
**Description:** Redirects users to the homepage or custom URLs after login and logout.
**How to Use:**
- By default, users are redirected to the homepage after login/logout.
**How to Customize:**
1. Open `main.php`.
2. Edit the lines containing `wp_redirect(home_url());` to change the destination URL.


### AJAX Support
**Description:** Handles login and signup via AJAX for a seamless, modern user experience.
**How to Use:**
- No manual action required. AJAX is used automatically for login/signup.


### Password Generation
**Description:** Automatically generates secure passwords for new users.
**How to Use:**
- No manual action required. Passwords are generated automatically for new users.


### Role Assignment
**Description:** Assigns the 'subscriber' role to new users by default.
**How to Customize:**
1. Open `main.php`.
2. Find the array for `wp_insert_user` and change `'role' => 'subscriber'` to your desired role (e.g., `'role' => 'customer'`).


### Profile Photo Sync
**Description:** Stores the user's Facebook profile photo in their WordPress profile.
**How to Use:**
- The photo URL is stored in user meta. Retrieve it with:
   ```php
   get_user_meta($user_id, 'profile_photo', true);
   ```


### Location Sync
**Description:** Stores the user's Facebook location in their WordPress profile.
**How to Use:**
- The location is stored in user meta. Retrieve it with:
   ```php
   get_user_meta($user_id, 'billing_address_1', true);
   ```


### Billing Info Sync
**Description:** Optionally syncs billing info fields for WooCommerce compatibility.
**How to Use:**
- Billing fields are automatically filled for new users. You can access them via WooCommerce or user meta.


### Logout Redirect
**Description:** Redirects users to the homepage after logout for improved UX.
**How to Customize:**
1. Open `main.php`.
2. Edit the `logoutRedirect` function to change the redirect URL.


### Extensible Hooks
**Description:** Uses WordPress hooks (`do_action`) for extensibility and custom workflows.
**How to Use:**
- Add your own functions to WordPress hooks. Example:
   ```php
   add_action('wp_login', 'my_custom_login_action', 10, 2);
   function my_custom_login_action($user_login, $user) {
         // Custom code here
   }
   ```


### Error Handling
**Description:** Gracefully handles Facebook API errors and login failures.
**How to Use:**
- No manual action required. Errors are handled and displayed automatically.


### Configurable Data Fields
**Description:** Easily adjust which Facebook profile fields are requested and stored.
**How to Customize:**
1. Open `main.php`.
2. Edit the Graph API query string to add or remove fields:
   ```php
   $response = $FB->get("me?fields=id,first_name,last_name,gender,email,birthday,location,picture.type(large)", $access_token);
   ```


### Supports HTTPS
**Description:** Fully compatible with secure (SSL) WordPress sites.
**How to Use:**
- Make sure your WordPress site uses HTTPS. Facebook requires SSL for authentication.


### Easy Setup
**Description:** Minimal configuration required; just add your Facebook App credentials in `main.php`.
**How to Use:**
1. Create a Facebook App and get your App ID and Secret.
2. Open `main.php` and set:
   ```php
   $FB = new \Facebook\Facebook([
      'app_id' => 'YOUR_APP_ID',
      'app_secret' => 'YOUR_APP_SECRET',
      'default_graph_version' => 'v2.10'
   ]);
   ```


## Overview

The Facebook Sign-In Plugin allows you to add a Facebook login button anywhere on your WordPress website using a shortcode. It leverages the Facebook PHP SDK to authenticate users via Facebook and automatically registers or logs them in to your WordPress site. This plugin is ideal for sites that want to simplify user registration and login using social authentication.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Facebook App (for API credentials)

## Installation

1. **Clone or Download the Plugin**
   - Place the plugin folder in your WordPress `wp-content/plugins` directory.

2. **Install Facebook PHP SDK**
   - The SDK is included in the `Facebook/` directory. No extra installation is required.

3. **Activate the Plugin**
   - Go to your WordPress admin dashboard → Plugins → Activate "Simple Facebook Login".

## Configuration

### 1. Create a Facebook App
- Go to [Facebook Developers](https://developers.facebook.com/)
- Create a new app and get your `App ID` and `App Secret`.
- Set the OAuth redirect URI to your site's AJAX handler (e.g., `https://yourdomain.com/wp-admin/admin-ajax.php`).

### 2. Configure Plugin Settings
- Open `main.php` and set your Facebook App credentials:
  ```php
  $FB = new \Facebook\Facebook([
      'app_id' => 'YOUR_APP_ID',
      'app_secret' => 'YOUR_APP_SECRET',
      'default_graph_version' => 'v2.10'
  ]);
  ```
- Optionally, adjust the requested user data fields in the Graph API call.

### 3. Shortcode Usage
- Add the Facebook login button anywhere using:
  ```
  [facebook-sign-in]
  ```

## How It Works

1. **User clicks Facebook Login button**
2. **Redirects to Facebook for authentication**
3. **On success, Facebook redirects back to your site (AJAX handler)**
4. **Plugin verifies nonce and retrieves user data from Facebook**
5. **If user does not exist, registers a new WordPress user**
6. **If user exists, logs them in**
7. **Redirects user to homepage or desired location**

## Security
- Uses WordPress nonces to prevent CSRF attacks.
- Facebook OAuth2 ensures secure authentication.
- User passwords are randomly generated for new users.

## Customization
- You can modify the fields requested from Facebook in the Graph API call.
- Change the redirect URLs after login/logout by editing the respective functions in `main.php`.
- Customize the login button style by editing the HTML in the shortcode handler.

## Troubleshooting
- Ensure your Facebook App is live and the redirect URI matches your site's AJAX handler.
- Check that your App ID and Secret are correct.
- Make sure your site uses HTTPS for Facebook authentication.
- If you see undefined function errors, make sure the plugin is running inside WordPress.

## File Structure
- `main.php`: Main plugin logic and hooks
- `Facebook/`: Facebook PHP SDK library
- `config/`: Configuration files
- `examples/`: Example usage and test scripts

## Support
For issues or feature requests, open an issue on [GitHub](https://github.com/Great0s/facebook-sign-in-plugin).

## 📋 Quick Reference

### Shortcode Usage
```php
// Basic usage
[facebook-sign-in]

// With custom CSS class (requires modification)
[facebook-sign-in class="my-custom-button"]
```

### Hook Examples
```php
// Custom user creation logic
add_action('wp_login', 'my_facebook_login_handler', 10, 2);
function my_facebook_login_handler($user_login, $user) {
    // Your custom code here
    error_log('Facebook user logged in: ' . $user_login);
    
    // Send welcome email
    wp_mail($user->user_email, 'Welcome!', 'Thanks for signing up with Facebook!');
}

// Modify user data before saving
add_filter('wp_insert_user_data', 'modify_facebook_user_data', 10, 1);
function modify_facebook_user_data($data) {
    // Custom user data modifications
    return $data;
}

// Custom redirect after login
add_action('wp_login', 'custom_facebook_redirect', 10, 2);
function custom_facebook_redirect($user_login, $user) {
    if (isset($_SESSION['facebook_login'])) {
        wp_redirect('/welcome-page/');
        exit;
    }
}
```

### Configuration Validation
```php
// Check if plugin is properly configured
function is_facebook_plugin_configured() {
    global $FB;
    return !empty($FB) && 
           !empty($FB->getApp()->getId()) && 
           !empty($FB->getApp()->getSecret());
}

// Validate Facebook App settings
function validate_facebook_app() {
    try {
        global $FB;
        $response = $FB->get('/me?fields=id', 'YOUR_ACCESS_TOKEN');
        return true;
    } catch (Exception $e) {
        error_log('Facebook App validation failed: ' . $e->getMessage());
        return false;
    }
}
```

## 🎨 Customization Examples

### Custom Button Styling
```php
// In your theme's functions.php
add_filter('facebook_login_button_html', 'custom_facebook_button');
function custom_facebook_button($html) {
    return '<button class="btn btn-facebook" onclick="window.location.href=\'' . $fullUrl . '\';">
                <i class="fab fa-facebook-f"></i> Continue with Facebook
            </button>';
}
```

### Custom User Fields
```php
// Add custom fields to user registration
add_action('user_register', 'save_facebook_custom_fields');
function save_facebook_custom_fields($user_id) {
    if (isset($_SESSION['facebook_user_data'])) {
        $facebook_data = $_SESSION['facebook_user_data'];
        
        // Save additional fields
        update_user_meta($user_id, 'facebook_id', $facebook_data['id']);
        update_user_meta($user_id, 'facebook_gender', $facebook_data['gender']);
        update_user_meta($user_id, 'facebook_birthday', $facebook_data['birthday']);
        
        unset($_SESSION['facebook_user_data']);
    }
}
```

### Custom Error Handling
```php
// Handle Facebook login errors gracefully
add_action('facebook_login_error', 'handle_facebook_error');
function handle_facebook_error($error_message) {
    // Log error
    error_log('Facebook Login Error: ' . $error_message);
    
    // Show user-friendly message
    wp_die('Sorry, there was an issue with Facebook login. Please try again or contact support.');
}
```

## 🔍 Testing Checklist

### Pre-deployment Testing
- [ ] Facebook App created and configured
- [ ] App ID and Secret added to code
- [ ] WordPress user registration enabled (`Settings > General > Anyone can register`)
- [ ] Plugin activated in WordPress admin
- [ ] Shortcode displays login button on frontend
- [ ] Login redirects to Facebook correctly
- [ ] Facebook permissions requested correctly
- [ ] User data syncs to WordPress properly
- [ ] New users receive correct role assignment
- [ ] Existing users can log in with Facebook
- [ ] Logout works and redirects properly
- [ ] Admin notifications sent for new users
- [ ] HTTPS enabled (required for production)

### Production Testing
- [ ] Test with different Facebook accounts
- [ ] Test with existing WordPress users
- [ ] Test error scenarios (invalid credentials, network issues)
- [ ] Verify user data privacy compliance
- [ ] Test mobile responsiveness
- [ ] Performance testing under load

## 🐛 Common Issues & Solutions

### Issue: "Undefined function" errors
**Solution:** These errors occur when the plugin is analyzed outside WordPress. They disappear when the plugin runs inside WordPress.

### Issue: Facebook login returns to admin-ajax.php
**Solution:** This is normal behavior. The AJAX handler processes the login and redirects users appropriately.

### Issue: Users not being created
**Solution:** 
1. Check that WordPress user registration is enabled
2. Verify Facebook App permissions include 'email'
3. Ensure the Facebook account has a verified email

### Issue: Login button not appearing
**Solution:**
1. Verify the plugin is activated
2. Check that the shortcode is spelled correctly: `[facebook-sign-in]`
3. Ensure there are no PHP errors preventing the plugin from loading

### Issue: SSL certificate errors
**Solution:** Facebook requires HTTPS for authentication. Ensure your site has a valid SSL certificate.

## 📊 Performance Optimization

### Caching Considerations
```php
// Cache Facebook user data to reduce API calls
function cache_facebook_user_data($user_id, $facebook_data) {
    set_transient('facebook_user_' . $user_id, $facebook_data, HOUR_IN_SECONDS);
}

function get_cached_facebook_data($user_id) {
    return get_transient('facebook_user_' . $user_id);
}
```

### Database Optimization
```php
// Index user meta for faster lookups
add_action('init', 'add_facebook_indexes');
function add_facebook_indexes() {
    global $wpdb;
    $wpdb->query("ALTER TABLE {$wpdb->usermeta} ADD INDEX facebook_id_index (meta_key, meta_value(191))");
}
```

## 🔐 Security Best Practices

### Environment Variables
```php
// Use environment variables for sensitive data
$FB = new \Facebook\Facebook([
    'app_id' => getenv('FACEBOOK_APP_ID'),
    'app_secret' => getenv('FACEBOOK_APP_SECRET'),
    'default_graph_version' => 'v18.0'
]);
```

### Rate Limiting
```php
// Implement rate limiting for login attempts
function check_facebook_login_rate_limit($ip_address) {
    $attempts = get_transient('facebook_login_attempts_' . $ip_address);
    if ($attempts >= 5) {
        wp_die('Too many login attempts. Please try again later.');
    }
    set_transient('facebook_login_attempts_' . $ip_address, $attempts + 1, 15 * MINUTE_IN_SECONDS);
}
```

## 📈 Analytics & Tracking

### Track Login Events
```php
// Google Analytics tracking
add_action('wp_login', 'track_facebook_login');
function track_facebook_login($user_login, $user) {
    if (isset($_SESSION['facebook_login'])) {
        ?>
        <script>
        gtag('event', 'login', {
            'method': 'Facebook'
        });
        </script>
        <?php
    }
}
```

## 🌐 Internationalization

### Multi-language Support
```php
// Add translation support
add_action('plugins_loaded', 'facebook_login_load_textdomain');
function facebook_login_load_textdomain() {
    load_plugin_textdomain('facebook-login', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Use translatable strings
return __('Registrations are closed for now!', 'facebook-login');
```
