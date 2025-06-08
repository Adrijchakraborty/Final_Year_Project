#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>

// WiFi credentials
const char* ssid = "CallMeDean🔥";
const char* password = "123456789";
const char* serverUrl = "http://192.168.0.105/Smart%20Notice%20Board(FYP)/server.php?latest";

// OLED display settings
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SH1106G display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

const int potPin = A0;

WiFiClient wifiClient;

// Word-wrap function
void printWrappedText(String text, int x, int& y, int maxWidth) {
  int len = text.length();
  String line = "";

  for (int i = 0; i < len; i++) {
    line += text[i];

    // Check if line exceeds max width in pixels
    int16_t x1, y1;
    uint16_t w, h;
    display.getTextBounds(line, x, y, &x1, &y1, &w, &h);

    if (w > maxWidth || text[i] == '\n') {
      display.setCursor(x, y);
      display.println(line.substring(0, line.length() - 1));
      y += 10;
      line = text[i];
    }
  }

  if (line.length() > 0) {
    display.setCursor(x, y);
    display.println(line);
    y += 10;
  }
}

int getWrappedHeight(String text, int maxWidth) {
  int lineCount = 1;
  String line = "";

  for (int i = 0; i < text.length(); i++) {
    line += text[i];

    int16_t x1, y1;
    uint16_t w, h;
    display.getTextBounds(line, 0, 0, &x1, &y1, &w, &h);

    if (w > maxWidth || text[i] == '\n') {
      line = text[i];  // start new line
      lineCount++;
    }
  }
  return lineCount * 10;  // each line ~10px height
}



void setup() {
  Serial.begin(115200);

  // Initialize OLED display
  if (!display.begin(0x3C, true)) {
    Serial.println("Display allocation failed");
    while (true)
      ;  // halt
  }

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SH110X_WHITE);
  display.setCursor(0, 0);
  display.println("Connecting to WiFi...");
  display.display();

  // Connect to WiFi with timeout
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 20) {
    delay(500);
    Serial.print(".");
    retries++;
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("\nFailed to connect to WiFi");
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Failed!");
    display.display();
    while (true)
      ;  // halt
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

      // Debug raw response
      Serial.println("Raw response:");
      Serial.println(response);

      // Parse JSON response
      DynamicJsonDocument doc(2048);
      DeserializationError error = deserializeJson(doc, response);

      if (!error) {
        int maxNotices = doc.size();
        int potValue = analogRead(potPin);
        int startNoticeIndex = map(potValue, 0, 1023, 0, max(1, maxNotices - 1));
        startNoticeIndex = constrain(startNoticeIndex, 0, max(0, maxNotices - 1));

        // Display notices
        display.clearDisplay();
        display.setCursor(0, 0);
        display.println("Notices:");
        int yPosition = 10;

        // Dynamically fit notices based on screen height
        for (int i = startNoticeIndex; i < maxNotices; i++) {
          JsonObject notice = doc[i];
          String content = notice["content"].as<String>();

          int noticeHeight = getWrappedHeight(content, 118) + 10;  // '>' line + wrapped text

          if (yPosition + noticeHeight > SCREEN_HEIGHT) {
            break;  // Stop if it won't fit
          }

          display.setCursor(0, yPosition);
          display.print("> ");
          yPosition += 10;

          printWrappedText(content, 10, yPosition, 118);  // 118 leaves margin
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

  delay(250);  // Smooth refresh
}
