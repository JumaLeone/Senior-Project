CREATE DATABASE IF NOT EXISTS php_project;
USE php_project;

CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date_created DATETIME NOT NULL,
    is_read BOOLEAN DEFAULT FALSE
);

CREATE TABLE properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_type VARCHAR(100) NOT NULL,
    price_range VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    area INT NOT NULL,
    capacity VARCHAR(50) NOT NULL,
    description TEXT NOT NULL
);

CREATE TABLE buyers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT,
    user_id INT NOT NULL
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO properties (property_type, price_range, location, area, capacity, description) VALUES
('Apartment', '200,000 - 400,000', 'Metro Manila, Quezon City', 32, '1-2 persons', 'Located near major universities, shopping malls, and MRT stations for easy commuting.'),
('Residential Lot', '300,000 - 600,000', 'Cavite, Dasmariñas City', 50, 'N/A (Lot only)', 'Situated in a gated community, close to schools, hospitals, and public markets.'),
('Condo', '600,000 - 1,000,000', 'Metro Manila, Makati City', 35, '1-2 persons', 'Walking distance to business districts, parks, and upscale restaurants.'),
('House and Lot', '800,000 - 1,000,000', 'Laguna, Sta. Rosa City', 60, '3-4 persons', 'Located in a suburban area near industrial zones, schools, and commercial establishments.'),
('Apartment', '250,000 - 500,000', 'Pampanga, Angeles City', 40, '2-3 persons', 'Close to Clark Freeport Zone, SM City Clark, and Angeles University Foundation.'),
('Commercial', '500,000 - 1,000,000', 'Bulacan, Malolos City', 70, 'N/A (Commercial)', 'Strategically located near public markets, transportation hubs, and schools.'),
('Residential Lot', '400,000 - 700,000', 'Rizal, Antipolo City', 80, 'N/A (Lot only)', 'Overlooks the Metro Manila skyline; near churches, resorts, and eco-tourism sites.'),
('House and Lot', '600,000 - 900,000', 'Batangas, Lipa City', 65, '3-4 persons', 'Located in a peaceful community near Lipa Cathedral and SM City Lipa.'),
('Condo', '700,000 - 1,000,000', 'Metro Manila, Taguig City', 30, '1 person', 'Situated in Bonifacio Global City, near high-end malls and multinational offices.'),
('Residential Lot', '200,000 - 300,000', 'Ilocos Norte, Laoag City', 100, 'N/A (Lot only)', 'Located in a quiet area near government offices and cultural landmarks.');
