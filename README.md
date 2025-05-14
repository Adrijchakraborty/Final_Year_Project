# 📌 Smart Notice Board (ESP8266 + PHP + MySQL)

The **Smart Notice Board** is a full-stack IoT project that enables users to publish and manage important notices remotely through a web interface. Notices are stored in a **MySQL** database and displayed on a **1.3" OLED screen (SH1106)** connected to an **ESP8266** microcontroller. Users can scroll through multiple notices using a **potentiometer**.

---

## 🚀 Features

- 📡 **Wi-Fi Enabled ESP8266**
- 📋 **Web-based Notice Submission**
- 🗑️ **Delete functionality from Web UI**
- ⚙️ **JSON API for real-time updates**
- 🧭 **Scrollable notices via potentiometer**
- 🖥️ **OLED Display (1.3" SH1106, I2C)**

---

## 🛠️ Tech Stack

- **Frontend**: HTML, CSS  
- **Backend**: PHP  
- **Database**: MySQL  
- **Microcontroller**: ESP8266 (NodeMCU)  
- **Display**: SH1106 1.3" OLED (I2C)  
- **Data Protocol**: HTTP with JSON response  
- **Libraries**: `ArduinoJson`, `Adafruit_SH110X`, `ESP8266HTTPClient`

---

## 📦 Folder Structure

- /htdocs/SmartNoticeBoard/ → Web app files
- /htdocs/SmartNoticeBoard/server.php → API & form handler
- /htdocs/SmartNoticeBoard/style.css → Styling
- /arduino/SmartNoticeBoard.ino → ESP8266 firmware
- /db/smart_notice_board.sql → Database schema

---

## 📷 Hardware Setup

### OLED (I2C) Wiring

| OLED Pin | ESP8266 Pin |
|----------|-------------|
| VCC      | 3.3V        |
| GND      | GND         |
| SDA      | D2 (GPIO4)  |
| SCL      | D1 (GPIO5)  |

### Potentiometer Wiring

| Potentiometer | ESP8266 Pin |
|---------------|-------------|
| Left Pin      | 3.3V        |
| Right Pin     | GND         |
| Middle Pin    | A0          |

---

## 🖥️ Web Interface Features

- Add new notices with **importance level**
- Display all notices in **descending order of importance**
- **Delete** individual notices via a button
- JSON endpoint at `/server.php?latest` returns latest notices

---

## 🌐 Setup Instructions

### ▶️ Web (XAMPP)

1. Install **XAMPP** and start **Apache & MySQL**.
2. Copy the project folder to `htdocs/`.
3. Import `smart_notice_board.sql` using **phpMyAdmin**.
4. Access the site at:  
   `http://localhost/SmartNoticeBoard/`

### ▶️ ESP8266

1. Install **Arduino IDE** and ESP8266 board package.
2. Install required libraries:
   - `Adafruit_SH110X`
   - `Adafruit_GFX`
   - `ArduinoJson`
   - `ESP8266HTTPClient`
3. Open `SmartNoticeBoard.ino` and update:
   - **Wi-Fi credentials**
   - `serverUrl` with your local IP (e.g., `http://192.168.x.x/SmartNoticeBoard/server.php?latest`)
4. Upload to ESP8266 and connect hardware as shown above.

---

## 🧪 API Usage

### `GET /server.php?latest`

Returns a JSON array of notices:

```json
[
  {
    "content": "Notice 1",
    "importance_level": 8
  },
  ...
]
```
---

## 🗑️ Delete Feature (New)
A "Delete" button is added next to each notice on the web interface.
Clicking it sends a POST request to delete the corresponding notice by ID from the database.

---

## 📈 Future Enhancements
- 🔋 Battery backup support

- 💾 MicroSD logging

- 🌐 Deploy to cloud (e.g., InfinityFree, Vercel)

- 🛠️ Advanced filtering & search

- 📱 Mobile UI improvements

---

## 📎 Notes
- Ensure the ESP8266 and your server (laptop/PC) are on the same Wi-Fi network.

- Use a stable power supply for the OLED to avoid flickering.

- If using a mobile hotspot, update the serverUrl IP address accordingly.

---