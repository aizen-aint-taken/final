 <div class="sidebar">
     <div class="logo">
         <img src="../maharlika/logo.jpg" alt="Logo">
         <h3>Library System</h3>
     </div>
     <a href="index.php"><i class="fas fa-home"></i> Home</a>
     <a href="reservations.php"><i class="fas fa-book"></i> Reservations</a>
     <!-- <a href="#"><i class="fas fa-user"></i> Profile</a> -->
     <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt"></i> Logout</a>
 </div>

 <style>
     .sidebar {
         height: 100vh;
         width: 250px;
         position: fixed;
         top: 0;
         left: 0;
         background-color: #2c3e50;
         padding-top: 20px;
         box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
         transition: all 0.3s ease;
     }

     .sidebar:hover {
         box-shadow: 2px 0 15px rgba(0, 0, 0, 0.2);
     }

     .sidebar a {
         padding: 12px 20px;
         text-decoration: none;
         font-size: 16px;
         color: #ecf0f1;
         display: flex;
         align-items: center;
         gap: 10px;
         transition: all 0.3s ease;
     }

     .sidebar a:hover {
         background-color: #34495e;
         color: #3498db;
         transform: translateX(5px);
     }

     .sidebar a i {
         font-size: 18px;
     }

     .sidebar .logo {
         text-align: center;
         padding: 20px;
         margin-bottom: 20px;
         border-bottom: 1px solid #34495e;
     }

     .sidebar .logo img {
         width: 150px;
         height: auto;
         border-radius: 50%;
     }

     .sidebar .logo h3 {
         color: #ecf0f1;
         margin-top: 10px;
         font-size: 20px;
         font-weight: bold;
     }
 </style>