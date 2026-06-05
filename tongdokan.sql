
CREATE DATABASE IF NOT EXISTS tongdokan;
USE tongdokan;

DROP TABLE IF EXISTS staff;
CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    department VARCHAR(50),
    shift VARCHAR(20),
    status VARCHAR(20) DEFAULT 'active',
    joined_date DATE
);

INSERT INTO staff (username, password, full_name, role, department, shift, status, joined_date) VALUES
('siam',   'product123',  'Md Sakhoyat Hossain Siam',           'Product Manager',  'Catalogue',  'Morning', 'active', '2026-04-13'),
('ovy',    'order123',    'Azmain Hossain Ovy',                  'Order Manager',    'Operations', 'Morning', 'active', '2026-04-13'),
('khalid', 'customer123', 'Khalid Saifullah',                    'Customer Manager', 'Support',    'Morning', 'active', '2026-04-13'),
('shafin', 'staff123',    'Shafin Zaman',                        'Staff Manager',    'HR',         'Morning', 'active', '2026-04-13'),
('mahraz', 'payment123',  'Mohammod Golam Mortuza Mahraz',       'Payment Manager',  'Finance',    'Morning', 'active', '2026-04-13');

DROP TABLE IF EXISTS products;
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    category VARCHAR(80),
    origin VARCHAR(80),
    emoji VARCHAR(10),
    tag VARCHAR(40),
    is_available BOOLEAN DEFAULT TRUE,
    date_added DATE DEFAULT (CURRENT_DATE)
);

INSERT INTO products (product_name, description, price, stock_quantity, category, origin, emoji, tag) VALUES
('Jamdani Saree - Indigo Bloom',  'Hand-woven in Rupganj. Each motif takes 6 hours.',   79.00, 12, 'Saree & Fabric', 'Narayanganj', '🪡', 'Heritage'),
('Nakshi Kantha Throw',            'Stitched stories from Jessore grandmothers.',         29.00, 34, 'Home',           'Jessore',     '🧵', 'Bestseller'),
('Sundarban Wild Honey 500g',      'Mawalis collect this in monsoon. Untouched.',          9.00,  5, 'Snacks & Pithe', 'Khulna',      '🍯', 'Pure'),
('Sylhet Seven-Layer Tea Kit',     'Romesh Ram Gours legendary recipe at home.',          12.00,  0, 'Tea & Spice',    'Srimangal',   '🍵', 'Iconic'),
('Bishwa Ijtema Brass Lota',       'Hammered by Dhamrai brass-workers.',                  17.00, 15, 'Handicraft',     'Dhamrai',     '🫖', 'Artisan'),
('Chitoi Pitha Mix',               'Rice flour, gur, and your nanis blessing.',            4.00, 50, 'Snacks & Pithe', 'Comilla',     '🥞', 'Winter'),
('Tangail Cotton Saree',           'Featherlight. Made for Dhaka summers.',               39.00, 20, 'Saree & Fabric', 'Tangail',     '🌸', 'New'),
('Rickshaw Art Tin Tray',          'Painted by R. K. Das, third-gen rickshaw artist.',     7.00, 25, 'Home',           'Old Dhaka',   '🛺', 'Quirky'),
('Panch Phoron Spice Blend',       'Five seeds. Endless dishes. One bottle.',              3.00, 80, 'Tea & Spice',    'Bogra',       '🌶️', 'Pantry'),
('Shital Pati Floor Mat',          'Murta cane, hand-split. Cool as the name says.',      15.00, 10, 'Handicraft',     'Sylhet',      '🪷', 'UNESCO'),
('Comilla Roshmalai Box',          'Cold-chain shipped. Eat within 3 days. Worth it.',     6.00, 30, 'Snacks & Pithe', 'Comilla',     '🍮', 'Fresh'),
('Dhakai Muslin Scarf',            '300-thread weave. The fabric of empires, returning.', 49.00,  8, 'Saree & Fabric', 'Dhaka',       '✨', 'Revival');

DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    phone VARCHAR(30),
    location VARCHAR(100),
    total_orders INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    joined_date DATE
);

INSERT INTO customers (full_name, email, password, phone, location, total_orders, total_spent, status, joined_date) VALUES
('Rifa Ahmed',      'rifa@email.com',    'rifa1234',    '+1-416-555-0101', 'Toronto, Canada',    8, 476.00, 'vip',    '2026-01-15'),
('Nabil Islam',     'nabil@email.com',   'nabil1234',   '+880-1711-001122', 'Dhaka, Bangladesh', 3, 129.00, 'active', '2026-02-04'),
('Sumaiya Khan',    'sumaiya@email.com', 'sumaiya1234', '+44-7700-900100',  'London, UK',       12, 793.00, 'vip',    '2025-12-20'),
('Farhan Hossain',  'farhan@email.com',  'farhan1234',  '+61-455-001-002',  'Sydney, Australia', 1,  24.00, 'new',    '2026-04-22'),
('Tasnim Akter',    'tasnim@email.com',  'tasnim1234',  '+1-212-555-7788',  'New York, USA',     6, 407.00, 'active', '2026-03-10'),
('Arif Chowdhury',  'arif@email.com',    'arif1234',    '+880-1819-553344', 'Chittagong, Bangladesh', 2, 29.00, 'active', '2026-04-05');

DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    item_count INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address VARCHAR(200),
    status VARCHAR(20) DEFAULT 'pending',
    order_date DATE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

INSERT INTO orders (customer_id, item_count, total_amount, shipping_address, status, order_date) VALUES
(1, 3, 135.00, 'Toronto, Canada',         'delivered',  '2026-05-02'),
(2, 1,  79.00, 'Dhaka, Bangladesh',       'processing', '2026-05-03'),
(3, 5,  48.00, 'London, UK',              'shipped',    '2026-05-04'),
(4, 2,  24.00, 'Sydney, Australia',       'pending',    '2026-05-05'),
(5, 4, 178.00, 'New York, USA',           'processing', '2026-05-06'),
(6, 1,   6.00, 'Chittagong, Bangladesh',  'cancelled',  '2026-05-07');

DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(40),
    status VARCHAR(20) DEFAULT 'pending',
    delivery_status VARCHAR(40),
    payment_date DATE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

INSERT INTO payments (order_id, amount, method, status, delivery_status, payment_date) VALUES
(1, 135.00, 'PayPal',    'completed', 'Delivered',   '2026-05-02'),
(2,  79.00, 'Card',      'completed', 'In Transit',  '2026-05-03'),
(3,  48.00, 'Apple Pay', 'completed', 'Shipped',     '2026-05-04'),
(4,  24.00, 'PayPal',    'pending',   'Pending',     '2026-05-05'),
(5, 178.00, 'Card',      'completed', 'Processing',  '2026-05-06'),
(6,   6.00, 'Apple Pay', 'refunded',  'Cancelled',   '2026-05-07');
