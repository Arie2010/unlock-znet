import socket
import time

host = "192.168.100.1"
port = 23

def send_cmd(sock, cmd, delay=0.9):
    if cmd is not None:
        sock.sendall((cmd + "\r\n").encode())
        print("Kirim:", cmd)
    time.sleep(delay)
    try:
        return sock.recv(8192).decode(errors="ignore")
    except Exception:
        return ""

print(f"Menghubungkan ke {host}...")
s = socket.create_connection((host, port), timeout=5)
s.settimeout(0.5)

send_cmd(s, None, 0.8)
send_cmd(s, "root", 0.8)
res = send_cmd(s, "admin", 1.0)

if "Enter the ID of the session" in res:
    print("Sesi Telnet penuh, memutus sesi lama...")
    send_cmd(s, "1", 1.0)

commands = [
    "su",
    "shell",
    "cfgtool set /var/UnzipedC.xml InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.X_HW_WebUserInfoInstance.2 PassMode 0",
    "cfgtool set /var/UnzipedC.xml InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.X_HW_WebUserInfoInstance.2 Password admintelecom",
    "cfgtool set /var/UnzipedC.xml InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.X_HW_WebUserInfoInstance.1 UserLevel 0",
    "cp /var/UnzipedC.xml /mnt/jffs2/hw_ctree.xml",
    "sync",
    "exit",
    "reset"
]

for cmd in commands:
    send_cmd(s, cmd, 0.9)

s.close()
print("Selesai! ONT sedang reboot. Tunggu ±1 menit lalu login memakai ZnetAdmin / admintelecom.")
