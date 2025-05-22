<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit();
}

// Check if transaction ID is provided
if (!isset($_GET['id'])) {
    die("Transaction ID is required");
}

$transaction_id = $_GET['id'];
$print_mode = isset($_GET['print']) && $_GET['print'] == 'true';

// Function to load and parse XML
function loadTransactions() {
    $xml = simplexml_load_file('transactions.xml');
    if ($xml === false) {
        die('Error loading XML file');
    }
    return $xml;
}

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M d, Y h:i A');
}

// Load transactions
$transactions = loadTransactions();

// Find the specific transaction
$transaction = null;
foreach ($transactions->transaction as $t) {
    if ((string)$t['id'] == $transaction_id) {
        $transaction = $t;
        break;
    }
}

// If transaction not found
if ($transaction === null) {
    die("Transaction not found");
}

// Get admin information
require_once 'connect.php'; // Database connection

$admin_username = $_SESSION['admin'];
$stmt = $conn->prepare("SELECT fullname, profile_picture FROM admin WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();

$admin_fullname = "Admin User"; // Default value
$admin_profile = ""; // Default empty profile picture path

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $admin_fullname = $row['fullname'];
  $admin_profile = $row['profile_picture'];
}
$stmt->close();

// Include TCPDF library (make sure it's installed)
if ($print_mode) {
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('La Croissanterie');
    $pdf->SetAuthor('La Croissanterie Admin');
    $pdf->SetTitle('Transaction Receipt #' . $transaction_id);
    $pdf->SetSubject('Transaction Receipt');
    
    // Set default header data
    $pdf->setHeaderData('', 0, 'La Croissanterie', 'Transaction Receipt #' . $transaction_id);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Transaction details
    $html = '<h1 style="text-align:center;">Transaction Receipt</h1>';
    $html .= '<hr>';
    
    // Transaction info in a table
    $html .= '<table border="0" cellpadding="5">
        <tr>
            <td width="30%"><strong>Transaction ID:</strong></td>
            <td width="70%">' . $transaction_id . '</td>
        </tr>
        <tr>
            <td><strong>Order ID:</strong></td>
            <td>' . $transaction['order_id'] . '</td>
        </tr>
        <tr>
            <td><strong>User ID:</strong></td>
            <td>' . $transaction['user_id'] . '</td>
        </tr>
        <tr>
            <td><strong>Date:</strong></td>
            <td>' . formatDate($transaction['date']) . '</td>
        </tr>
        <tr>
            <td><strong>Payment Method:</strong></td>
            <td>' . ucfirst($transaction->payment_method) . '</td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>' . ucfirst($transaction->status) . '</td>
        </tr>
    </table>';
    
    $html .= '<br><h3>Items</h3>';
    
    // Items table
    $html .= '<table border="1" cellpadding="5">
        <thead>
            <tr style="background-color:#f8f9fa; font-weight:bold;">
                <th width="40%">Product</th>
                <th width="20%">Unit Price</th>
                <th width="15%">Quantity</th>
                <th width="25%">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($transaction->items->item as $item) {
        $html .= '<tr>
            <td>' . $item->name . '</td>
            <td>₱' . number_format((float)$item->price, 2) . '</td>
            <td>' . $item->quantity . '</td>
            <td>₱' . number_format((float)$item->total, 2) . '</td>
        </tr>';
    }
    
    $html .= '</tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="3" align="right">Total:</td>
                <td>₱' . number_format((float)$transaction->total_amount, 2) . '</td>
            </tr>
        </tfoot>
    </table>';
    
    $html .= '<br><p>This is an official receipt for your transaction. Thank you for your business!</p>';
    $html .= '<p style="font-size:9px;">Generated on: ' . date('Y-m-d H:i:s') . ' by ' . htmlspecialchars($admin_fullname) . '</p>';
    
    // Print HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('Transaction_' . $transaction_id . '.pdf', 'I');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction #<?= $transaction_id ?> - La Croissanterie</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        .transaction-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .transaction-header h1 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        .transaction-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .detail-item {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        .detail-value {
            font-size: 16px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: var(--light-color);
            color: var(--primary-color);
            font-weight: 500;
            text-align: left;
            padding: 12px 15px;
        }
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .items-table tfoot td {
            font-weight: bold;
            border-top: 2px solid #eee;
        }
        .print-actions {
            text-align: center;
            margin-top: 30px;
        }
        .btn-print {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn-print:hover {
            background-color: var(--secondary-color);
        }
        .btn-print i {
            margin-right: 10px;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-left: 10px;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: normal;
            color: white;
        }
        .status-paid {
            background-color: #1cc88a;
        }
        .status-pending {
            background-color: #f6c23e;
            color: #212529;
        }
        @media print {
            .sidebar, .header, .print-actions {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    <div class="sidebar-menu">
      <a href="admindashboard.php" class="menu-item">
        <i class="fas fa-tachometer-alt"></i>
        <span class="menu-text">Dashboard</span>
      </a>
      <a href="product.php" class="menu-item">
        <i class="fas fa-box"></i>
        <span class="menu-text">Products</span>
      </a>
      <a href="user_list.php" class="menu-item">
        <i class="fas fa-users"></i>
        <span class="menu-text">Users</span>
      </a>
      <a href="#" class="menu-item">
        <i class="fas fa-shopping-cart"></i>
        <span class="menu-text">Orders</span>
      </a>
      <a href="adminTransaction.php" class="menu-item active">
        <i class="fas fa-money-bill-wave"></i>
        <span class="menu-text">Transactions</span>
      </a>
      <a href="#" class="menu-item">
        <i class="fas fa-chart-bar"></i>
        <span class="menu-text">Reports</span>
      </a>
      <a href="#" class="menu-item">
        <i class="fas fa-cog"></i>
        <span class="menu-text">Settings</span>
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <h1 class="page-title">Transaction Details</h1>
      <div class="user-info">
        <?php if(!empty($admin_profile)): ?>
          <img src="<?php echo htmlspecialchars($admin_profile); ?>" alt="Profile" class="profile-pic">
        <?php endif; ?>
        <span class="user-name"><?php echo htmlspecialchars($admin_fullname); ?></span>
        <a href="#" onclick="confirmLogout()" class="logout-btn">Logout</a>
      </div>
    </div>

    <div class="dashboard-content">
      <div class="print-container">
        <div class="transaction-header">
          <h1>Transaction Receipt</h1>
          <p>La Croissanterie Bakery</p>
        </div>
        
        <div class="transaction-details">
          <div>
            <div class="detail-item">
              <span class="detail-label">Transaction ID</span>
              <span class="detail-value"><?= $transaction_id ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Order ID</span>
              <span class="detail-value"><?= $transaction['order_id'] ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">User ID</span>
              <span class="detail-value"><?= $transaction['user_id'] ?></span>
            </div>
          </div>
          <div>
            <div class="detail-item">
              <span class="detail-label">Date</span>
              <span class="detail-value"><?= formatDate($transaction['date']) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Payment Method</span>
              <span class="detail-value"><?= ucfirst($transaction->payment_method) ?></span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Status</span>
              <span class="detail-value">
                <span class="status-badge status-<?= strtolower($transaction->status) ?>">
                  <?= ucfirst($transaction->status) ?>
                </span>
              </span>
            </div>
          </div>
        </div>
        
        <h2>Items</h2>
        <table class="items-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transaction->items->item as $item): ?>
            <tr>
              <td><?= $item->name ?></td>
              <td>₱<?= number_format((float)$item->price, 2) ?></td>
              <td><?= $item->quantity ?></td>
              <td>₱<?= number_format((float)$item->total, 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="text-align: right;">Total</td>
              <td>₱<?= number_format((float)$transaction->total_amount, 2) ?></td>
            </tr>
          </tfoot>
        </table>
        
        <div class="print-actions">
          <button onclick="window.location.href='transaction.php?id=<?= $transaction_id ?>&print=true'" class="btn-print">
            <i class="fas fa-print"></i> Print as PDF
          </button>
          <button onclick="window.location.href='adminTransaction.php'" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Transactions
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function confirmLogout() {
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
      }
    }
  </script>
</body>
</html>