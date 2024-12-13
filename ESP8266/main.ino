#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// WiFi credentials
const char* ssid = "Adi(2.4GHz)";
const char* password = "nonetwork@129";
const char* serverUrl = "http://192.168.0.120/Smart%20Notice%20Board(FYP)/server.php?latest";

// OLED display settings
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

const int potPin = A0;

// WiFi client
WiFiClient wifiClient;

void setup() {
  Serial.begin(115200);
  
  // Initialize OLED display
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("SSD1306 allocation failed");
    for (;;);
  }
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println("Connecting to WiFi...");
  display.display();

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected to WiFi");

  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("Connected to:");
  display.setCursor(0, 10);
  display.println(WiFi.SSID()); // Display the connected WiFi SSID
  display.display();
  delay(1000);
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(wifiClient, serverUrl);
    int httpResponseCode = http.GET();

    if (httpResponseCode > 0) {
      String response = http.getString();
      
      // Parse JSON response
      DynamicJsonDocument doc(1024);
      DeserializationError error = deserializeJson(doc, response);

      if (!error) {
        // Read the potentiometer value
        int potValue = analogRead(potPin);
        int maxNotices = doc.size(); // Total number of notices
        int startNoticeIndex = map(potValue, 0, 1023, 0, maxNotices - 3); // Adjust range
        startNoticeIndex = constrain(startNoticeIndex, 0, maxNotices - 3); // Ensure valid range

        // Display notices based on the potentiometer value
        display.clearDisplay();
        display.setCursor(0, 0);
        display.println("Notices:");

        int yPosition = 10; // Start below the header (y=10)
        for (int i = startNoticeIndex; i < startNoticeIndex + 5 && i < maxNotices; i++) {
          JsonObject notice = doc[i];
          String content = notice["content"];

          display.setCursor(0, yPosition); // Set cursor for each notice
          display.print("> ");         // Display a bullet point
          display.print(content);
          yPosition += 10;                // Move down for the next line
        }

        display.display();
      } else {
        Serial.print("JSON Parse Error: ");
        Serial.println(error.c_str());
      }
    } else {
      Serial.print("Error on HTTP request: ");
      Serial.println(httpResponseCode);
    }

    http.end();
  } else {
    Serial.println("WiFi Disconnected");
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Disconnected");
    display.display();
  }

  delay(250); // Reduce delay for smoother scrolling
}