##  Setup Database & Installation XAMPP

### 1. Persiapan
- Pastikan **XAMPP** terinstal
  - Apache (Port: 8081)
  - MySQL (Port: 3306)
    <img width="1001" height="651" alt="image" src="https://github.com/user-attachments/assets/3d1eb05e-f4bc-48de-bd26-73ad509abeab" />

- Letakkan project di folder: C:\xampp\htdocs\appointment
  <img width="1057" height="353" alt="image" src="https://github.com/user-attachments/assets/236f7a4c-0197-4461-90f6-ffc499fbec07" />

## API
- Letakan Project di folder : C:\xampp\htdocs\appointment\api
  <img width="1052" height="94" alt="image" src="https://github.com/user-attachments/assets/500a722c-9324-44ab-9ca9-3bb229d73f41" />

  
### 2. Buat Database
1. Akses **phpMyAdmin**:  
 [http://localhost:8081/phpmyadmin/](http://localhost:8081/phpmyadmin/)
<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/f8981d09-230a-47cd-859b-faaefee19794" />

2. Jalankan query berikut:
CREATE DATABASE appointment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/e14374a9-e8ab-4da2-870c-8009603b3c35" />

USE appointment_db;

<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/112d1b6f-ee04-4659-b4b3-71611e0cb955" />

-- Membuat Table users
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255),
username VARCHAR(100) NOT NULL UNIQUE,
preferred_timezone VARCHAR(100) DEFAULT 'Asia/Jakarta'
);
<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/4a293c1e-2d60-491a-a92c-b5dd802d982c" />


-- MEmbuat Table appointments
CREATE TABLE appointments (
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
creator_id INT NOT NULL,
start_utc DATETIME NOT NULL,
end_utc DATETIME NOT NULL,
FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);
<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/ed248bac-3254-4cd8-a544-ace78a424163" />

-- Membuat Table participants
CREATE TABLE appointment_participants (
appointment_id INT NOT NULL,
user_id INT NOT NULL,
PRIMARY KEY (appointment_id, user_id),
FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/8a7fa5cd-aae0-4ce4-9ce1-298c4a12a25a" />

-- Hasil Setelah Setting Database
<img width="940" height="502" alt="image" src="https://github.com/user-attachments/assets/68c2170d-0e6f-43ce-9c8a-bde6811e566e" />



