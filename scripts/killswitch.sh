#!/usr/bin/env bash
#
# killswitch.sh — Internet-Killswitch für Draytek Vigor 130 (VDSL)
#
# Verwendung:
#   ./killswitch.sh status    — "active", "blocked" oder "syncing"
#   ./killswitch.sh details   — VDSL-Verbindungsdetails anzeigen
#   ./killswitch.sh on        — Internet deaktivieren  (DSL → Idle)
#   ./killswitch.sh off       — Internet wiederherstellen (DSL-Neustart)
#
# Es werden keinerlei Einstellungen am Router geändert.

ROUTER_IP="192.168.2.2"
ROUTER_USER="admin"
ROUTER_PASS="admin"

# --- Hilfsfunktionen ---

die() { echo "FEHLER: $*" >&2; exit 1; }

# Telnet-Befehl senden und Ausgabe zurückgeben.
telnet_cmd() {
    local cmd="$1"
    local timeout="${2:-5}"
    {
        sleep 1
        echo "$ROUTER_USER"
        sleep 1
        echo "$ROUTER_PASS"
        sleep 1
        echo "$cmd"
        sleep "$timeout"
        echo "quit"
    } | telnet "$ROUTER_IP" 2>/dev/null
}

# SSH-Variante (Fallback). Vigor 130 braucht ältere Algorithmen.
ssh_cmd() {
    local cmd="$1"
    sshpass -p "$ROUTER_PASS" \
        ssh -o StrictHostKeyChecking=no \
            -o UserKnownHostsFile=/dev/null \
            -o KexAlgorithms=+diffie-hellman-group14-sha1,diffie-hellman-group1-sha1 \
            -o HostKeyAlgorithms=+ssh-dss,ssh-rsa \
            -o PubkeyAcceptedAlgorithms=+ssh-rsa \
            -c aes128-cbc,3des-cbc,aes256-cbc \
            "$ROUTER_USER@$ROUTER_IP" "$cmd" 2>/dev/null
}

# Befehl senden — versucht zuerst SSH, dann Telnet.
send_cmd() {
    local cmd="$1"
    local timeout="${2:-5}"
    if command -v sshpass &>/dev/null; then
        ssh_cmd "$cmd"
    elif command -v telnet &>/dev/null; then
        telnet_cmd "$cmd" "$timeout"
    else
        die "Weder 'sshpass' noch 'telnet' gefunden.\n  sudo apt install sshpass   # oder\n  sudo apt install telnet"
    fi
}

# DSL-State vom Router abfragen (SHOWTIME, IDLE, HANDSHAKE, TRAINING, ...)
get_dsl_state() {
    send_cmd "vdsl status" 3 | grep -ioE '(SHOWTIME|IDLE|HANDSHAKE|TRAINING|INITIALIZING|EXCEPTION)' | head -1
}

# --- status ---
#   active  — DSL synchronisiert, Internet erreichbar
#   syncing — DSL synchronisiert gerade (Handshake/Training) oder
#             Router erreichbar aber Internet noch nicht da
#   blocked — DSL im Idle-Modus oder Router nicht erreichbar

do_status() {
    local dsl_state
    dsl_state=$(get_dsl_state)

    if ping -c 1 -W 2 8.8.8.8 &>/dev/null; then
        echo "active"
        return
    fi

    # Kein Internet — liegt es an Idle oder an laufendem Resync?
    case "${dsl_state^^}" in
        SHOWTIME)
            # DSL steht, aber Internet geht (noch) nicht → Übergang
            echo "syncing"
            ;;
        IDLE)
            echo "blocked"
            ;;
        HANDSHAKE|TRAINING|INITIALIZING)
            echo "syncing"
            ;;
        "")
            # Keine Antwort vom Router → vermutlich rebootet gerade
            if ping -c 1 -W 2 "$ROUTER_IP" &>/dev/null; then
                echo "syncing"
            else
                echo "syncing"  # Router startet noch neu
            fi
            ;;
        *)
            echo "syncing"
            ;;
    esac
}

# --- details ---

do_details() {
    echo "=== VDSL-Details (Vigor 130 @ $ROUTER_IP) ==="
    echo ""
    send_cmd "vdsl status more" 5
}

# --- on (Internet deaktivieren) ---

do_on() {
    echo "Internet wird deaktiviert (DSL → Idle) ..."
    send_cmd "vdsl idle on" 3
    sleep 3
    do_status
}

# --- off (Internet wiederherstellen) ---

do_off() {
    echo "Internet wird wiederhergestellt (DSL-Neustart) ..."
    send_cmd "vdsl reboot" 3
    echo "DSL synchronisiert neu — das dauert 30–90 Sekunden."
    echo ""
    echo -n "Warte "
    for i in $(seq 1 12); do
        sleep 10
        if ping -c 1 -W 2 8.8.8.8 &>/dev/null; then
            echo ""
            echo "active (nach ~$((i * 10))s)"
            return 0
        fi
        echo -n "."
    done
    echo ""
    echo "blocked (nach 120s noch kein Internet — DSL-LED prüfen)"
}

# --- Main ---

case "${1,,}" in
    status)   do_status ;;
    details)  do_details ;;
    on)       do_on ;;
    off)      do_off ;;
    *)
        echo "Verwendung: $0 {status|details|on|off}"
        echo ""
        echo "  status   — \"active\", \"blocked\" oder \"syncing\""
        echo "  details  — VDSL-Verbindungsdetails anzeigen"
        echo "  on       — Internet deaktivieren  (Killswitch EIN)"
        echo "  off      — Internet wiederherstellen (Killswitch AUS)"
        exit 1
        ;;
esac
