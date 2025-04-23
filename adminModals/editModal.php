    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="form-group mb-3">
                            <label for="editName">Full Name</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editEmail">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editRole">Role</label>
                            <select class="form-control" name="role" id="editRole" required>
                                <option value="a">Admin</option>
                                <option value="sa">Super Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="updateAdmin" class="btn btn-primary w-100">Update Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>