<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เกมจับฉลาก</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }

    #nameList {
      height: 555px;
      border: 1px solid #ddd;
      padding: 5px;
      background: #f9f9f9;
      overflow-y: auto;
    }

    #nameList .duplicate {
      background-color: red;
      color: white;
    }

    .winner {
      font-size: 32px;
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
      font-size: 24px;
      font-weight: bold;
      color: white;
      background: linear-gradient(145deg, #00b4d8, #0077b6);
      border: none;
      border-radius: 10px;
      padding: 10px 20px;
      box-shadow: 0 5px #005f73;
      transition: transform 0.1s, box-shadow 0.1s;
    }

    .btn-3d:active {
      transform: translateY(5px);
      box-shadow: 0 1px #005f73;
    }

    #log {
      height: 555px;
      border: 1px solid #ddd;
      padding: 5px;
      background: #eef7eb;
      overflow-y: auto;
    }
  </style>
</head>
<body>
  <div class="row">
    <div class="col-md-3">
      <div class="card">
        <div class="card-header">
          <h3>รายชื่อผู้เข้าร่วม</h3>
        </div>
        <div class="card-body">
          <div id="nameList" contenteditable="true"></div>
        </div>
        <div class="card-footer text-center">
          <button class="btn btn-primary" onclick="checkDuplicates()">ตรวจสอบรายชื่อซ้ำ</button>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h3>เกมจับฉลาก</h3>
        </div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <div class="d-flex flex-column flex-center gap-3">
            <div class="d-flex flex-column flex-center gap-3">
              <label for="speed" class="form-label">ตั้งค่าความเร็วการสุ่ม (มิลลิวินาที):</label>
              <input id="speed" type="number" class="form-control mb-3" placeholder="ใส่ค่าความเร็ว" min="1" value="10">
            </div>
            <div class="d-flex flex-column flex-column-fluid flex-center gap-3">
              <div class="names hide"></div>
              <div class="winner hide"></div>
            </div>
          </div>
        </div>
        <div class="card-footer text-center">
          <button class="btn-3d" onclick="rollClick()">สุ่ม</button>
          <button class="btn-3d hide mt-2" onclick="rollClick()">สุ่มอีกครั้ง</button>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card">
        <div class="card-header">
          <h3>บันทึกผล</h3>
        </div>
        <div class="card-body">
          <div id="log"></div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const rollEl = document.querySelector(".btn-3d:not(.hide)");
    const rollAgainEl = document.querySelector(".btn-3d.hide");
    const namesEl = document.querySelector(".names");
    const winnerEl = document.querySelector(".winner");
    const entriesEl = document.getElementById("nameList");
    const logEl = document.getElementById("log");

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
        winnerEl.innerHTML = `<span>ผู้ชนะคือ...</span><br>${winner}`;

        // ลบผู้ชนะออกจากรายชื่อ
        const updatedEntries = ENTRIES.filter((i) => i !== winner);
        entriesEl.innerHTML = updatedEntries.map((name) => `<div>${name}</div>`).join("");

        // บันทึกผลผู้ชนะ
        const logEntry = document.createElement("div");
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
            node.classList.remove("duplicate");
          }
        }
      });
    }
  </script>
</body>
</html>
