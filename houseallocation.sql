CREATE DATABASE php_project;
GO

USE php_project;
GO

CREATE TABLE admin_users (
    id INT PRIMARY KEY IDENTITY(1,1),
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);
GO


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


INSERT INTO properties (property_type, price_range, location, area, capacity, description)
VALUES 
('Apartment', '100,000 - 300,000', 'Nairobi, Roysambu', 32, '1-2 persons', 'Located near TRM, Kenyatta University, and Thika Road transport corridor.'),
('Apartment', '100,000 - 300,000', 'Nakuru, Free Area', 32, '1-2 persons', 'Near Egerton University campus annex, supermarkets, and matatu routes.'),
('Apartment', '150,000 - 300,000', 'Kisumu, Nyalenda', 32, '1-2 persons', 'Close to Kisumu CBD, shopping centers, and Kisumu Polytechnic.'),
('Residential Lot', '200,000 - 300,000', 'Machakos, Matuu', 50, 'N/A (Lot only)', 'Situated in a gated area near local markets, schools, and dispensaries.'),
('Condo', '250,000 - 300,000', 'Kakamega, Amalemba', 35, '1-2 persons', 'Located near Masinde Muliro University and Kakamega Forest Park.'),
('House and Lot', '280,000 - 300,000', 'Bungoma, Kanduyi', 60, '3-4 persons', 'Located in a developing estate near Bungoma town and schools.'),
('Apartment', '180,000 - 300,000', 'Embu, Majimbo', 40, '2-3 persons', 'Close to Embu University and Embu Level 5 Hospital.'),
('Commercial', '250,000 - 300,000', 'Kitale, Matisi', 70, 'N/A (Commercial)', 'Near Kitale Bus Park and agricultural produce market.'),
('Residential Lot', '200,000 - 300,000', 'Kajiado, Isinya', 80, 'N/A (Lot only)', 'Quiet area near public transport, markets, and churches.'),
('House and Lot', '200,000 - 300,000', 'Nyeri, King''ong''o', 65, '3-4 persons', 'Peaceful neighborhood near Nyeri town and tea-growing areas.'),
('Condo', '200,000 - 300,000', 'Nyahururu, Town Centre', 30, '1 person', 'Close to Nyahururu Waterfalls, parks, and shopping areas.'),
('Residential Lot', '100,000 - 200,000', 'Taita Taveta, Voi', 100, 'N/A (Lot only)', 'Quiet zone near Tsavo, government offices, and local amenities.');

GO

USE php_project;
GO

SELECT * FROM properties;
SELECT * FROM admin_users;
SELECT * FROM users;
SELECT * FROM notifications;
SELECT * FROM buyers;
SELECT occupation FROM buyers;
SELECT * FROM feedback;








-- If the table is now empty and you want IDs to start from 1
DBCC CHECKIDENT ('buyers', RESEED, 0);

ALTER TABLE buyers
ADD occupation VARCHAR(100) NOT NULL DEFAULT 'Other';

DELETE FROM buyers;
GO

DBCC CHECKIDENT ('buyers', RESEED, 0);
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
VALUES ('test@example.com', 'Test Subject', 'This is a test feedback message');




