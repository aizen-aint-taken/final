<?php

session_start();
include('../config/conn.php');


include('../includes/header.php');
include('../includes/sidebar.php');
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-left: 250px; padding: 40px;">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <h2 class="mb-4 text-center">DepEd Books — Grades 7 to 12</h2>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-striped text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Grade Level</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Published Date</th>
                            <th>Subject</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Grade 7</td>
                            <td>Mathematics Learner’s Module</td>
                            <td>DepEd</td>
                            <td>DepEd Bureau of Learning Resources</td>
                            <td>2016</td>
                            <td>Mathematics</td>
                            <td>20</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success btn-sm me-2"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Grade 8</td>
                            <td>English Learner’s Module</td>
                            <td>DepEd</td>
                            <td>DepEd Bureau of Learning Resources</td>
                            <td>2016</td>
                            <td>English</td>
                            <td>15</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success btn-sm me-2"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Grade 9</td>
                            <td>Science Learner’s Module</td>
                            <td>DepEd</td>
                            <td>DepEd Bureau of Learning Resources</td>
                            <td>2017</td>
                            <td>Science</td>
                            <td>12</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success btn-sm me-2"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Grade 10</td>
                            <td>Araling Panlipunan Learner’s Module</td>
                            <td>DepEd</td>
                            <td>DepEd Bureau of Learning Resources</td>
                            <td>2017</td>
                            <td>Araling Panlipunan</td>
                            <td>18</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success btn-sm me-2"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Grade 11</td>
                            <td>Oral Communication in Context</td>
                            <td>DepEd</td>
                            <td>DepEd Bureau of Learning Resources</td>
                            <td>2018</td>
                            <td>English</td>
                            <td>10</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success btn-sm me-2"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>Grade 12</td>
                            <td>General Mathematics</td>
                            <td>DepEd</td>
                            <td>DepEd Bureau of Learning Resources</td>
                            <td>2018</td>
                            <td>Mathematics</td>
                            <td>8</td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success btn-sm me-2"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include('../includes/footer.php'); ?>