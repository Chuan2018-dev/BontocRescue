#include <SPI.h>
#include <LoRa.h>
#include <TinyGPSPlus.h>

// LoRa Pins
#define LORA_SS    5
#define LORA_RST   14
#define LORA_DIO0  4

// GPS Pins
#define GPS_RX 32   // GPS TX -> ESP32 GPIO32
#define GPS_TX 33   // GPS RX -> ESP32 GPIO33 optional
#define GPS_BAUD 9600

// Button
#define BUTTON_PIN 27

const String SENDER_ID = "S01";

TinyGPSPlus gps;
HardwareSerial gpsSerial(2);

bool lastButtonState = HIGH;
bool alertActive = false;
bool waitingForAck = false;

unsigned long lastSendTime = 0;
unsigned long lastGpsPrint = 0;

const unsigned long RESEND_INTERVAL = 3000;

int sequenceNumber = 0;

double lastLat = 0.0;
double lastLng = 0.0;
int lastSat = 0;
bool gpsValid = false;

String createAlertPacket() {
  String packet = "ALERT|";
  packet += SENDER_ID;
  packet += "|";
  packet += String(sequenceNumber);
  packet += "|";

  if (gpsValid) {
    packet += String(lastLat, 6);
    packet += "|";
    packet += String(lastLng, 6);
    packet += "|";
    packet += String(lastSat);
  } else {
    packet += "0.000000|0.000000|0";
  }

  return packet;
}

void sendAlert() {
  String packet = createAlertPacket();

  Serial.println("Sending Alert:");
  Serial.println(packet);

  LoRa.beginPacket();
  LoRa.print(packet);
  LoRa.endPacket();

  waitingForAck = true;
  alertActive = true;
  lastSendTime = millis();
}

void readGPS() {
  while (gpsSerial.available() > 0) {
    gps.encode(gpsSerial.read());
  }

  if (gps.location.isValid()) {
    gpsValid = true;
    lastLat = gps.location.lat();
    lastLng = gps.location.lng();
    lastSat = gps.satellites.value();
  } else {
    gpsValid = false;
    lastSat = gps.satellites.value();
  }

  if (millis() - lastGpsPrint >= 3000) {
    lastGpsPrint = millis();

    Serial.print("GPS Valid: ");
    Serial.print(gpsValid ? "YES" : "NO");
    Serial.print(" | Satellites: ");
    Serial.print(lastSat);

    if (gpsValid) {
      Serial.print(" | Lat: ");
      Serial.print(lastLat, 6);
      Serial.print(" | Lng: ");
      Serial.print(lastLng, 6);
    }

    Serial.println();
  }
}

void receiveLoRaResponse() {
  int packetSize = LoRa.parsePacket();

  if (packetSize) {
    String message = "";

    while (LoRa.available()) {
      message += (char)LoRa.read();
    }

    Serial.println("Received: " + message);

    String networkAck = "ACK|" + SENDER_ID + "|" + String(sequenceNumber);
    String userAck = "ALERT_ACKNOWLEDGED|" + SENDER_ID + "|" + String(sequenceNumber);

    if (message == networkAck) {
      waitingForAck = false;
      Serial.println("Receiver received the alert.");
    }

    if (message == userAck) {
      waitingForAck = false;
      alertActive = false;
      Serial.println("Dashboard acknowledged the alert.");
    }
  }
}

void setup() {
  Serial.begin(115200);

  pinMode(BUTTON_PIN, INPUT_PULLUP);

  gpsSerial.begin(GPS_BAUD, SERIAL_8N1, GPS_RX, GPS_TX);

  LoRa.setPins(LORA_SS, LORA_RST, LORA_DIO0);

  if (!LoRa.begin(433E6)) {
    Serial.println("LoRa Init Failed!");
    while (1);
  }

  LoRa.setSyncWord(0x12);
  LoRa.enableCrc();

  Serial.println("Sender Ready");
  Serial.println("Press button to send emergency alert.");
}

void loop() {
  readGPS();
  receiveLoRaResponse();

  bool currentButtonState = digitalRead(BUTTON_PIN);

  if (!alertActive && lastButtonState == HIGH && currentButtonState == LOW) {
    delay(50);

    if (digitalRead(BUTTON_PIN) == LOW) {
      sequenceNumber++;
      sendAlert();

      while (digitalRead(BUTTON_PIN) == LOW) {
        delay(10);
      }
    }
  }

  lastButtonState = currentButtonState;

  if (alertActive && waitingForAck && millis() - lastSendTime >= RESEND_INTERVAL) {
    Serial.println("No ACK yet. Resending...");
    sendAlert();
  }
}