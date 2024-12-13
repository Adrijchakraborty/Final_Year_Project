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
  display.println("Connected to WiFi");
  display.display();
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(wifiClient, serverUrl);
    int httpResponseCode = http.GET();

    if (httpResponseCode > 0) {
      String response = http.getString();
      
      // Parse JSON response
      StaticJsonDocument<1024> doc;
      DeserializationError error = deserializeJson(doc, response);

      if (!error) {
        String highestPriorityNotice;
        int maxImportance = -1;

        // Find the notice with the highest importance
        for (JsonObject notice : doc.as<JsonArray>()) {
          String content = notice["content"];
          int importance = notice["importance_level"];
          if (importance > maxImportance) {
            maxImportance = importance;
            highestPriorityNotice = content;
          }
        }

        // Display the highest priority notice
        if (maxImportance != -1) {
          display.clearDisplay();
          display.setCursor(0, 0);
          display.println("Notice:");
          display.println(highestPriorityNotice);
          display.display();

          Serial.println("Highest Priority Notice:");
          Serial.println(highestPriorityNotice);
          Serial.print("Importance: ");
          Serial.println(maxImportance);
        } else {
          Serial.println("No notices available.");
        }
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

  delay(2500); // Wait 2.5 seconds before the next request
}