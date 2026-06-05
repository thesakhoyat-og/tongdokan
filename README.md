# Tong Dokan — Bangladeshi E-Commerce Platform

> "From the tong to your door" — A full-stack e-commerce web application built using PHP, MySQL and XAMPP as part of a Software Engineering Agile Project.

---

## Project Overview

Tong Dokan is a web-based e-commerce platform that sells authentic Bangladeshi products to customers worldwide. The system includes a public-facing storefront where customers can browse and purchase products, and a secure admin panel where staff members manage their individual components.

The project was built following **Agile/SCRUM methodology** over 7 sprints, with daily standups, sprint reviews, and a product backlog managed on Jira.

---

## Team Members

| Name | Role | Component |
|------|------|-----------|
| Md Sakhoyat Hossain Siam | Product Manager | Product Management System |
| Azmain Hossain Ovy | Order Manager | Order Management System |
| Khalid Saifullah | Customer Manager | Customer Management System |
| Shafin Zaman | Staff Manager | Staff Management System |
| Mohammod Golam Mortuza Mahraz | Payment Manager | Payment & Delivery System |

---

## Technologies Used

| Technology | Purpose |
|-----------|---------|
| PHP 8.x | Server-side scripting |
| MySQL | Database (via phpMyAdmin) |
| HTML5 / CSS3 | Frontend structure and styling |
| XAMPP | Local development server (Apache + MySQL) |
| JavaScript | Form confirmations and basic interactions |
| Git / GitHub | Version control |
| Jira | Agile backlog and sprint management |

---

## Project Structure

```
tongdokan/
│
├── index.php                        ← Public storefront (shared)
├── login.php                        ← Staff login page
├── logout.php                       ← Staff logout handler
├── customer_login.php               ← Customer login page
├── customer_register.php            ← Customer registration
├── customer_dashboard.php           ← Customer account and order history
├── customer_logout.php              ← Customer logout handler
├── profile.php                      ← Staff change username and password
├── db_connect.php                   ← MySQL database connection
├── styles.css                       ← All CSS styles
├── tongdokan.sql                    ← Database setup file (import in phpMyAdmin)
├── README.md                        ← This file
│
├── dashboards/
│   ├── product_management.php       ← Siam's component
│   ├── order_management.php         ← Ovy's component
│   ├── customer_management.php      ← Khalid's component
│   ├── staff_management.php         ← Shafin's component
│   └── payment_delivery.php         ← Mahraz's component
│
└── includes/
    ├── header.php                   ← Shared dashboard sidebar and nav
    └── footer.php                   ← Shared dashboard footer
```

---

## Database Tables

| Table | Owner | Description |
|-------|-------|-------------|
| `staff` | Shafin | Login credentials and staff info |
| `products` | Siam | All product listings |
| `customers` | Khalid | Customer accounts |
| `orders` | Ovy | Customer orders |
| `payments` | Mahraz | Payment and delivery records |

---

## How to Run Locally (XAMPP)

### Step 1 — Install XAMPP
Download and install XAMPP from https://www.apachefriends.org

### Step 2 — Copy project files
Copy the entire `tongdokan` folder into your XAMPP htdocs directory:
```
Windows:  C:\xampp\htdocs\tongdokan
Mac:      /Applications/XAMPP/htdocs/tongdokan
```

### Step 3 — Start XAMPP
Open XAMPP Control Panel and start:
- Apache
- MySQL

### Step 4 — Import the database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click the **Import** tab
3. Click **Choose File** and select `tongdokan.sql`
4. Click **Go**

### Step 5 — Open the website
```
http://localhost/tongdokan/index.php
```

---

## Login Credentials

### Staff Login
Go to: `http://localhost/tongdokan/login.php`

| Username | Password | Dashboard |
|----------|----------|-----------|
| `siam` | `product123` | Product Management |
| `ovy` | `order123` | Order Management |
| `khalid` | `customer123` | Customer Management |
| `shafin` | `staff123` | Staff Management |
| `mahraz` | `payment123` | Payment & Delivery |

### Customer Login
Go to: `http://localhost/tongdokan/customer_login.php`

| Email | Password |
|-------|----------|
| `rifa@email.com` | `rifa1234` |
| `nabil@email.com` | `nabil1234` |
| `sumaiya@email.com` | `sumaiya1234` |

Or register a new account at: `http://localhost/tongdokan/customer_register.php`

---

## Features

### Public Storefront
- Browse all available products by category
- Search products by name or keyword
- Filter products by category
- View product details including origin, price and description
- Customer registration and login
- Customer order history and account management

### Staff Admin Panel

**Product Management (Siam)**
- Add, edit and delete products
- Search and filter by name, origin or stock status
- Toggle product visibility on the storefront
- View live stock value and stat cards

**Order Management (Ovy)**
- View all customer orders
- Update order status (pending to processing to shipped to delivered)
- Track delivery progress

**Customer Management (Khalid)**
- Add and remove customers
- Search and filter customers by name, email or status
- Update customer status (new / active / VIP / inactive)

**Staff Management (Shafin)**
- Add and remove staff members
- Update staff status (active / inactive / on leave)
- View department overview

**Payment & Delivery (Mahraz)**
- View all transactions
- Update payment status (pending / completed / refunded)
- Update delivery status
- View payment method breakdown (PayPal / Card / Apple Pay)

**All Staff**
- Change own username and password from the Profile page
- Changes reflect instantly in the database

---

## Security Notes

- All database queries use **prepared statements** to prevent SQL injection
- Sessions are used for authentication — staff and customers have separate sessions
- Role-based access control — each staff member can only access their own dashboard
- Passwords are stored as plain text (suitable for academic demo — production would use password_hash())

---

## Agile Process

This project followed SCRUM methodology:

- 7 sprints over 7 weeks
- Daily standup meetings (10-15 minutes)
- Weekly sprint reviews with the teacher
- Product backlog maintained on Jira
- Source code version controlled on GitHub
- Individual journals submitted each week documenting progress

---

## License

This project was built for academic purposes as part of a Software Engineering module.
2026 Tong Dokan Team — Khalid, Ovy, Siam, Shafin & Mahraz
