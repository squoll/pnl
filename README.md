# 📺 IPTV Management Dashboard

> The ultimate solution for managing IPTV clients, providers, and financials.

![Dashboard Banner](https://iptv.standigital.lv/img/2.png)

## ✨ Features

A powerful, responsive, and intuitive control panel designed for IPTV resellers and providers.

### 📊 Core Features

*   **Dashboard Analytics**: Real-time financial reports, income vs expenses tracking, and subscription growth charts
*   **Client Management**: Track subscriptions, expiration dates, contact details, and payment history
*   **Provider Management**: Organize different IPTV providers and their costs
*   **Payment Tracking**: Comprehensive payment history with profit calculations
*   **Account Settings**: User profile and password management

### 🗺️ Interactive Map

*   **Auto Geocoding**: Automatically converts addresses to coordinates using OpenStreetMap Nominatim
*   **Manual Geocoding**: Autocomplete search for failed geocoding addresses
*   **Clustering**: Visualizes high-density client areas with marker clusters
*   **Status Filtering**: Filter map markers by subscription status (Active, Expired, New)
*   **Batch Processing**: Geocode all clients at once with `geocode_all.php`

### 🔔 Smart Notifications

*   **Visual Alerts**: Dashboard notifications for expiring subscriptions (7 days, 14 days)
*   **SMS Templates**: Customizable SMS templates with dynamic variables (`{NAME}`, `{DAYS}`, `{DATE}`)
*   **One-click Sending**: Mobile SMS app integration or clipboard copy for desktop
*   **Expiration Tracking**: Real-time count of expiring subscriptions via API

### 🛡️ Advanced Security

*   **Security Logs**: Detailed audit trail of failed logins, IP blocks, and critical actions
*   **Brute-force Protection**: Automatic IP blocking after 5 failed login attempts (15-minute ban)
*   **Session Security**: CSRF protection, honeypot fields, and session hijacking prevention
*   **SQL Injection Prevention**: PDO prepared statements throughout
*   **XSS Protection**: All output properly escaped with `htmlspecialchars()`
*   **Log Management**: View and delete security logs to prevent database bloat

### 📈 System Health & Analytics

*   **ARPU Tracking**: Average Revenue Per User calculated over 30-day periods
*   **Churn Rate**: Monitor customer retention with automatic churn calculations
*   **Database Statistics**: View table sizes, row counts, and last update times
*   **Database Backup**: One-click SQL backup download with complete schema and data
*   **Performance Metrics**: Active vs total clients, revenue trends, and growth indicators

### 🌍 Multi-language Support

*   **Fully Localized**: Complete interface in **Russian (RU)** and **English (EN)**
*   **Easy Extension**: Simple array-based translation system
*   **Runtime Switching**: Change language on-the-fly without logout
*   **Consistent Keys**: All UI elements use the `t()` translation function

### 📱 Responsive Design

*   **Mobile Optimized**: Works perfectly on smartphones and tablets
*   **Desktop Ready**: Full-featured experience on larger screens
*   **Touch Friendly**: Optimized touch targets and gestures

### 🔧 Developer Tools

*   **Debug Payments**: View last 100 payments with detailed breakdown
*   **API Endpoints**: RESTful endpoints for geocoding and data retrieval
*   **Automated Setup**: Web-based installer for easy deployment

## 🚀 Getting Started

### Prerequisites

*   **PHP 7.4+** with extensions:
    *   PDO (MySQL)
    *   JSON
    *   cURL (for geocoding)
    *   Session support
*   **MySQL 5.7+** or **MariaDB 10.2+**
*   **Web Server**: Apache 2.4+ or Nginx 1.18+
*   **Modern Browser**: Chrome, Firefox, Safari, or Edge (for map features)

### 📥 Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/squoll/pnl.git
    cd pnl
    ```

2.  **Setup Configuration**
    
    Open `setup.php` in your browser to run the automatic installer:
    ```
    http://your-server/pnl/setup.php
    ```
    
    The installer will:
    - Test database connection
    - Create required tables (`tv_clients`, `tv_providers`, `tv_payments`, `users`, `security_logs`)
    - Set up initial admin account
    - Configure database credentials

3.  **Manual Configuration (Optional)**
    
    Rename `config/db.example.php` to `config/db.php` and edit:
    ```php
    <?php
    $host = 'localhost';
    $db = 'your_database';
    $user = 'your_username';
    $pass = 'your_password';
    ```

4.  **Login**
    
    Navigate to `login.php` and use the credentials created during setup.

### 🗄️ Database Schema

The system uses the following main tables:

*   **`tv_clients`**: Client information, subscription dates, addresses, coordinates
*   **`tv_providers`**: IPTV provider details and costs
*   **`tv_payments`**: Payment records with income, expenses, and profit calculations
*   **`users`**: Admin user accounts with hashed passwords
*   **`security_logs`**: Login attempts, IP blocks, and security events

## 📖 Usage Guide

### Managing Clients

1. **Add Client**: Click "Add Client" from dashboard or clients page
2. **Auto-geocoding**: Address is automatically geocoded when saved
3. **Manual Geocoding**: If auto-geocoding fails, use the map page to manually geocode
4. **Edit/Delete**: Use action buttons in the clients table

### SMS Notifications

1. Navigate to **SMS Settings** in the menu
2. Create templates with variables:
   - `{NAME}` - Client's first name
   - `{DAYS}` - Days until expiration
   - `{DATE}` - Expiration date
3. Click SMS icon next to expiring clients to send

### Viewing Analytics

1. **Dashboard**: Overview of income, expenses, and active clients
2. **System Health**: Detailed ARPU, churn rate, and database statistics
3. **Debug Payments**: Verify payment calculations and yearly totals

### Backup & Restore

**Backup:**
1. Go to **System Health** page
2. Click "Download Backup" button
3. Save the `.sql` file securely

**Restore:**
```bash
mysql -u username -p database_name < backup_file.sql
```

## 🔌 API Endpoints

### `api/geocode_single.php`
Geocode a single address using OpenStreetMap Nominatim.

**Method:** POST  
**Parameters:**
- `address` (string): Full address to geocode

**Response:**
```json
{
  "success": true,
  "lat": 56.9496,
  "lon": 24.1052
}
```

### `api/get_expiring_count.php`
Get count of subscriptions expiring within specified days.

**Method:** GET  
**Parameters:**
- `days` (int): Number of days threshold

**Response:**
```json
{
  "count": 5
}
```

## 🛠️ Technical Stack

*   **Backend**: PHP 7.4+ with PDO
*   **Database**: MySQL/MariaDB
*   **Frontend**: HTML5, CSS3, JavaScript (ES6+)
*   **CSS Framework**: Bootstrap 5
*   **Icons**: Unicons
*   **Maps**: Leaflet.js with OpenStreetMap tiles
*   **Geocoding**: OpenStreetMap Nominatim API
*   **Charts**: Chart.js (for analytics)

## 🌍 Language Support

The system is built with i18n in mind.

**Switch Language:**
- Use the dropdown in the top-right menu
- Language preference is stored in session

**Add New Language:**
1. Copy `lang/en.php` to `lang/your_code.php`
2. Translate all array values
3. Add language option to `includes/header.php`

**Translation Function:**
```php
<?= htmlspecialchars(t('translation_key')) ?>
```

## 🔒 Security Features

*   **Session Hijacking Protection**: User agent and IP validation
*   **Rate Limiting**: 5 failed login attempts → 15-minute IP ban
*   **SQL Injection Prevention**: PDO prepared statements exclusively
*   **XSS Protection**: All output escaped with `htmlspecialchars()`
*   **CSRF Protection**: Honeypot fields and token validation
*   **Password Security**: Bcrypt hashing with cost factor 12
*   **Secure Headers**: Content-Security-Policy and X-Frame-Options

## 🐛 Troubleshooting

### Geocoding Not Working

**Issue:** Addresses not converting to coordinates

**Solutions:**
1. Check internet connectivity (requires external API access)
2. Verify OpenStreetMap Nominatim is accessible
3. Use manual geocoding from the map page
4. Check `geocode_all.php` for batch processing errors

### Map Not Displaying

**Issue:** Map page shows blank or errors

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify Leaflet.js and OpenStreetMap tiles are loading
3. Ensure clients have valid coordinates (not NULL)
4. Clear browser cache

### Login Issues

**Issue:** Cannot login or "Invalid credentials" error

**Solutions:**
1. Verify database connection in `config/db.php`
2. Check if IP is blocked in `security_logs` table
3. Wait 15 minutes if rate-limited
4. Reset password via database if needed

### Database Connection Failed

**Issue:** "Could not connect to database" error

**Solutions:**
1. Verify MySQL/MariaDB service is running
2. Check credentials in `config/db.php`
3. Ensure database exists and user has permissions
4. Test connection: `mysql -u username -p database_name`

### SMS Not Sending

**Issue:** SMS button not working

**Solutions:**
1. On mobile: Check if SMS app is installed
2. On desktop: Use clipboard copy feature
3. Verify SMS template has valid variables
4. Check client has valid phone number

## 📸 Screenshots Gallery

| Dashboard Overview | Client Management |
|:---:|:---:|
| ![Dashboard](https://iptv.standigital.lv/img/2.png) | ![Clients](https://iptv.standigital.lv/img/7.png) |
| *Visualizes income, active users, and quick actions* | *Filterable list of all clients with status indicators* |

| Financial Reports | Analysis Charts |
|:---:|:---:|
| ![Finance](https://iptv.standigital.lv/img/3.png) | ![Charts](https://iptv.standigital.lv/img/3.png) |
| *Monthly breakdown of earnings and costs* | *Interactive graphs for business growth* |

| Client Map | Security Logs |
|:---:|:---:|
| ![Map](https://iptv.standigital.lv/img/5.png) | ![Security](https://iptv.standigital.lv/img/6.png) |
| *Geocoded client locations with status clusters* | *Audit trail of all login attempts and blocks* |

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

**Code Style:**
- Follow PSR-12 coding standards for PHP
- Use meaningful variable names
- Comment complex logic
- Use the `t()` function for all user-facing strings

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

*   **OpenStreetMap** - Geocoding and map tiles
*   **Leaflet.js** - Interactive map library
*   **Bootstrap** - UI framework
*   **Chart.js** - Analytics visualizations
