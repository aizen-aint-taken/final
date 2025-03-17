<footer style="text-align: center; padding: 10px; background-color: #f8f9fa; border-top: 1px solid #dee2e6;" class="main-footer">
  <div>
    <p>&copy; <span id="current-year"></span> Maharlika National High School All rights reserved.</p>
    <nav>
      <a href="/privacy-policy" style="margin-right: 15px;">Privacy Policy</a>
      <a href="../aboutUs/index.php" style="margin-right: 15px;">About Us</a>
    </nav>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  document.getElementById("current-year").textContent = new Date().getFullYear();
  document.querySelectorAll('.main-footer a').forEach(link => {
    link.addEventListener('click', function(event) {
      // event.preventDefault();
      const targetUrl = event.target.getAttribute('href');
      console.log(`Navigate to: ${targetUrl}`);
    });
  });
</script>
</body>

</html>