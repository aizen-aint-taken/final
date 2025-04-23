 <div class="modal fade" id="deleteAdminModal" tabindex="-1">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header bg-danger text-white">
                 <h5 class="modal-title">Confirm Deletion</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
             </div>
             <div class="modal-body">
                 <p>Are you sure you want to delete this admin account? This action cannot be undone.</p>
                 <div class="admin-info mb-3">
                     <p><strong>Name:</strong> <span id="deleteAdminName"></span></p>
                     <p><strong>Email:</strong> <span id="deleteAdminEmail"></span></p>
                 </div>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                 <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
             </div>
         </div>
     </div>
 </div>