<?php
// Database configuration
define("_WEBROOT_PATH_", "./");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {

  // Generate or get raffle key
  if (!isset($_GET['share'])) {
    $raffle_key = bin2hex(random_bytes(16)); // Generate a 32-character key
    $stmt = $pdo->prepare("INSERT INTO raffle_keys (raffle_key) VALUES (:raffle_key)");
    $stmt->execute(['raffle_key' => $raffle_key]);
    header("Location: ./?share=$raffle_key");
    exit;
  } else {
    $raffle_key = $_GET['share'];
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
      die("Invalid raffle key.");
    }
  }

  // Fetch entries
  $stmt = $pdo->prepare("SELECT `name` FROM raffle_entries WHERE raffle_key = :raffle_key");
  $stmt->execute(['raffle_key' => $raffle_key]);
  if ($stmt->rowCount() > 0) {
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $entries = [];
  }

  // Fetch logs
  $stmt = $pdo->prepare("SELECT log_message, created_at FROM raffle_logs WHERE raffle_key = :raffle_key ORDER BY created_at ASC");
  $stmt->execute(['raffle_key' => $raffle_key]);
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }

    #nameList {
      border: 1px solid #ddd;
      padding: 5px;
      background: #f9f9f9;
      overflow-y: auto;
      max-height: 379px;
    }

    #nameList .duplicate {
      background-color: red;
      color: white;
    }

    .winner {
      font-weight: bold;
      text-align: center;
    }

    .hide {
      display: none;
    }

    .names {
      font-size: 24px;
      text-align: center;
    }

    .btn-3d {
      font-size: 30px;
      font-weight: bold;
      color: white;
      background: linear-gradient(145deg, #ff8800, #ed1309);
      border: none;
      border-radius: 20px;
      padding: 15px 30px;
      box-shadow: 0 5px #a70e07;
      transition: transform 0.1s, box-shadow 0.1s;
    }

    .btn-3d:active {
      transform: translateY(5px);
      box-shadow: 0 1px #a70e07;
      background: linear-gradient(145deg, #e17800, #c70e05);
    }

    #log {
      border: 1px solid #ddd;
      padding: 5px;
      background: #eef7eb;
      overflow-y: auto;
      max-height: 450px;
    }
  </style>
</head>

<body class="p-0 m-0 bg-secondary">
  <script>
    document.documentElement.setAttribute("data-bs-theme", "light");
  </script>
  <div class="flex-column-fluid p-2">
    <div class="row g-2">
      <div class="col-lg-3 col-md-5 col-12">
        <div class="card rounded-4 shadow-sm h-600px">
          <div class="card-header">
            <div class="card-title">
              <h3>รายชื่อผู้เข้าร่วม</h3>
            </div>
          </div>
          <div class="card-body">
            <div id="nameList" contenteditable="true" class="h-100 rounded-3 fs-4 text-nowrap">
              <?php foreach ($entries as $ent): ?>
                <div class="text-nowrap"><?= $ent['name'] ?></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card-footer text-center">
            <button class="btn btn-primary" onclick="checkDuplicates()">ตรวจสอบรายชื่อซ้ำ</button>
          </div>
        </div>
      </div>

      <div class="col-lg-6 col-md-7 col-12">
        <div class="card h-100">
          <div class="card rounded-4 shadow-sm h-600px">
            <div class="card-header">
              <div class="card-title">
                <h3>รายการเข้าร่วม</h3>
              </div>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              <div class="d-flex flex-column flex-center gap-3 h-100">
                <div class="d-flex flex-column flex-center gap-3">
                  <label for="speed" class="form-label">ตั้งค่าความเร็วการสุ่ม (มิลลิวินาที):</label>
                  <input id="speed" type="number" class="form-control mb-3" placeholder="ใส่ค่าความเร็ว" min="1" value="10">
                </div>
                <div class="d-flex flex-column flex-column-fluid flex-center gap-3">
                  <div class="names hide"></div>
                  <div class="winner hide"></div>
                </div>
                <div>
                  <button class="btn-3d" onclick="rollClick()">สุ่ม</button>
                  <button class="btn-3d hide mt-2" onclick="rollClick()">สุ่มอีกครั้ง</button>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>

      <div class="col-lg-3 col-12">
        <div class="card rounded-4 shadow-sm h-600px">
          <div class="card-header">
            <div class="card-title">
              <h3>บันทึกผู้รับรางวัล</h3>
            </div>
          </div>
          <div class="card-body">
            <div id="log" class="h-100 rounded-3">
              <?php foreach ($logs as $log): ?>
                <div class="fs-4 text-nowrap"><?= $log['created_at'] ?> - <?= htmlspecialchars($log['log_message'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
  <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>

  <script>
    const rollEl = document.querySelector(".btn-3d:not(.hide)");
    const rollAgainEl = document.querySelector(".btn-3d.hide");
    const namesEl = document.querySelector(".names");
    const winnerEl = document.querySelector(".winner");
    const entriesEl = document.getElementById("nameList");
    const logEl = document.getElementById("log");
    logEl.scrollTop = logEl.scrollHeight;

    entriesEl.addEventListener("keyup", function() {
      const names = this.innerText.split("\n").map(name => name.trim()).filter(name => name);
      const raffleKey = new URLSearchParams(window.location.search).get("share");
      if (raffleKey) {
        saveEntries(raffleKey, names);
      }
    });

    function randomName() {
      const ENTRIES = entriesEl.innerText.split("\n").map((i) => i.trim()).filter((i) => i);
      const rand = Math.floor(Math.random() * ENTRIES.length);
      const name = ENTRIES[rand];
      namesEl.innerText = name;
    }

    function rollClick() {
      const ENTRIES = entriesEl.innerText.split("\n").map((i) => i.trim()).filter((i) => i);
      if (ENTRIES.length === 0) {
        alert("ไม่มีรายชื่อผู้เข้าร่วม กรุณาเพิ่มรายชื่อก่อนกดสุ่ม");
        return;
      }

      const speedInput = document.getElementById("speed").value;
      const speed = parseInt(speedInput, 10) || 10;
      const iterations = 30;
      const totalDelay = speed * iterations;

      rollEl.classList.add("hide");
      rollAgainEl.classList.add("hide");
      winnerEl.classList.add("hide");
      namesEl.classList.remove("hide");

      setDeceleratingTimeout(randomName, speed, iterations);

      setTimeout(() => {
        namesEl.classList.add("hide");
        winnerEl.classList.remove("hide");
        rollAgainEl.classList.remove("hide");

        const winner = namesEl.innerText;
        const timestamp = new Date().toLocaleTimeString();
        winnerEl.innerHTML = `<div class="d-flex flex-column flex-center gap-2">
                                <span class="fs-2hx">ผู้รับรางวัล</span>
                                <span class="fs-3hx fw-bold text-danger">${winner}</span>
                              </div>
                              `;

        // ลบผู้ชนะออกจากรายชื่อ
        const raffleKey = new URLSearchParams(window.location.search).get("share");
        if (raffleKey) {
          $.ajax({
            url: "./actions/log_winner.php",
            type: "POST",
            data: {
              raffle_key: raffleKey,
              message: winner
            },
            success: function() {
              const updatedEntries = ENTRIES.filter((i) => i !== winner);
              entriesEl.innerHTML = updatedEntries.map((name) => `<div>${name}</div>`).join("");
            },
            error: function(xhr, status, error) {
              console.error("Error logging winner:", error);
            },
          });
        }

        // บันทึกผลผู้ชนะ
        const logEntry = document.createElement("div");
        logEntry.classList.add("fs-3");
        logEntry.classList.add("text-danger");
        logEntry.classList.add("text-nowrap");
        logEntry.textContent = `(${timestamp}): ${winner}`;
        logEl.appendChild(logEntry);
        logEl.scrollTop = logEl.scrollHeight; // เลื่อนลงล่างสุด
      }, totalDelay);
    }

    function setDeceleratingTimeout(callback, factor, times) {
      const internalCallback = ((t, counter) => {
        return () => {
          if (--t > 0) {
            setTimeout(internalCallback, ++counter * factor);
            callback();
          }
        };
      })(times, 0);

      setTimeout(internalCallback, factor);
    }

    function checkDuplicates() {
      const names = entriesEl.innerText.split("\n").map((i) => i.trim()).filter((i) => i);
      const nameSet = new Set();
      const duplicates = new Set();

      names.forEach(name => {
        if (nameSet.has(name)) {
          duplicates.add(name);
        } else {
          nameSet.add(name);
        }
      });

      entriesEl.childNodes.forEach(node => {
        if (node.nodeType === Node.TEXT_NODE || node.nodeType === Node.ELEMENT_NODE) {
          const text = node.textContent.trim();
          if (duplicates.has(text)) {
            node.classList.add("duplicate");
          } else {
            if (hasClass(node, "duplicate")) {
              node.classList.remove("duplicate");
            }
          }
        }
      });
    }

    function hasClass(element, cls) {
      return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
    }

    function saveEntries(raffleKey, names) {
      $.ajax({
        url: "./actions/save_entries.php",
        type: "POST",
        data: {
          raffle_key: raffleKey,
          names: names
        },
        success: function(response) {
          console.log("Entries updated:", response);
        },
        error: function(xhr, status, error) {
          console.error("Error updating entries:", error);
        },
      });
    }
  </script>
</body>

</html>