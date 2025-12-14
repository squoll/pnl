# 📺 IPTV Management Dashboard

> The ultimate solution for managing IPTV clients, providers, and financials.

![Dashboard Banner](https://via.placeholder.com/1200x500.png?text=Dashboard+Overview+Screenshot)
*(Please replace with a screenshot of the main dashboard showing charts and stats)*

## ✨ Features

A powerful, responsive, and intuitive control panel designed for IPTV resellers and providers.

*   **📊 Analytics & Insights**: Real-time financial reports, income vs expenses tracking, and subscription growth charts.
*   **👥 Client Management**: Track subscriptions, expiration dates, and contact details.
*   **📡 Provider Management**: Organize different IPTV providers and their costs.
*   **🗺️ Interactive Map (New!)**:
    *   **Geocoding**: Automatically converts addresses to coordinates using OSM.
    *   **Clustering**: Visualizes high-density client areas.
    *   **Filtering**: Filter map markers by subscription status (Active, Expired, New).
*   **🔔 Smart Notifications**:
    *   Visual alerts for expiring subscriptions (7 days, 14 days).
    *   **SMS Templates**: Customizable SMS templates with dynamic variables (`{NAME}`, `{DAYS}`).
    *   **One-click Sending**: Integrates with mobile SMS apps or clipboard for Desktop.
*   **🌍 Multi-language Support**: Fully localized in **Russian (RU)** and **English (EN)** with easy extensibility to other languages.
*   **🛡️ Advanced Security**:
    *   **Security Logs**: Detailed audit trail of failed logins, IP blocks, and critical actions.
    *   **Brute-force protection**: IP blocking after 5 failed attempts.
    *   **Secure Session**: CSRF protection, Honeypots, and Session Hijacking prevention.
*   **📱 Responsive Design**: Works perfectly on desktop, tablet, and mobile.

## 🚀 Getting Started

### Prerequisites
*   PHP 7.4 or higher
*   MySQL/MariaDB
*   Web Server (Apache/Nginx)

### 📥 Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/yourusername/iptv-dashboard.git
    cd iptv-dashboard
    ```

2.  **Setup Configuration**
    Open `setup.php` in your browser to run the automatic installer:
    ```
    http://your-server/iptv-dashboard/setup.php
    ```
    Follow the on-screen instructions to connect your database.

3.  **Manual Configuration (Optional)**
    Rename `config/db.example.php` to `config/db.php` and edit your credentials manually.

## 📸 Screenshots Gallery

| Dashboard Overview | Client Management |
|:---:|:---:|
| ![Dashboard](https://via.placeholder.com/600x400?text=Main+Dashboard) | ![Clients](https://via.placeholder.com/600x400?text=Client+List) |
| *Visualizes income, active users, and quick actions* | *Filterable list of all clients with status indicators* |

| Financial Reports | Analysis Charts |
|:---:|:---:|
| ![Finance](https://via.placeholder.com/600x400?text=Financial+Report) | ![Charts](https://via.placeholder.com/600x400?text=Growth+Charts) |
| *Monthly breakdown of earnings and costs* | *Interactive graphs for business growth* |

| Client Map | Security Logs |
|:---:|:---:|
| ![Map](https://via.placeholder.com/600x400?text=Interactive+Map) | ![Security](https://via.placeholder.com/600x400?text=Security+Logs) |
| *Geocoded client locations with status clusters* | *Audit trail of all login attempts and blocks* |

### 🖼️ Recommended Screenshots to Take
To make your GitHub page look professional, please capture:
1.  **Main Dashboard**: Show the top cards and the graphs populated with some data.
2.  **Clients List**: Show the `tv_clients.php` page with the status indicators (colors).
3.  **Mobile View**: A screenshot from a phone to show responsiveness.
4.  **SMS Feature**: Show the prompt when clicking "SMS" (optional).

## 🌍 Language Support

The system is built with i18n in mind.
- **Switch Language**: Use the dropdown in the top-right menu.
- **Add New Language**: Copy `lang/en.php` to `lang/your_code.php` and translate the array values.

## 🔒 Security Features

- **Session Hijacking Protection**
- **Rate limiting** on login attempts (5 attempts -> 15 min ban)
- **SQL Injection prevention** via PDO Prepared Statements
- **XSS Protection** on output

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
