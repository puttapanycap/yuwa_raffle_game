<?php
// Database configuration
$host = 'localhost';
$db = 'yuwa-raffle-game';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Generate or get raffle key
    if (!isset($_GET['share'])) {
        $raffle_key = bin2hex(random_bytes(16)); // Generate a 32-character key
        $stmt = $pdo->prepare("INSERT INTO raffle_keys (raffle_key) VALUES (:raffle_key)");
        $stmt->execute(['raffle_key' => $raffle_key]);
        header("Location: start.php?share=$raffle_key");
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
    $stmt = $pdo->prepare("SELECT name FROM raffle_entries WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $entries = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกมจับฉลาก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            border: 1px solid #ddd;
        }

        .card-header {
            background: #f8f9fa;
            font-size: 20px;
            font-weight: bold;
        }

        .card-footer {
            background: #f1f1f1;
        }

        .roll {
            font-size: 24px;
            font-weight: bold;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 3px 3px 5px rgba(0, 0, 0, 0.2);
        }

        .hide {
            display: none;
        }

        #log {
            height: 555px;
            overflow-y: auto;
            background: #eef7eb;
            border: 1px solid #ddd;
            padding: 10px;
        }

        #log div {
            margin-bottom: 5px;
        }

        #nameList {
            height: 555px;
            overflow-y: auto;
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .winner {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Column 1: Names -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-header">รายชื่อผู้เข้าร่วม</div>
                    <div id="nameList" contenteditable="true"><?= implode("\n", $entries) ?></div>
                    <div class="card-footer">
                        <button class="btn btn-primary" onclick="checkDuplicates()">ตรวจสอบชื่อซ้ำ</button>
                    </div>
                </div>
            </div>

            <!-- Column 2: Game UI -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">เกมจับฉลาก</div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <div class="d-flex flex-column flex-center gap-3">
                            <label for="speed" class="form-label">ตั้งค่าความเร็วการสุ่ม (มิลลิวินาที):</label>
                            <input id="speed" type="number" class="form-control mb-3" placeholder="ใส่ค่าความเร็ว" min="1" value="10">
                        </div>
                        <div class="d-flex flex-column flex-column-fluid flex-center gap-3">
                            <div class="names hide"></div>
                            <div class="winner hide"></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-danger roll" onclick="rollClick()">สุ่ม</button>
                        <button class="btn btn-secondary hide roll-again" onclick="rollClick()">สุ่มอีกครั้ง</button>
                    </div>
                </div>
            </div>

            <!-- Column 3: Log -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-header">ประวัติการสุ่ม</div>
                    <div id="log">
                        <?php foreach ($logs as $log): ?>
                            <div><?= $log['created_at'] ?> - <?= htmlspecialchars($log['log_message'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const entriesEl = document.getElementById("nameList");
            const rollEl = document.querySelector(".roll");
            const rollAgainEl = document.querySelector(".roll-again");
            const namesEl = document.querySelector(".names");
            const winnerEl = document.querySelector(".winner");

            entriesEl.addEventListener("input", function() {
                const names = this.innerText.split("\n").map(name => name.trim()).filter(name => name);
                const raffleKey = new URLSearchParams(window.location.search).get("share");

                if (raffleKey) {
                    $.ajax({
                        url: "save_entries.php",
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
            });

            function rollClick() {
                const speedInput = document.getElementById("speed").value;
                const speed = parseInt(speedInput, 10) || 10;
                const iterations = 30;
                const names = entriesEl.innerText.split("\n").map(name => name.trim()).filter(name => name);
                const raffleKey = new URLSearchParams(window.location.search).get("share");

                if (names.length === 0 || !raffleKey) {
                    alert("กรุณาเพิ่มรายชื่อผู้เข้าร่วม");
                    return;
                }

                rollEl.classList.add("hide");
                rollAgainEl.classList.add("hide");
                winnerEl.classList.add("hide");
                namesEl.classList.remove("hide");
                namesEl.innerText = names.join("\n");

                setDeceleratingTimeout(() => {
                    const randomIndex = Math.floor(Math.random() * names.length);
                    namesEl.innerText = names[randomIndex];
                }, speed, iterations);

                setTimeout(() => {
                    const winner = namesEl.innerText;
                    winnerEl.innerHTML = `<span>ผู้ชนะคือ...</span><br>${winner}`;
                    winnerEl.classList.remove("hide");
                    rollAgainEl.classList.remove("hide");

                    // ลบผู้ชนะออกจากระบบ
                    $.ajax({
                        url: "log_winner.php",
                        type: "POST",
                        data: {
                            raffle_key: raffleKey,
                            message: winner
                        },
                        success: function() {
                            const updatedNames = names.filter(name => name !== winner);
                            entriesEl.innerText = updatedNames.join("\n");
                        },
                        error: function(xhr, status, error) {
                            console.error("Error logging winner:", error);
                        },
                    });
                }, speed * iterations);
            }

            rollEl.addEventListener("click", rollClick);
            rollAgainEl.addEventListener("click", rollClick);
        });
    </script>
</body>

</html>