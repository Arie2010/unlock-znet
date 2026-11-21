<?php
if (isset($_POST['action']) && $_POST['action'] === 'unlock') {
    header('Content-Type: application/json');
    $host = '192.168.100.1';
    $port = 23;
    $logs = [];

    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$fp) {
        echo json_encode([
            'status' => 'error',
            'message' => "Gagal terhubung ke $host:$port ($errstr). Pastikan Wi-Fi/LAN HP sudah tersambung ke ONT."
        ]);
        exit;
    }

    stream_set_blocking($fp, false);

    function sendCmd($fp, $cmd, $delayMs = 800000) {
        if ($cmd !== null) {
            fwrite($fp, $cmd . "\r\n");
        }
        usleep($delayMs);
        return fread($fp, 8192);
    }

    $logs[] = "Terhubung ke $host...";
    sendCmd($fp, null, 800000);
    sendCmd($fp, "root", 800000);
    $res = sendCmd($fp, "admin", 1000000);

    if (strpos($res, 'Enter the ID of the session') !== false) {
        $logs[] = "Sesi Telnet penuh, memutus sesi lama (ID 1)...";
        sendCmd($fp, "1", 1000000);
    }

    $commands = [
        "su",
        "shell",
        "cfgtool set /var/UnzipedC.xml InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.X_HW_WebUserInfoInstance.2 PassMode 0",
        "cfgtool set /var/UnzipedC.xml InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.X_HW_WebUserInfoInstance.2 Password admintelecom",
        "cfgtool set /var/UnzipedC.xml InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.X_HW_WebUserInfoInstance.1 UserLevel 0",
        "cp /var/UnzipedC.xml /mnt/jffs2/hw_ctree.xml",
        "sync",
        "exit",
        "reset"
    ];

    foreach ($commands as $cmd) {
        sendCmd($fp, $cmd, 900000);
        $logs[] = "OK: " . $cmd;
    }

    fclose($fp);
    $logs[] = "Selesai! ONT sedang reboot. Tunggu ±1 menit lalu login memakai ZnetAdmin / admintelecom.";

    echo json_encode([
        'status' => 'success',
        'logs' => $logs
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unlock ZNET EG8141H5</title>
<style>
  body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; display: flex; justify-content: center; }
  .card { background: #1e293b; border-radius: 14px; padding: 22px; width: 100%; max-width: 440px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
  h2 { margin: 0 0 6px; font-size: 20px; color: #38bdf8; }
  p { margin: 0 0 18px; font-size: 13px; color: #94a3b8; line-height: 1.5; }
  button { width: 100%; padding: 14px; font-size: 16px; font-weight: 700; border: none; border-radius: 10px; background: #0284c7; color: #fff; cursor: pointer; }
  button:disabled { background: #475569; cursor: not-allowed; }
  .log { margin-top: 16px; background: #020617; border: 1px solid #334155; border-radius: 8px; padding: 12px; font-family: monospace; font-size: 12px; color: #22c55e; min-height: 130px; white-space: pre-wrap; word-break: break-word; }
  .err { color: #f87171; }
  .link-btn { display: block; text-align: center; margin-top: 12px; padding: 11px; background: #334155; color: #e2e8f0; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; }
</style>
</head>
<body>
<div class="card">
  <h2>Unlock Superadmin ZNET</h2>
  <p>Target ONT: <b>192.168.100.1</b> (EG8141H5)<br>Login hasil: <b>ZnetAdmin</b> / <b>admintelecom</b></p>
  <button id="btnUnlock" onclick="startUnlock()">UNLOCK & REBOOT ONT</button>
  <div id="logBox" class="log">Siap. Pastikan Wi-Fi HP sudah terhubung ke ONT...</div>
  <a href="http://192.168.100.1" target="_blank" class="link-btn">Buka Halaman Login ONT (192.168.100.1)</a>
</div>

<script>
async function startUnlock() {
  const btn = document.getElementById('btnUnlock');
  const logBox = document.getElementById('logBox');
  btn.disabled = true;
  btn.textContent = 'Sedang Memproses (±10 detik)...';
  logBox.className = 'log';
  logBox.textContent = 'Menghubungi 192.168.100.1 via Telnet...\n';

  try {
    const formData = new FormData();
    formData.append('action', 'unlock');
    const res = await fetch('', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.status === 'success') {
      logBox.textContent = data.logs.join('\n');
    } else {
      logBox.className = 'log err';
      logBox.textContent = data.message;
    }
  } catch (e) {
    logBox.className = 'log err';
    logBox.textContent = 'Error koneksi ke server lokal: ' + e.message;
  } finally {
    btn.disabled = false;
    btn.textContent = 'UNLOCK & REBOOT ONT';
  }
}
</script>
</body>
</html>
