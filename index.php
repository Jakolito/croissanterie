<?php
session_start();
$xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
$file = file_exists($xmlPath) ? simplexml_load_file($xmlPath) : null;

// Collect unique categories
$categories = [];
if ($file !== false && $file !== null) {
    foreach ($file->pastry as $row) {
        $cat = (string) $row->producttype;
        if (!in_array($cat, $categories)) {
            $categories[] = $cat;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pastry Inventory</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 30px;
        }
        h2 {
            margin-bottom: 30px;
        }
        .table-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }
        img.thumb {
            width: 80px;
            height: auto;
            border-radius: 6px;
        }
        .table thead th {
            background-color: #343a40;
            color: #fff;
            text-align: center;
            vertical-align: middle;
        }
        .table td, .table th {
            vertical-align: middle !important;
            text-align: center;
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .form-group {
            max-width: 300px;
        }
        .btn {
            border-radius: 5px;
        }
        .alert {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Pastry Inventory</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info text-center">
            <?php
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Category Filter Dropdown -->
    <div class="form-group">
        <label for="categoryFilter">Filter by Category:</label>
        <select id="categoryFilter" class="form-control">
            <option value="All">All</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Pastry Table -->
    <div class="table-container">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Tag</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($file): ?>
                    <?php foreach ($file->pastry as $row): ?>
                        <tr class="pastry-item" data-category="<?php echo htmlspecialchars($row->producttype); ?>">
                            <td>
                                <?php if (!empty($row->image)): ?>
                                    <img src="uploads/<?php echo basename($row->image); ?>" class="thumb" alt="Image">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row->name); ?></td>
                            <td><?php echo htmlspecialchars($row->description); ?></td>
                            <td><?php echo htmlspecialchars($row->producttype); ?></td>
                            <td><?php echo htmlspecialchars($row->price); ?></td>
                            <td><?php echo htmlspecialchars($row->quantity); ?></td>
                            <td><?php echo htmlspecialchars($row->producttag); ?></td>
                            <td>
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#edit_<?php echo urlencode($row->name); ?>">Edit</button>
                                <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#delete_<?php echo urlencode($row->name); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No pastries found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Button (triggers modal) -->
    <button class="btn btn-primary" data-toggle="modal" data-target="#addModal">Add New Pastry</button>
</div>

<!-- Include Edit/Delete Modals -->
<?php include 'edit_delete_modal.php'; ?>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="add.php" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="addModalLabel">Add New Pastry</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <input type="text" class="form-control" name="producttype" required>
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="text" class="form-control" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" class="form-control" name="quantity" required>
                    </div>
                    <div class="form-group">
                        <label>Tag:</label>
                        <input type="text" class="form-control" name="producttag" required>
                    </div>
                    <div class="form-group">
                        <label>Image:</label>
                        <input type="file" class="form-control" name="image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-primary">Add</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
    $('#categoryFilter').on('change', function() {
        var selected = $(this).val();
        $('.pastry-item').each(function() {
            var cat = $(this).data('category');
            if (selected === 'All' || cat === selected) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
</script>

</body>
</html>
