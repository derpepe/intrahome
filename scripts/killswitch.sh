#!/bin/bash

# Killswitch Skript für DNS-Blocking (macOS / Linux)
# Achtung: Benötigt ggf. sudo-Rechte je nach System. 
# Für macOS blockiert es Wi-Fi DNS-Server, für Linux /etc/resolv.conf anpassen.

ACTION=$1
SCRIPT_DIR=$(dirname "$0")
STATUS_FILE="$SCRIPT_DIR/.killswitch_status"

if [[ "$ACTION" == "on" ]]; then
    # Blockiere Internet durch ungültigen DNS-Server
    if [[ "$OSTYPE" == "darwin"* ]]; then
        networksetup -setdnsservers Wi-Fi 127.0.0.1 > /dev/null 2>&1
    else
        # Fallback für Linux (simuliert für jetzt)
        echo "nameserver 127.0.0.1" > /tmp/resolv.conf.blocked
    fi
    echo "blocked" > "$STATUS_FILE"
    echo "INTERNET BLOCKED"
elif [[ "$ACTION" == "off" ]]; then
    # Stelle Internet wieder her
    if [[ "$OSTYPE" == "darwin"* ]]; then
        networksetup -setdnsservers Wi-Fi empty > /dev/null 2>&1
    else
        # Fallback
        echo "nameserver 8.8.8.8" > /tmp/resolv.conf.blocked
    fi
    echo "active" > "$STATUS_FILE"
    echo "INTERNET ACTIVE"
elif [[ "$ACTION" == "status" ]]; then
    if [[ -f "$STATUS_FILE" ]]; then
        cat "$STATUS_FILE"
    else
        echo "active"
    fi
else
    echo "Usage: $0 {on|off|status}"
    exit 1
fi
