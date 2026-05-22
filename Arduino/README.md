# Arduino LoRa Hardware Integration

This folder contains the ESP32 LoRa sender, gateway, and receiver sketches used by the Stitch Rescue demo.

## Functional Flow

1. `Sender_GPS` sends a compact LoRa packet when the emergency button is pressed.
2. `Gateway1` or `Gateway2` forwards the packet to the receiver.
3. `Reciever` keeps the local ESP32 dashboard active, sends LoRa ACKs, and posts the alert to Laravel.
4. Laravel saves the alert as an `IncidentReport` with `transmission_type = lora`.
5. The responder dashboard receives the new incident through the existing incident feed broadcast path.

`sketch_may21a` is a small GPS-only test sketch you can use before uploading the full sender sketch.

## Receiver API Setup

Copy this file:

```txt
Arduino/Arduino/Reciever/arduino_secrets.example.h
```

To this local-only file:

```txt
Arduino/Arduino/Reciever/arduino_secrets.h
```

Then set:

```cpp
const char* WIFI_SSID = "YOUR_WIFI_NAME";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char* STITCH_LORA_API_URL = "https://stitch-web-demo.onrender.com/api/v1/lora/alerts";
const char* STITCH_LORA_TOKEN = "same-token-as-LORA_INGEST_TOKEN";
```

`arduino_secrets.h` is ignored by Git so real WiFi and API credentials stay private.

## Laravel Environment

Set the same shared token in Laravel:

```env
LORA_INGEST_TOKEN=replace-with-a-long-random-token
```

For Render, add the same value to the `stitch-web-demo` environment variables.

## Packet Format

The receiver expects forwarded gateway packets in this format:

```txt
GW|G1|-70|9.5|ALERT|S01|1|10.123456|123.123456|7
```

Fields:

```txt
GW | gateway id | gateway RSSI | gateway SNR | ALERT | sender id | sequence | latitude | longitude | satellites
```

Laravel deduplicates LoRa alerts by `sender_id + sequence`, so a repeated LoRa retry will not create duplicate incidents.
