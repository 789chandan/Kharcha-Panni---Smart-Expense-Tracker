
<?php
// 1. Database connection include karein
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- Sabhi Queries ---
$u_id = $_SESSION['user_id']; 

// --- Sabhi Queries (Isme check karein ki $u_id hi likha hai, $uid nahi) ---
$sql = "SELECT t.id, t.amount, t.note, t.date, c.name as category_name, c.type 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = '$u_id' 
        ORDER BY t.date DESC";
$result = $conn->query($sql);

$total_income = $conn->query("SELECT SUM(amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE c.type = 'income' AND t.user_id = '$u_id'")->fetch_assoc()['total'] ?? 0;

$total_expense = $conn->query("SELECT SUM(amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE c.type = 'expense' AND t.user_id = '$u_id'")->fetch_assoc()['total'] ?? 0;

$balance = $total_income - $total_expense;

// --- 2. AB AI SUGGESTION LOGIC LIKHEIN (Kyuki ab $balance define ho chuka hai) ---
$suggestion_msg = ""; 
$suggestion_class = "alert-info";

if ($balance < 0) {
    $suggestion_msg = "🚨 Danger: Aapka balance negative hai! Kharchon par control karein.";
    $suggestion_class = "alert-danger";
} 
elseif ($balance > 0 && $balance < 1000) {
    $suggestion_msg = "⚠️ Warning: Aapka balance ₹1000 se kam hai. Agle kuch din dhyan se kharch karein.";
    $suggestion_class = "alert-warning";
}
elseif ($total_income > 0 && ($total_expense / $total_income) > 0.8) {
    $suggestion_msg = "🚨 Alert: Aap apni 80% income kharch kar chuke hain. Savings badhayein!";
    $suggestion_class = "alert-danger";
}
else {
    $suggestion_msg = "✅ Sab sahi hai! Aapka budget filhal control mein hai.";
    $suggestion_class = "alert-success";
}

// 3. Month wise data (User specific)
$current_month = date('m'); 
$current_year = date('Y');
$m_income = $conn->query("SELECT SUM(amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE c.type = 'income' AND MONTH(t.date) = '$current_month' AND YEAR(t.date) = '$current_year' AND t.user_id = '$u_id'")->fetch_assoc()['total'] ?? 0;

$m_expense = $conn->query("SELECT SUM(amount) as total FROM transactions t JOIN categories c ON t.category_id = c.id WHERE c.type = 'expense' AND MONTH(t.date) = '$current_month' AND YEAR(t.date) = '$current_year' AND t.user_id = '$u_id'")->fetch_assoc()['total'] ?? 0;

// Chart Data Preparation
// Pehle empty arrays define karein taki error na aaye
$chart_labels = []; 
$chart_data = [];
$analysis_data = []; 

// Query mein strictly check karein ki user_id match ho rahi hai
$cat_analysis_res = $conn->query("SELECT c.name, SUM(t.amount) as total 
                                 FROM transactions t 
                                 JOIN categories c ON t.category_id = c.id 
                                 WHERE c.type = 'expense' AND t.user_id = '$u_id' 
                                 GROUP BY c.name 
                                 ORDER BY total DESC");

if ($cat_analysis_res && $cat_analysis_res->num_rows > 0) {
    while($row = $cat_analysis_res->fetch_assoc()) {
        $analysis_data[] = $row;
        $chart_labels[] = $row['name'];
        $chart_data[] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .nav-brand { font-weight: bold; font-size: 1.5rem; }
        .balance-card { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
    </style>
</head>
<body>
<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
  <div id="successToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle-fill me-2"></i> Transaction added successfully!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toast ko show karna
        var toastEl = document.getElementById('successToast');
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 }); // 3000ms = 3 seconds
        toast.show();

        // 3 second baad URL se '?status=success' hatana taki reload pe bar-bar na dikhe
        setTimeout(function() {
            const url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, document.title, url);
        }, 3500);
    });
</script>
<?php endif; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 sticky-top">
    <div class="container">
        <a class="navbar-brand nav-brand" href="#">💰 KharchaPaani</a>
        <a class="navbar-brand nav-brand">"Aapka personal pocket manager"</a>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#transactionModal">+ Add Transaction</button>
            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">New Category</button>
            <a href="export.php" class="btn btn-info btn-sm">📥 Excel</a>
        </div>
        
        <div class="ms-auto text-white">
            Welcome, <?php echo $_SESSION['username']; ?>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mb-5">
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card p-3 bg-white border-start border-warning border-5">
                <h5 class="mb-0">✨ Month Overview: <?php echo date('F Y'); ?></h5>
                <div class="d-flex gap-4 mt-2 flex-wrap">
                    <span>Monthly Income: <b>₹<?php echo number_format($m_income); ?></b></span>
                    <span>Monthly Expense: <b>₹<?php echo number_format($m_expense); ?></b></span>
                    <span>Savings: <b class="text-success">₹<?php echo number_format($m_income - $m_expense); ?></b></span>
                </div>
            </div>
        </div>
      <div class="row mb-3">
    <div class="col-12">
        <?php if ($suggestion_msg != ""): ?>
            <div class="alert <?php echo $suggestion_class; ?> d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-lightbulb-fill me-2"></i> 
                <div>
                    <strong>AI Suggestion:</strong> <?php echo $suggestion_msg; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
        <div class="col-md-4"><div class="card p-4 text-center border-bottom border-success border-5"><h6>TOTAL INCOME</h6><h2 class="text-success">₹<?php echo number_format($total_income); ?></h2></div></div>
        <div class="col-md-4"><div class="card p-4 text-center border-bottom border-danger border-5"><h6>TOTAL EXPENSE</h6><h2 class="text-danger">₹<?php echo number_format($total_expense); ?></h2></div></div>
        <div class="col-md-4"><div class="card p-4 text-center balance-card"><h6>CURRENT BALANCE</h6><h2>₹<?php echo number_format($balance); ?></h2></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card p-4 mb-4 text-center">
                <h5>Expense Distribution</h5>
                <canvas id="expenseChart" style="max-height: 250px;"></canvas>
            </div>
            <div class="card p-4">
                <h5>Category Breakdown</h5>
                <?php if(empty($analysis_data)) echo "<p class='text-muted small'>No expense data yet.</p>"; ?>
                <?php foreach($analysis_data as $row): ?>
                <div class="d-flex justify-content-between p-2 border-bottom">
                    <span><?php echo $row['name']; ?></span>
                    <span class="fw-bold text-danger">₹<?php echo number_format($row['total']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card p-4">
                <h5 class="mb-3">Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Category</th><th>Amount</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M', strtotime($row['date'])); ?></td>
                                    <td><b><?php echo $row['category_name']; ?></b><br><small class="text-muted"><?php echo $row['note']; ?></small></td>
                                    <td class="<?php echo ($row['type'] == 'income') ? 'text-success' : 'text-danger'; ?> fw-bold">
                                        ₹<?php echo number_format($row['amount']); ?>
                                    </td>
                                    <td><a href="delete.php?id=<?php echo $row['id']; ?>" class="text-danger h5 text-decoration-none" onclick="return confirm('Delete this record?')">×</a></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No transactions found. Add some!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="save_transaction.php" method="POST">
                <div class="modal-body">
                    <label class="small fw-bold">Amount (₹)</label>
                    <input type="number" step="0.01" class="form-control mb-3" name="amount" placeholder="0.00" required>
                    
                    <label class="small fw-bold">Category</label>
                    <select class="form-select mb-3" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php
                        $cat_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                        while($cat = $cat_res->fetch_assoc()) echo "<option value='".$cat['id']."'>" . $cat['name'] . " (" . $cat['type'] . ")</option>";
                        ?>
                    </select>
                    
                    <label class="small fw-bold">Date</label>
                    <input type="date" class="form-control mb-3" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    
                    <label class="small fw-bold">Note</label>
                    <input type="text" class="form-control mb-3" name="note" placeholder="What was this for?">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add_category.php" method="POST">
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" name="cat_name" placeholder="Category Name (e.g. Food)" required>
                    <select class="form-select mb-3" name="cat_type">
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('expenseChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
                hoverOffset: 4
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<footer class="text-center">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <p class="footer-text mb-0">
                    💰 <b>KharchaPaani</b> &copy; <?php echo date('Y'); ?> 
                    | Simple, Smart & Secure
                </p>
                <p class="footer-text mt-1">
                    Designed and Developed by 
                    <a href="#" class="dev-name">Chandan Kumar - IT Engineer </a>
                </p>
            </div>
        </div>
    </div>
</footer>

<div id="ai-chat-wrapper" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999;">
        <div id="chat-icon" class="shadow-lg d-flex align-items-center justify-content-center" 
             style="width: 65px; height: 65px; background: linear-gradient(135deg, #6e8efb, #a777e3); border-radius: 50%; cursor: pointer;">
            <span style="font-size: 30px;">🤖</span>
        </div>

        <div id="ai-window" class="card shadow d-none" style="width: 320px; position: absolute; bottom: 80px; right: 0; border-radius: 15px;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span>KharchaPanni AI Assistant</span>
                <button type="button" class="btn-close btn-close-white" id="ai-close"></button>
            </div>
            <div id="ai-body" class="card-body" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                <div class="mb-2"><small><b>Chat Bot:</b> Namaste! Main aapka assistant hoon.</small></div>
            </div>
            <div class="card-footer">
                <div class="input-group">
                    <input type="text" id="ai-input" class="form-control" placeholder="Poochiye...">
                    <button class="btn btn-primary" id="ai-send">🚀</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Window toggle logic
        document.getElementById('chat-icon').onclick = () => document.getElementById('ai-window').classList.toggle('d-none');
        document.getElementById('ai-close').onclick = () => document.getElementById('ai-window').classList.add('d-none');

        function sendMessage() {
    let input = document.getElementById('ai-input');
    let msg = input.value.trim().toLowerCase(); // Message ko small letters mein karein
    if(!msg) return;

    let body = document.getElementById('ai-body');
    // User ka message dikhayein
    body.innerHTML += `<div class="text-end mb-2"><small class="bg-primary text-white p-2 rounded d-inline-block">${input.value}</small></div>`;
    input.value = "";

    // Bot ka reply logic
    setTimeout(() => {
        let reply = "";

        // Alag-alag sawalon ke liye alag-alag jawab
        if (msg.includes("good morning") || msg.includes("gm") || msg.includes("hello") || msg.includes("hi")) {
            reply = "Suprabhat! ☀️ Main aapka KharchaPaani assistant hoon. Aaj main aapki kaise madad karu?";
        } 
        else if (msg.includes("balance") || msg.includes("paisa") || msg.includes("kitna hai")) {
            reply = "💰 Aapka current balance ₹<?php echo number_format($balance); ?> hai.";
        } 
        else if (msg.includes("income") || msg.includes("kamai")) {
            reply = "📈 Is mahine ki kul kamai ₹<?php echo number_format($m_income); ?> hai.";
        }
        else if (msg.includes("expense") || msg.includes("kharcha")) {
            reply = "📉 Is mahine aapne ₹<?php echo number_format($m_expense); ?> kharch kiye hain.";
        }
        else if (msg.includes("tip") || msg.includes("advice")) {
            reply = "💡 Tip: Rozana ek cup chai ka paisa bacha kar mahine ke aakhir mein aap ₹500+ bacha sakte hain!";
        }
        else {
            reply = "Maaf kijiye, main abhi seekh raha hoon. Aap mujhse Balance, Income ya Expense ke baare mein pooch sakte hain.";
        }

        body.innerHTML += `<div class="mb-2"><small class="bg-light p-2 rounded d-inline-block border"><b>Bot:</b> ${reply}</small></div>`;
        body.scrollTop = body.scrollHeight;
    }, 500);
}

        document.getElementById('ai-send').onclick = sendMessage;
        // 1. Enter key dabane par message bhejne ke liye
document.getElementById('ai-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// 2. Button click par message bhejne ke liye (ye shayad pehle se hai)
document.getElementById('ai-send').onclick = sendMessage;
    </script>
</body>
</html>                                                            