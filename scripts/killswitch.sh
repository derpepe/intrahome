#!/usr/bin/env bash
#
# killswitch.sh — Internet-Killswitch für Draytek Vigor 130 (VDSL)
#
# Verwendung:
#   ./killswitch.sh status    — "active" oder "blocked" (schnell)
#   ./killswitch.sh details   — VDSL-Verbindungsdetails vom Router
#   ./killswitch.sh on        — Internet deaktivieren  (DSL → Idle)
#   ./killswitch.sh off       — Internet wiederherstellen (DSL-Neustart)
#
# Es werden keinerlei Einstellungen am Router geändert.

ROUTER_IP="192.168.2.2"
ROUTER_USER="admin"
ROUTER_PASS="admin"

# --- Hilfsfunktionen ---

die() { echo "FEHLER: $*" >&2; exit 1; }

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

# =====================================================================
# --- status — ein einzelner Ping, so schnell wie möglich ---
# =====================================================================

do_status() {
    if ping -c 1 -W 1 8.8.8.8 &>/dev/null; then
        echo "active"
    else
        echo "blocked"
    fi
}

# =====================================================================
# --- details — VDSL-Status vom Router ---
# =====================================================================

do_details() {
    send_cmd "vdsl status more" 5
}

# =====================================================================
# --- on (Internet deaktivieren) ---
# =====================================================================

do_on() {
    send_cmd "vdsl idle on" 3 >/dev/null
    sleep 2
    do_status
}

# =====================================================================
# --- off (Internet wiederherstellen) ---
# =====================================================================

do_off() {
    send_cmd "vdsl reboot" 3 >/dev/null
    echo -n "syncing"
    for _ in $(seq 1 18); do
        sleep 5
        if ping -c 1 -W 1 8.8.8.8 &>/dev/null; then
            echo ""
            echo "active"
            return 0
        fi
        echo -n "."
    done
    echo ""
    echo "blocked"
}

# =====================================================================
# --- Main ---
# =====================================================================

case "${1,,}" in
    status)   do_status ;;
    details)  do_details ;;
    on)       do_on ;;
    off)      do_off ;;
    *)
        echo "Verwendung: $0 {status|details|on|off}"
        echo ""
        echo "  status   — \"active\" oder \"blocked\""
        echo "  details  — VDSL-Verbindungsdetails anzeigen"
        echo "  on       — Internet deaktivieren  (Killswitch EIN)"
        echo "  off      — Internet wiederherstellen (Killswitch AUS)"
        exit 1
        ;;
esac
