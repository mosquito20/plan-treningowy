CREATE DATABASE IF NOT EXISTS baza
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_polish_ci;

USE baza;

CREATE TABLE IF NOT EXISTS klienci (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    bmi DECIMAL(4,2),
    rodzaj_planu TEXT
);
