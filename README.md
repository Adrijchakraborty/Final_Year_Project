# Smart Notice Board Project

This project is a **Smart Notice Board** system developed using PHP, MySQL, and Arduino (ESP8266). The system allows users to manage and display notices remotely. Data is stored in a MySQL database and can be updated via a website. The ESP8266 microcontroller fetches and displays the notices.

## Prerequisites

### For Web Server
1. **XAMPP**: Install and configure XAMPP to create a local server.
2. **Folder Setup**: Place the project folder inside the `htdocs` directory of XAMPP.
3. **Browser Access**: Open `localhost` in your browser to access the application.

### For Arduino (ESP8266)
1. **Arduino IDE**: Install the Arduino IDE.
2. **Libraries**: Install necessary libraries in the Arduino IDE for ESP8266.
3. **Connections**: Set up the ESP8266 connections as per the provided diagrams.

## Features

1. **Display Notices**: Fetch and display notices from a MySQL database.
2. **Update Notices**: Update notices through a web interface.
3. **JSON API**: The `server.php` file includes functionality to send JSON responses to the URL `/server.php?latest`.
4. **Arduino Integration**: Compiled and uploaded code on the ESP8266 to display notices on an electronic screen.

## Setup Instructions

### Web Application
1. Install XAMPP and start the Apache and MySQL servers.
2. Place the project folder inside the `htdocs` directory of XAMPP.
3. Import the provided SQL file into the MySQL database using phpMyAdmin.
4. Access the project by navigating to `localhost/<project_folder>` in your browser.

### Arduino ESP8266
1. Install the Arduino IDE.
2. Install the necessary ESP8266 libraries.
3. Open the provided Arduino sketch and configure it with your Wi-Fi credentials.
4. Compile the code and upload it to the ESP8266.
5. Set up hardware connections as per the provided diagrams.

## Future Updates

1. **Bigger Display**: Integrate a larger display for better visibility (pending confirmation from the supervisor).
2. **UI Improvements**: 
   - Implement a delete function for notices.
   - Add sorting functionality based on user preference.
3. **Battery Integration**: Implement battery support for portability.
4. **MicroSD Card**: Add support for data storage using a MicroSD card.
5. **Hosting**: Deploy the application on a web hosting platform for remote access.

## Usage

1. Open the web interface to add, update, or delete notices.
2. Access the `/server.php?latest` endpoint for JSON responses.
3. View updated notices on the connected electronic display via ESP8266.

## Notes

- Ensure that the ESP8266 is connected to the same network as the server.
- Double-check connections before powering up the ESP8266.
- Follow the diagrams and instructions for hardware connections.

---
For further details, consult the code comments and hardware setup diagrams provided in the repository.

