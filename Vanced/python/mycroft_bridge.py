# mycroft_bridge.py
import json
import sys
import uuid
import websocket
import threading

MYCROFT_WS = "ws://localhost:8181/core"  # Dentro del contenedor es localhost

# Enviar mensaje y esperar respuesta
class MycroftBridge:
    def __init__(self):
        self.response = None
        self.event = threading.Event()
        self.msg_id = str(uuid.uuid4())
        self.ws = websocket.WebSocketApp(
            MYCROFT_WS,
            on_message=self.on_message,
            on_open=self.on_open,
            on_error=self.on_error
        )

    def on_open(self, ws):
        utterance = self.text
        msg = {
            "type": "recognizer_loop:utterance",
            "data": {
                "utterances": [utterance],
                "lang": "en-us"
            },
            "context": {
                "client_name": "external_bridge",
                "ident": self.msg_id
            }
        }
        ws.send(json.dumps(msg))

    def on_message(self, ws, message):
        try:
            msg = json.loads(message)
            # Escucha la respuesta de "speak"
            if msg["type"] == "speak":
                self.response = msg["data"]["utterance"]
                self.event.set()
                ws.close()
        except Exception as e:
            print("Error parsing message:", e)

    def on_error(self, ws, error):
        print("WebSocket Error:", error)
        self.event.set()

    def ask(self, text):
        self.text = text
        thread = threading.Thread(target=self.ws.run_forever)
        thread.start()
        self.event.wait(timeout=10)
        return self.response or "No response from Mycroft."


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python mycroft_bridge.py \"Your question here\"")
        sys.exit(1)

    bridge = MycroftBridge()
    response = bridge.ask(sys.argv[1])
    print(f"Respuesta: {response}")
