<!-- Add New Pastry Modal -->
<div class="modal fade" id="addnew" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <center><h4 class="modal-title" id="myModalLabel">Add New Pastry</h4></center>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form method="POST" action="add.php" enctype="multipart/form-data">
                        <!-- Name -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Name:</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Description:</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="description" required>
                            </div>
                        </div>

                        <!-- Product Type (Dropdown) -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Product Type:</label>
                            </div>
                            <div class="col-sm-10">
                                <select class="form-control" name="producttype" required>
                                    <option value="Donut">Donut</option>
                                    <option value="Croissant">Croissant</option>
                                    <option value="Cheesecake">Cheesecake</option>
                                </select>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Price:</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="number" class="form-control" name="price" required>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Quantity:</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="number" class="form-control" name="quantity" required>
                            </div>
                        </div>

                        <!-- Product Tag -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Product Tag:</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="producttag" required>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="row form-group">
                            <div class="col-sm-2">
                                <label class="control-label" style="position:relative; top:7px;">Image:</label>
                            </div>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>

            </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
                <button type="submit" name="add" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Save</button>
            </div>
                    </form>
        </div>
    </div>
</div>
