<?php
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

<!-- Category Dropdown -->
<div class="container">
    <div class="form-group">
        <label for="categoryDropdown">Categories:</label>
        <select class="form-control" id="categoryDropdown">
            <?php foreach ($categories as $category): ?>
                <option><?php echo htmlspecialchars($category); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Edit Modal -->
<?php foreach ($file->pastry as $row): ?>
<div class="modal fade" id="edit_<?php echo urlencode($row->name); ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <center><h4 class="modal-title">Edit Pastry</h4></center>
            </div>
            <div class="modal-body">
                <form method="POST" action="edit.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row->name); ?>">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row->name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <input type="text" class="form-control" name="description" value="<?php echo htmlspecialchars($row->description); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <select class="form-control" name="producttype" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($row->producttype == $cat) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price:</label>
                        <input type="text" class="form-control" name="price" value="<?php echo htmlspecialchars($row->price); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" class="form-control" name="quantity" value="<?php echo htmlspecialchars($row->quantity); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tag:</label>
                        <input type="text" class="form-control" name="producttag" value="<?php echo htmlspecialchars($row->producttag); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Image:</label><br>
                        <?php if (!empty($row->image)): ?>
                            <img src="uploads/<?php echo basename($row->image); ?>" width="100" alt="Pastry Image"><br><br>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Delete Modal -->
<?php foreach ($file->pastry as $row): ?>
<div class="modal fade" id="delete_<?php echo urlencode($row->name); ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <center><h4 class="modal-title">Delete Pastry</h4></center>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($row->name); ?></strong>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <a href="delete.php?id=<?php echo urlencode($row->name); ?>" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
