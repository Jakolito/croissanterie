<?php
include('connect.php');
session_start();
if (!isset($_SESSION['admin'])) {
    echo "<script>alert('Please log in again!.'); window.location.href = 'login.php';</script>";
    exit();
}
// XML file path
$xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
$file = file_exists($xmlPath) ? simplexml_load_file($xmlPath) : null;

// Categories XML file path - we'll store categories separately for better persistence
$categoriesXmlPath = 'C:\xampp\htdocs\PASTRY\categories.xml';

// Load or create categories XML file
if (file_exists($categoriesXmlPath)) {
    $categoriesXml = simplexml_load_file($categoriesXmlPath);
} else {
    // Create new categories XML structure if file doesn't exist
    $categoriesXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><categories></categories>');
    
    // Add categories from pastry.xml if available
    if ($file !== false && $file !== null) {
        $existingCategories = [];
        foreach ($file->pastry as $row) {
            $cat = trim((string) $row->producttype);
            if (!in_array($cat, $existingCategories)) {
                $existingCategories[] = $cat;
                $category = $categoriesXml->addChild('category', $cat);
            }
        }
    }
    
    // Save the new categories XML file
    $categoriesXml->asXML($categoriesXmlPath);
}

// Collect categories from the categories XML
$categories = [];
foreach ($categoriesXml->category as $category) {
    $categories[] = trim((string) $category);
}

// Handle category add functionality
if (isset($_POST['add_category'])) {
    $newCategory = trim($_POST['new_category']);
    
    if (!empty($newCategory) && !in_array($newCategory, $categories)) {
        // Add to our PHP array first
        $categories[] = $newCategory;
        
        // Add to XML structure
        $categoriesXml->addChild('category', $newCategory);
        
        // Save updated XML
        $categoriesXml->asXML($categoriesXmlPath);
        
        $_SESSION['message'] = "New category '$newCategory' added successfully!";
    } else {
        $_SESSION['message'] = "Category already exists or is empty!";
    }
}

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : 'All';

$total_items = 0;
$filtered_pastries = [];

// Loop through all pastries and apply filters
if ($file !== false && $file !== null) {
    foreach ($file->pastry as $pastry) {
        $include_in_results = true;
        
        // Apply search filter if present
        if (!empty($search)) {
            $pastry_name = strtolower((string)$pastry->name);
            $pastry_desc = strtolower((string)$pastry->description);
            $search_term = strtolower($search);
            
            if (strpos($pastry_name, $search_term) === false && 
                strpos($pastry_desc, $search_term) === false) {
                $include_in_results = false;
            }
        }
        
        // Apply category filter if not "All"
        if ($filter_category !== 'All') {
            $product_type = trim((string)$pastry->producttype);
            // Debug logging - uncomment if needed
            // error_log("Comparing: '$product_type' with '$filter_category'");
            if (strcasecmp($product_type, $filter_category) !== 0) {
                $include_in_results = false;
            }
        }
        
        if ($include_in_results) {
            $filtered_pastries[] = $pastry;
            $total_items++;
        }
    }
}

// Calculate pagination
$total_pages = ceil($total_items / $items_per_page);
$offset = ($page - 1) * $items_per_page;
$paginated_pastries = array_slice($filtered_pastries, $offset, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Croissanterie </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Cards */
        .stats-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: var(--light-color);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 500;
            margin: 0;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 14px;
            color: #777;
            margin: 0;
        }
        
        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
       
    
    
        
        .table-container {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .table-title {
            font-size: 1.2rem;
            margin: 0;
        }
        
        .filters-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
            width: 100%;
        }
        
        .search-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
        }
        
        .filter-dropdown {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }
        
        .search-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex-grow: 1;
        }
        
        .search-btn, .add-btn, .add-category-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn {
            background-color: #6c757d;
            color: #fff;
        }
        
        .add-btn {
            background-color: #28a745;
            color: #fff;
        }
        
        .add-category-btn {
            background-color: #17a2b8;
            color: #fff;
        }
        
       /* Add these styles to improve table line appearance */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    table th, table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    table th {
        background-color: #f8f9fa;
        font-weight: bold;
        border-bottom: 2px solid #dee2e6;
    }
    
    table tr:hover {
        background-color: #f5f5f5;
    }
    
    table tr:last-child td {
        border-bottom: none;
    }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .no-image {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .edit-btn {
            background-color: #17a2b8;
            color: #fff;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: #fff;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination-btn {
            padding: 8px 15px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #343a40;
        }
        
        .pagination-btn.active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        
        .modal-content {
            border-radius: 5px;
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-radius: 5px 5px 0 0;
        }
        
        .submit-btn {
            background-color: #007bff;
            color: #fff;
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
            <a href="product.php" class="menu-item active">
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
            <h1 class="page-title">Product Management</h1>
            <div class="user-info">
                <span class="user-name">Admin User</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info">
                <?php
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box fa-lg"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo $total_items; ?></p>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags fa-lg"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?php echo count($categories); ?></p>
                    <p class="stat-label">Categories</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-value">
                        <?php
                        $total_stock = 0;
                        if ($file !== false && $file !== null) {
                            foreach ($file->pastry as $pastry) {
                                $total_stock += (int)$pastry->quantity;
                            }
                        }
                        echo $total_stock;
                        ?>
                    </p>
                    <p class="stat-label">Total Stock</p>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Product List</h2>
                <div class="filters-container">
                    <form action="" method="GET" class="search-container">
                    <select name="category" class="filter-dropdown" id="category_filter">
    <option value="All" <?php echo $filter_category === 'All' ? 'selected' : ''; ?>>All Categories</option>
    <?php foreach ($categories as $cat): ?>
        <?php $cat = trim($cat); ?>
        <option value="<?php echo htmlspecialchars($cat); ?>" 
                <?php echo strcasecmp($filter_category, $cat) === 0 ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat); ?>
        </option>
    <?php endforeach; ?>
</select>
                        <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">Search</button>
                        <button type="button" class="add-btn" data-toggle="modal" data-target="#addProductModal">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <button type="button" class="add-category-btn" data-toggle="modal" data-target="#addCategoryModal">
                            <i class="fas fa-folder-plus"></i> Add Category
                        </button>
                    </form>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Tag</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($paginated_pastries) > 0): ?>
                        <?php foreach ($paginated_pastries as $pastry): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($pastry->image)): ?>
                                        <img src="uploads/<?php echo basename($pastry->image); ?>" class="product-image" alt="Product Image">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($pastry->name); ?></td>
                                <td><?php echo htmlspecialchars($pastry->description); ?></td>
                                <td><?php echo htmlspecialchars($pastry->producttype); ?></td>
                                <td>₱<?php echo htmlspecialchars($pastry->price); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($pastry->quantity); ?>
                                    <button class="btn btn-sm btn-outline-primary ml-2" data-toggle="modal" data-target="#updateStockModal_<?php echo urlencode($pastry->name); ?>">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($pastry->producttag); ?></td>
                                <td class="action-btns">
                                    <button class="edit-btn" data-toggle="modal" data-target="#editModal_<?php echo urlencode($pastry->name); ?>">Edit</button>
                                    <button class="delete-btn" data-toggle="modal" data-target="#deleteModal_<?php echo urlencode($pastry->name); ?>">Delete</button>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal for each product -->
                            <div class="modal fade" id="editModal_<?php echo urlencode($pastry->name); ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="update_product.php" method="POST" enctype="multipart/form-data">
                                            <div class="modal-body">
                                                <input type="hidden" name="original_name" value="<?php echo htmlspecialchars($pastry->name); ?>">
                                                
                                                <div class="form-group">
                                                    <label for="name">Name:</label>
                                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($pastry->name); ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="description">Description:</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($pastry->description); ?></textarea>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="producttype">Category:</label>
                                                    <select class="form-control" id="producttype" name="producttype" required>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $pastry->producttype == $category ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="price">Price:</label>
                                                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($pastry->price); ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="quantity">Quantity:</label>
                                                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($pastry->quantity); ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="producttag">Tag:</label>
                                                    <input type="text" class="form-control" id="producttag" name="producttag" value="<?php echo htmlspecialchars($pastry->producttag); ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="image">Image:</label>
                                                    <?php if (!empty($pastry->image)): ?>
                                                        <div class="mb-2">
                                                            <img src="uploads/<?php echo basename($pastry->image); ?>" style="max-width: 100px; max-height: 100px;" alt="Product Image">
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="file" class="form-control-file" id="image" name="image">
                                                    <small class="form-text text-muted">Leave empty to keep current image</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Delete Modal for each product -->
                            <div class="modal fade" id="deleteModal_<?php echo urlencode($pastry->name); ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            Are you sure you want to delete <strong><?php echo htmlspecialchars($pastry->name); ?></strong>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <form action="delete_product.php" method="POST">
                                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($pastry->name); ?>">
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Update Stock Modal for each product -->
                            <div class="modal fade" id="updateStockModal_<?php echo urlencode($pastry->name); ?>" tabindex="-1" role="dialog" aria-labelledby="updateStockModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="updateStockModalLabel">Update Stock</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="update_stock.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($pastry->name); ?>">
                                                
                                                <div class="form-group">
                                                    <label>Current Stock: <?php echo htmlspecialchars($pastry->quantity); ?></label>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="stock_action">Action:</label>
                                                    <select class="form-control" id="stock_action" name="stock_action" required>
                                                        <option value="add">Add Stock</option>
                                                        <option value="subtract">Remove Stock</option>
                                                        <option value="set">Set Exact Value</option>
                                                        </select>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="stock_quantity">Quantity:</label>
                                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="1" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Stock</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No products found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>" class="pagination-btn">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>" class="pagination-btn <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>" class="pagination-btn">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="add_name">Name:</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_description">Description:</label>
                            <textarea class="form-control" id="add_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_producttype">Category:</label>
                            <select class="form-control" id="add_producttype" name="producttype" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_price">Price:</label>
                            <input type="number" step="0.01" class="form-control" id="add_price" name="price" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_quantity">Quantity:</label>
                            <input type="number" class="form-control" id="add_quantity" name="quantity" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_producttag">Tag:</label>
                            <input type="text" class="form-control" id="add_producttag" name="producttag" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add_image">Image:</label>
                            <input type="file" class="form-control-file" id="add_image" name="image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_category">Category Name:</label>
                            <input type="text" class="form-control" id="new_category" name="new_category" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>