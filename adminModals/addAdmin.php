 <div class="modal fade" id="addAdminModal" tabindex="-1">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title">Add Admin</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
             </div>
             <div class="modal-body">
                 <form action="" method="POST">
                     <div class="form-group mb-3">
                         <label for="addName">Full Name</label>
                         <input type="text" class="form-control" name="name" id="addName" required>
                     </div>
                     <div class="form-group mb-3">
                         <label for="addEmail">Email</label>
                         <input type="email" class="form-control" name="email" id="addEmail" required>
                     </div>
                     <div class="form-group mb-3">
                         <label for="addRole">Role</label>
                         <select class="form-control" name="role" id="addRole" required>
                             <option value="a">Admin</option>
                             <?php if (checkSuperAdminExists($conn)): ?>
                                 <option value="sa">Super Admin</option>
                             <?php endif; ?>
                         </select>
                     </div>
                     <div class="form-group mb-3">
                         <label for="password">Password</label>
                         <input type="password" class="form-control" name="password" id="password" required>
                     </div>
                     <button type="submit" name="addAdmin" class="btn btn-success w-100">Add Admin</button>
                 </form>
             </div>
         </div>
     </div>
 </div>