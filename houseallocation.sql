CREATE DATABASE php_project;
GO

USE php_project;
GO

--create admins table
CREATE TABLE admin_users (
    id INT PRIMARY KEY IDENTITY(1,1),
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);
GO

--create users table
CREATE TABLE users (
    id INT PRIMARY KEY IDENTITY(1,1),
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);
GO

-- Create notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY IDENTITY(1,1),
    user_email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date_created DATETIME NOT NULL,
    is_read BIT DEFAULT 0
);
GO

-- Create properties table
CREATE TABLE properties (
    id INT PRIMARY KEY IDENTITY(1,1),
    property_type VARCHAR(100) NOT NULL,
    price_range VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    area INT NOT NULL,
    capacity VARCHAR(50) NOT NULL,
    description TEXT NOT NULL
);
GO
ALTER TABLE properties
ADD deposit_fee INT;


-- Create buyers table
CREATE TABLE buyers (
    id INT PRIMARY KEY IDENTITY(1,1),
    property_id INT NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
GO
ALTER TABLE buyers
ADD occupation VARCHAR(100) NOT NULL DEFAULT 'Other';

ALTER TABLE buyers ADD payment_method VARCHAR(20);


-- Create feedback table
CREATE TABLE feedback (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_email VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    date_submitted DATETIME DEFAULT GETDATE(),
    status VARCHAR(50) DEFAULT 'unread'
);
GO

--create payments table
CREATE TABLE payments (
    id INT PRIMARY KEY IDENTITY(1,1),
    invoice_id VARCHAR(100),
    property_id INT,
    amount DECIMAL(10,2),
    phone VARCHAR(20),
    status VARCHAR(50),
    payment_method VARCHAR(20),
    created_at DATETIME DEFAULT GETDATE()
);
GO

ALTER TABLE payments
ADD mpesa_receipt VARCHAR(50);

ALTER TABLE payments
ADD CONSTRAINT FK_payments_property_id
FOREIGN KEY (property_id) REFERENCES properties(id);

SELECT * FROM payments ORDER BY created_at DESC;


INSERT INTO properties 
(property_type, price_range, location, area, capacity, description, deposit_fee)
VALUES 
('Apartment', '320,000', 'Eldoret, Kapsoya', 32, '1-2 persons', 'Modern apartment near Rupa Mall and Eldoret Hospital.', 12000),
('Apartment', '280,000', 'Eldoret, Langas', 32, '1-2 persons', 'Accessible location near Langas Market and public transport.', 10000),
('Apartment', '300,000', 'Eldoret, Elgon View', 32, '1-2 persons', 'Serene neighborhood close to Eldoret Club and hospitals.', 13000),
('Residential Lot', '220,000', 'Turbo, Township', 50, 'N/A (Lot only)', 'Gated plot close to Turbo Town center and market.', 10000),
('Condo', '350,000', 'Eldoret, West Indies', 35, '1-2 persons', 'Secure area near Uasin Gishu Primary and sports clubs.', 14000),
('House and Lot', '390,000', 'Moi’s Bridge, Township', 60, '3-4 persons', 'Growing estate near Moi’s Bridge trading center.', 25000),
('Apartment', '330,000', 'Eldoret, Maili Nne', 40, '2-3 persons', 'Near University of Eldoret access route and shopping zones.', 18000),
('Commercial', '380,000', 'Burnt Forest, Market Area', 70, 'N/A (Commercial)', 'Prime space near the matatu terminus and produce market.', 20000),
('Residential Lot', '240,000', 'Kesses, Moi University Vicinity', 80, 'N/A (Lot only)', 'Quiet area ideal for development near Moi University.', 12000),
('House and Lot', '360,000', 'Ziwa, Town Outskirts', 65, '3-4 persons', 'Peaceful suburb near Ziwa Technical and shopping zone.', 22000),
('Condo', '340,000', 'Eldoret, Pioneer', 30, '1 person', 'Secure gated community near Eldoret Polytechnic.', 11000),
('Residential Lot', '180,000', 'Flax, Chepkorio Road', 100, 'N/A (Lot only)', 'Lush plot ideal for residential use near scenic views.', 10000);
GO


SELECT * FROM properties;
SELECT * FROM admin_users;
SELECT * FROM users;
SELECT * FROM notifications;
SELECT * FROM buyers;
SELECT * FROM feedback;
SELECT * FROM payments;


DELETE FROM payments
SELECT occupation FROM buyers;



UPDATE payments
SET status = 'Paid';


DBCC CHECKIDENT ('payments', RESEED, 0);
GO

-- Check if table exists and has data
SELECT COUNT(*) FROM notifications;

-- Check what emails are in the table
SELECT DISTINCT user_email FROM notifications;

-- Check if your specific email has notifications
SELECT * FROM notifications WHERE user_email = 'your_email_here';


SELECT COUNT(*) AS feedback_count FROM feedback
SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'feedback';

INSERT INTO feedback (user_email, subject, message) 





