<?php
include('connect.php');
session_start();

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $search_condition = " WHERE username LIKE '%$search%' OR fname LIKE '%$search%' OR lname LIKE '%$search%' OR email LIKE '%$search%'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM account" . $search_condition;
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users with pagination and search
$query = "SELECT * FROM account" . $search_condition . " LIMIT $offset, $records_per_page";
$users = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>La Croissanterie Admin - Users</title>
  <style>
    :root {
      --primary-color: #513826;
      --accent-color: #a67c52;
      --light-color: #f5f1eb;
      --dark-color: #362517;
      --text-color: #333;
      --font-main: 'Helvetica Neue', Arial, sans-serif;
      --sidebar-width: 250px;
    }
    
    body {
      margin: 0;
      font-family: var(--font-main);
      background-color: var(--light-color);
      color: var(--text-color);
      line-height: 1.6;
      display: flex;
      min-height: 100vh;
    }
    
    /* Sidebar Styles */
    .sidebar {
      width: var(--sidebar-width);
      background-color: var(--primary-color);
      color: white;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      transition: all 0.3s;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    
    .sidebar-header {
      padding: 20px;
      display: flex;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .logo-text {
      font-size: 18px;
      font-weight: 300;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-left: 10px;
      color: white;
    }
    
    .sidebar-menu {
      padding: 20px 0;
    }
    
    .menu-item {
      padding: 15px 20px;
      display: flex;
      align-items: center;
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      transition: all 0.3s;
      border-left: 3px solid transparent;
    }
    
    .menu-item:hover, .menu-item.active {
      background-color: rgba(255,255,255,0.1);
      color: white;
      border-left: 3px solid var(--accent-color);
    }
    
    .menu-item i {
      margin-right: 15px;
      width: 20px;
      text-align: center;
    }
    
    /* Main Content Styles */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      padding: 20px;
      transition: all 0.3s;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      background-color: white;
      padding: 15px 20px;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .page-title {
      font-size: 24px;
      font-weight: 400;
      color: var(--primary-color);
    }
    
    .user-info {
      display: flex;
      align-items: center;
    }
    
    .user-name {
      margin-right: 15px;
    }
    
    .logout-btn {
      color: var(--text-color);
      text-decoration: none;
      padding: 5px 10px;
      border: 1px solid #ddd;
      border-radius: 3px;
      transition: all 0.3s;
    }
    
    .logout-btn:hover {
      background-color: #f2f2f2;
    }
    
    /* Table Styles */
    .table-container {
      background-color: white;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      overflow: hidden;
      margin-bottom: 30px;
    }
    
    .table-header {
      padding: 15px 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .table-title {
      font-size: 18px;
      font-weight: 400;
      color: var(--primary-color);
    }
    
    .search-container {
      display: flex;
      align-items: center;
    }
    
    .search-input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      margin-right: 10px;
    }
    
    .search-btn, .add-btn {
      padding: 8px 15px;
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .search-btn:hover, .add-btn:hover {
      background-color: #8c6744;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    th {
      background-color: var(--light-color);
      font-weight: 400;
      color: var(--primary-color);
    }
    
    tr:hover {
      background-color: #f9f9f9;
    }
    
    .action-btns {
      display: flex;
      gap: 5px;
    }
    
    .edit-btn, .delete-btn, .view-btn {
      padding: 5px 10px;
      background-color: transparent;
      border: 1px solid;
      border-radius: 3px;
      cursor: pointer;
      font-size: 12px;
      transition: all 0.3s;
    }
    
    .edit-btn {
      border-color: #4caf50;
      color: #4caf50;
    }
    
    .edit-btn:hover {
      background-color: #4caf50;
      color: white;
    }
    
    .delete-btn {
      border-color: #f44336;
      color: #f44336;
    }
    
    .delete-btn:hover {
      background-color: #f44336;
      color: white;
    }
    
    .view-btn {
      border-color: #2196f3;
      color: #2196f3;
    }
    
    .view-btn:hover {
      background-color: #2196f3;
      color: white;
    }
    
    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    
    .pagination-btn {
      padding: 5px 10px;
      margin: 0 5px;
      border: 1px solid #ddd;
      background-color: white;
      cursor: pointer;
      transition: all 0.3s;
      color: var(--text-color);
      text-decoration: none;
      display: inline-block;
    }
    
    .pagination-btn:hover, .pagination-btn.active {
      background-color: var(--accent-color);
      color: white;
      border-color: var(--accent-color);
    }
    
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      overflow: auto;
      justify-content: center;
      align-items: center;
    }
    
    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 20px;
      border-radius: 5px;
      width: 50%;
      max-width: 500px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      position: relative;
    }
    
    .close-btn {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 400;
      color: var(--primary-color);
    }
    
    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .form-textarea {
      resize: vertical;
      min-height: 100px;
    }
    
    .submit-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .submit-btn:hover {
      background-color: #8c6744;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
      .sidebar {
        width: 70px;
        z-index: 999;
      }
      
      .sidebar .logo-text, .sidebar .menu-text {
        display: none;
      }
      
      .sidebar-header {
        justify-content: center;
      }
      
      .menu-item {
        padding: 15px;
        justify-content: center;
      }
      
      .menu-item i {
        margin-right: 0;
      }
      
      .main-content {
        margin-left: 70px;
      }
      
      .modal-content {
        width: 80%;
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      <a href="user_list.php" class="menu-item active">
        <i class="fas fa-users"></i>
        <span class="menu-text">Users</span>
      </a>
      <a href="#" class="menu-item">
        <i class="fas fa-shopping-cart"></i>
        <span class="menu-text">Orders</span>
      </a>
      <a href="#" class="menu-item">
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
      <h1 class="page-title">Registered Users</h1>
      <div class="user-info">
        <span class="user-name">Admin User</span>
        <a href="#" class="logout-btn">Logout</a>
      </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
      <div class="table-header">
        <h2 class="table-title">Users List</h2>
        <form id="searchForm" action="user_list.php" method="GET" class="search-container">
          <input type="text" name="search" class="search-input" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="search-btn">Search</button>
        </form>
      </div>
      <table>
        <thead>
          <tr>
            <th>Account ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($user = $users->fetch_assoc()): ?>
            <tr>
              <td><?= $user['AccountID'] ?></td>
              <td><?= $user['username'] ?></td>
              <td><?= $user['fname'] . ' ' . $user['lname'] ?></td>
              <td><?= $user['email'] ?></td>
              <td><?= $user['status'] ?></td>
              <td class="action-btns">
                <button class="view-btn" onclick="openViewModal('<?= $user['username'] ?>', 
                                              '<?= $user['fname'] ?>', 
                                              '<?= $user['lname'] ?>', 
                                              '<?= $user['email'] ?>', 
                                              '<?= $user['status'] ?>')">View</button>
                <button class="delete-btn" onclick="openDeleteModal(<?= $user['AccountID'] ?>)">Delete</button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <div class="pagination">
        <?php if($total_pages > 1): ?>
          <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="pagination-btn <?= $page == $i ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- View Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeViewModal()">&times;</span>
      <h3 style="color: var(--primary-color); margin-bottom: 20px;">User Details</h3>
      <div class="form-group">
        <label class="form-label">Username</label>
        <p id="viewUsername" style="margin: 5px 0 15px 0;"></p>
      </div>
      <div class="form-group">
        <label class="form-label">Name</label>
        <p id="viewName" style="margin: 5px 0 15px 0;"></p>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <p id="viewEmail" style="margin: 5px 0 15px 0;"></p>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <p id="viewStatus" style="margin: 5px 0 15px 0;"></p>
      </div>
      <div style="text-align: right; margin-top: 20px;">
        <button class="submit-btn" onclick="closeViewModal()">Close</button>
      </div>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
      <h3 style="color: var(--primary-color); margin-bottom: 20px;">Enter Admin Password to Confirm</h3>
      <form id="deleteForm" method="POST" action="delete_user.php">
        <input type="hidden" name="account_id" id="accountId">
        <div class="form-group">
          <label class="form-label">Admin Password</label>
          <input type="password" name="admin_password" class="form-input" placeholder="Enter your password" required>
        </div>
        <div style="text-align: right; margin-top: 20px;">
          <button type="submit" class="submit-btn" style="background-color: #f44336;">Delete</button>
          <button type="button" class="submit-btn" style="background-color: #ddd; color: #333; margin-left: 10px;" onclick="closeDeleteModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openDeleteModal(id) {
      document.getElementById('accountId').value = id;
      document.getElementById('deleteModal').style.display = "flex";
    }
    
    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = "none";
    }
    
    function openViewModal(username, fname, lname, email, status) {
      document.getElementById('viewUsername').innerText = username;
      document.getElementById('viewName').innerText = fname + ' ' + lname;
      document.getElementById('viewEmail').innerText = email;
      document.getElementById('viewStatus').innerText = status;
      document.getElementById('viewModal').style.display = "flex";
    }
    
    function closeViewModal() {
      document.getElementById('viewModal').style.display = "none";
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
      }
    }
    
    // Clear search functionality
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.querySelector('.search-input');
      const searchForm = document.getElementById('searchForm');
      
      searchForm.addEventListener('submit', function(e) {
        if (searchInput.value.trim() === '') {
          // If search is empty, redirect to page without search parameter
          e.preventDefault();
          window.location.href = 'user_list.php';
        }
      });
    });
  </script>

</body>
</html>