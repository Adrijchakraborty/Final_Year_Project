#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SH110X.h>
#include <qrcode.h>

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

// Display timing
const unsigned long DISPLAY_INTERVAL = 5000; // 5 seconds per mode
unsigned long lastModeSwitch = 0;
bool showQR = false;
int currentNoticeIndex = -1;
String currentUrl = "";

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

void displayQRCode() {
  // Generate QR code (version 3 = 29x29 modules)
  QRCode qrcode;
  uint8_t qrcodeData[qrcode_getBufferSize(3)];
  qrcode_initText(&qrcode, qrcodeData, 3, 0, currentUrl.c_str());

  // Calculate positioning
  const int scale = 2;
  const int qrSize = qrcode.size * scale;
  const int xPos = (SCREEN_WIDTH - qrSize) / 2;
  const int yPos = (SCREEN_HEIGHT - qrSize) / 2;
  
  // Draw QR code
  display.clearDisplay();
  
  for (uint8_t y = 0; y < qrcode.size; y++) {
    for (uint8_t x = 0; x < qrcode.size; x++) {
      if (qrcode_getModule(&qrcode, x, y)) {
        display.fillRect(
          xPos + x * scale, 
          yPos + y * scale,
          scale, 
          scale, 
          SH110X_WHITE
        );
      }
    }
  }
}

void displayNoticeContent(String content, int noticeIndex, int maxNotices) {
  display.clearDisplay();
  int yPosition = 0;
  display.setCursor(0, yPosition);
  display.print("Notice ");
  display.print(noticeIndex + 1);
  display.print("/");
  display.println(maxNotices);
  yPosition += 10;
  
  printWrappedText(content, 0, yPosition, SCREEN_WIDTH);
}

void setup() {
  Serial.begin(115200);

  // Initialize OLED display
  if (!display.begin(0x3C, true)) {
    Serial.println("Display allocation failed");
    while (true);
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
    while (true);
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
      DynamicJsonDocument doc(2048);
      DeserializationError error = deserializeJson(doc, response);

      if (!error) {
        int maxNotices = doc.size();
        int potValue = analogRead(potPin);
        int newNoticeIndex = map(potValue, 0, 1023, 0, max(0, maxNotices - 1));
        newNoticeIndex = constrain(newNoticeIndex, 0, max(0, maxNotices - 1));

        if (maxNotices > 0) {
          JsonObject notice = doc[newNoticeIndex];
          String content = notice["content"].as<String>();
          bool hasUrl = notice.containsKey("url") && !notice["url"].isNull() && notice["url"].as<String>().length() > 0;
          String newUrl = hasUrl ? notice["url"].as<String>() : "";

          // Check if notice changed
          if (newNoticeIndex != currentNoticeIndex || newUrl != currentUrl) {
            currentNoticeIndex = newNoticeIndex;
            currentUrl = newUrl;
            showQR = false; // Reset to content view when notice changes
            lastModeSwitch = millis();
          }

          // Toggle between content and QR every 5s (only if URL exists)
          if (hasUrl && millis() - lastModeSwitch > DISPLAY_INTERVAL) {
            showQR = !showQR;
            lastModeSwitch = millis();
          }

          // Display appropriate screen
          if (showQR && hasUrl && currentUrl.length() > 0) {
            displayQRCode();
          } else {
            displayNoticeContent(content, currentNoticeIndex, maxNotices);
          }
        } else {
          display.clearDisplay();
          display.setCursor(0, 0);
          display.println("No notices found");
        }
        display.display();
      }
    }
    http.end();
  } else {
    Serial.println("WiFi Disconnected");
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Disconnected");
    display.display();
  }
  delay(100);
}