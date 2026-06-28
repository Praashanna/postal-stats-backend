# Postal Stats Backend

A Laravel 12 API-only backend designed to work alongside [Postal MTA](https://github.com/postalserver/postal) to provide enhanced email statistics and analytics capabilities.

## 📧 What is this?

This project addresses some limitations in Postal MTA's built-in statistics by providing:

- **Enhanced Analytics** - More detailed email statistics and insights
- **Time Series Data** - Chart-ready data for opens, bounces, and delivery metrics
- **Multi-Server Management** - Manage statistics across multiple Postal installations
- **Advanced Filtering** - Filter data by domains, time periods, and custom criteria
- **CSV Export** - Export detailed reports for further analysis
- **Real-time Connection Testing** - Verify Postal database connectivity

## 🎯 Why was this created?

While [Postal MTA](https://github.com/postalserver/postal) is an excellent open-source mail delivery platform, its statistics interface has some limitations:

- Limited historical data visualization
- Basic filtering and search capabilities
- No multi-server statistics aggregation
- Limited export options for detailed analysis

This backend provides a robust API that connects directly to Postal's database to offer enhanced statistics and analytics capabilities.

## 🚀 Features

### 📊 Enhanced Statistics
- **Server Statistics** - Comprehensive stats for individual Postal servers
- **Combined Analytics** - Aggregate statistics across multiple servers
- **Bounce Analysis** - Detailed bounce statistics by domain and email
- **Open Tracking** - Email open statistics and tracking
- **Time Series Charts** - Hourly and daily chart data for trends

### 🔧 Server Management
- **Multi-Server Support** - Connect to multiple Postal installations
- **Connection Testing** - Real-time database connectivity validation
- **Server Status Management** - Enable/disable servers dynamically
- **Secure Configuration** - Encrypted password storage

### 📤 Export Capabilities
- **CSV Export** - Export bounce data and opened addresses
- **Filtered Exports** - Export data with custom filters
- **Bulk Data Access** - API endpoints for bulk data retrieval

### 🔐 Security & Production Ready
- **JWT Authentication** - Secure API access
- **Production-Safe Error Handling** - No sensitive data exposure
- **Rate Limiting** - Built-in API protection
- **Comprehensive Logging** - Detailed operation logs

## 🖥️ Frontend

This backend is designed to work with the [Postal Stats Frontend](https://github.com/Praashanna/postal-stats) which provides a modern web interface for visualizing and managing your email statistics.

**Get the Frontend:** [https://github.com/Praashanna/postal-stats](https://github.com/Praashanna/postal-stats)

## 🛠️ Installation

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/MariaDB
- Access to Postal MTA database(s)

### Quick Setup

```bash
# Clone the repository
git clone https://github.com/your-username/postal-stats-backend.git
cd postal-stats-backend

# Install dependencies
composer install

# Set up environment
cp .env.example .env
# Edit .env with your database settings

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# Start the development server
php artisan serve
```

## 📖 API Documentation

### Authentication
All API endpoints require JWT authentication:

```bash
# Login to get token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# Use token in subsequent requests
curl -H "Authorization: Bearer your_jwt_token" \
  http://localhost:8000/api/servers
```

### Key Endpoints

- **GET** `/api/servers` - List all Postal servers
- **GET** `/api/servers/{id}` - Show a Postal server
- **POST** `/api/servers/{id}/test-connection` - Test a Postal server message database connection
- **GET** `/api/stats/server/{id}` - Get server statistics
- **GET** `/api/stats/server/{id}/suppressions` - List recipient suppressions with pagination and search
- **DELETE** `/api/stats/server/{id}/suppressions` - Remove suppressions by scope, domain, preset, or exact address
- **GET** `/api/stats/server/{id}/bounces` - Get bounce data
- **GET** `/api/export/server/{id}/bounces` - Export bounce data as CSV

## 🔧 CLI Tools

The backend includes command-line tools for configuration:

```bash
# Load Postal DB settings from /opt/postal/config/postal.yml
php artisan postal:load-config

# Create user account
php artisan create:account
```

## ⚙️ Configuration

### Postal Servers

Postal servers are read directly from Postal's `main_db.servers` table. The app does not store or sync servers into its own database.

```bash
# Default path: /opt/postal/config/postal.yml
php artisan postal:load-config

# Custom path
php artisan postal:load-config /path/to/postal.yml
```

### Environment Variables

Key configuration options:

```bash
# Database (for this application)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=postal_stats_backend

# JWT Configuration
JWT_SECRET=your_jwt_secret
JWT_TTL=60

# CORS (for frontend)
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

## 🏢 About

This project is developed and maintained by **Praashanna** at [**No Stress Limited**](https://hostmaria.com) - a hosting and web services company focused on providing reliable email and web hosting solutions.

### Links
- **Postal MTA (Official):** [https://github.com/postalserver/postal](https://github.com/postalserver/postal)
- **Frontend Repository:** [https://github.com/Praashanna/postal-stats](https://github.com/Praashanna/postal-stats)
- **No Stress Limited:** [https://hostmaria.com](https://hostmaria.com)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## 📝 License

This project is open-source software licensed under the [MIT license](LICENSE).

## 🆘 Support

If you encounter any issues or need help, open an issue on GitHub

## 🙏 Acknowledgments

- **Postal Team** - For creating the excellent [Postal MTA](https://github.com/postalserver/postal)
- **Laravel Team** - For the robust framework
- **Community Contributors** - For feedback and contributions

---

**Made with ❤️ by [Prashanna](https://github.com/Praashanna) at [No Stress Limited](https://hostmaria.com)**
