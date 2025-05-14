#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>

// WiFi credentials
const char* ssid = "Ap";
const char* password = "anishpatel";
const char* serverUrl = "http://192.168.234.153/Final_Year_Project_Demo-main/server.php?latest";

// OLED display settings
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SH1106G display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);  // SH1106G constructor

const int potPin = A0;

// WiFi client
WiFiClient wifiClient;

void setup() {
  Serial.begin(115200);

  // Initialize OLED display
  if (!display.begin(0x3C, true)) {
    Serial.println("Display allocation failed");
    while (true);  // halt
  }

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
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
  display.println(WiFi.SSID());
  display.display();
  delay(1500);
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(wifiClient, serverUrl);
    int httpResponseCode = http.GET();

    if (httpResponseCode > 0) {
      String response = http.getString();

      // Parse JSON response
      DynamicJsonDocument doc(2048);  // Increased buffer
      DeserializationError error = deserializeJson(doc, response);

      if (!error) {
        // Read potentiometer value
        int potValue = analogRead(potPin);
        int maxNotices = doc.size();
        int startNoticeIndex = map(potValue, 0, 1023, 0, maxNotices - 3);
        startNoticeIndex = constrain(startNoticeIndex, 0, maxNotices - 3);

        // Display notices
        display.clearDisplay();
        display.setCursor(0, 0);
        display.println("Notices:");

        int yPosition = 10;
        for (int i = startNoticeIndex; i < startNoticeIndex + 5 && i < maxNotices; i++) {
          JsonObject notice = doc[i];
          String content = notice["content"].as<String>();

          display.setCursor(0, yPosition);
          display.print("> ");
          display.print(content);
          yPosition += 10;
        }

        display.display();
      } else {
        Serial.print("JSON Parse Error: ");
        Serial.println(error.c_str());
      }
    } else {
      Serial.print("HTTP Request Failed: ");
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

  delay(250);  // Smooth scrolling
}