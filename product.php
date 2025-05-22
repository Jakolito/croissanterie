<?php
include('connect.php');
session_start();

// XML file path
$xmlPath = 'C:\xampp\htdocs\PASTRY1\pastry.xml';
$file = file_exists($xmlPath) ? simplexml_load_file($xmlPath) : null;

// Categories XML file path - we'll store categories separately for better persistence
$categoriesXmlPath = 'C:\xampp\htdocs\PASTRY1\categories.xml';

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
$items_per_page = 6;
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

function updateInventoryOnApproval($transaction_id) {
    // Path to the XML files
    $transactionsXmlPath = 'transactions.xml';
    $pastryXmlPath = 'C:\xampp\htdocs\PASTRY1\pastry.xml';
    
    // Load transactions XML
    if (!file_exists($transactionsXmlPath)) {
        return ['success' => false, 'message' => 'Transactions file not found'];
    }
    
    $transactions = simplexml_load_file($transactionsXmlPath);
    if ($transactions === false) {
        return ['success' => false, 'message' => 'Error loading transactions XML'];
    }
    
    // Find the transaction by ID
    $targetTransaction = null;
    foreach ($transactions->transaction as $transaction) {
        if ((string)$transaction['id'] === $transaction_id) {
            $targetTransaction = $transaction;
            break;
        }
    }
    
    if ($targetTransaction === null) {
        return ['success' => false, 'message' => 'Transaction not found'];
    }
    
    // Check if the transaction has already been processed
    if ((string)$targetTransaction->inventory_updated === 'yes') {
        return ['success' => false, 'message' => 'Inventory already updated for this transaction'];
    }
    
    // Load pastry XML
    if (!file_exists($pastryXmlPath)) {
        return ['success' => false, 'message' => 'Products file not found'];
    }
    
    $pastries = simplexml_load_file($pastryXmlPath);
    if ($pastries === false) {
        return ['success' => false, 'message' => 'Error loading products XML'];
    }
    
    // Process each item in the order
    $updatedItems = [];
    $inventoryUpdated = true;
    
    // Ensure we have items to process
    if (!isset($targetTransaction->items) || !isset($targetTransaction->items->item)) {
        return ['success' => false, 'message' => 'No items found in transaction'];
    }
    
    foreach ($targetTransaction->items->item as $orderItem) {
        $productName = (string)$orderItem->name;
        $orderQuantity = (int)$orderItem->quantity;
        
        // Find the product in pastry.xml
        $productFound = false;
        foreach ($pastries->pastry as $pastry) {
            if ((string)$pastry->name === $productName) {
                $productFound = true;
                $currentStock = (int)$pastry->quantity;
                
                // Check if we have enough stock
                if ($currentStock >= $orderQuantity) {
                    // Update the stock
                    $pastry->quantity = $currentStock - $orderQuantity;
                    $updatedItems[] = [
                        'name' => $productName,
                        'ordered' => $orderQuantity,
                        'remaining' => (int)$pastry->quantity
                    ];
                } else {
                    $inventoryUpdated = false;
                    $updatedItems[] = [
                        'name' => $productName,
                        'ordered' => $orderQuantity,
                        'error' => 'Insufficient stock (available: ' . $currentStock . ')'
                    ];
                }
                break;
            }
        }
        
        if (!$productFound) {
            $inventoryUpdated = false;
            $updatedItems[] = [
                'name' => $productName,
                'ordered' => $orderQuantity,
                'error' => 'Product not found'
            ];
        }
    }
    
    // If all inventory updates were successful, save the XML files
    if ($inventoryUpdated) {
        // Add a flag to mark this transaction as processed
        $targetTransaction->addChild('inventory_updated', 'yes');
        
        // Save both XML files
        $pastries->asXML($pastryXmlPath);
        $transactions->asXML($transactionsXmlPath);
        
        return [
            'success' => true,
            'message' => 'Inventory updated successfully',
            'items' => $updatedItems
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Some items could not be updated',
            'items' => $updatedItems
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Croissanterie </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="product.css">
    
    
</head>
<body>

    <!-- Sidebar -->
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
      <a href="adminorder.php" class="menu-item">
        <i class="fas fa-shopping-cart"></i>
        <span class="menu-text">Orders</span>
      </a>
      <a href="adminTransaction.php" class="menu-item">
        <i class="fas fa-money-bill-wave"></i>
        <span class="menu-text">Transactions</span>
      </a>
      <a href="adminSetting.php" class="menu-item">
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

   <!-- Add Category Modal with Edit Functionality -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Category Management</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Category Add Form -->
                <form action="" method="POST" class="mb-4">
                    <div class="form-group">
                        <label for="new_category">Add New Category:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="new_category" name="new_category" required>
                            <div class="input-group-append">
                                <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Category List Table -->
                <h6 class="mb-3">Existing Categories</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="align-middle">
                                        <span id="category_<?php echo md5($category); ?>"><?php echo htmlspecialchars($category); ?></span>
                                        <form id="edit_form_<?php echo md5($category); ?>" action="edit_category.php" method="POST" class="d-none">
                                            <div class="input-group">
                                                <input type="hidden" name="original_category" value="<?php echo htmlspecialchars($category); ?>">
                                                <input type="text" class="form-control" name="new_category_name" value="<?php echo htmlspecialchars($category); ?>">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit('<?php echo md5($category); ?>')">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center" style="width: 120px;">
                                        <button type="button" class="btn btn-info btn-sm edit-category-btn" onclick="showEditForm('<?php echo md5($category); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteCategoryModal_<?php echo md5($category); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Delete Category Modal -->
                                <div class="modal fade" id="deleteCategoryModal_<?php echo md5($category); ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-sm" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete the category "<strong><?php echo htmlspecialchars($category); ?></strong>"?
                                                <p class="text-danger mt-2">
                                                    <small>Note: This will not delete products in this category but they will need to be reassigned.</small>
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                <form action="delete_category.php" method="POST">
                                                    <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($category); ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Category Edit Functions -->
<script>
    function showEditForm(categoryId) {
        // Hide the category name and show the edit form
        document.getElementById('category_' + categoryId).classList.add('d-none');
        document.getElementById('edit_form_' + categoryId).classList.remove('d-none');
    }
    
    function cancelEdit(categoryId) {
        // Hide the edit form and show the category name
        document.getElementById('edit_form_' + categoryId).classList.add('d-none');
        document.getElementById('category_' + categoryId).classList.remove('d-none');
    }
</script>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>