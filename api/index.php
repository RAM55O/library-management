<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    // Include database connection
    require_once 'config.php';
    
    // Fetch dashboard statistics
    $stats = [
        'total_books' => 0,
        'available_books' => 0,
        'total_members' => 0,
        'active_loans' => 0,
        'overdue_books' => 0
    ];
    
    // Count total books
    $result = $conn->query("SELECT SUM(quantity) as total FROM books");
    if ($row = $result->fetch_assoc()) {
        $stats['total_books'] = $row['total'] ?? 0;
    }
    
    // Count available books
    $result = $conn->query("SELECT SUM(available) as available FROM books");
    if ($row = $result->fetch_assoc()) {
        $stats['available_books'] = $row['available'] ?? 0;
    }
    
    // Count total members
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status='active'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_members'] = $row['total'] ?? 0;
    }
    
    // Count active loans
    $result = $conn->query("SELECT COUNT(*) as total FROM loans WHERE status='issued'");
    if ($row = $result->fetch_assoc()) {
        $stats['active_loans'] = $row['total'] ?? 0;
    }
    
    // Count overdue books (due_date < today and status='issued')
    $result = $conn->query("SELECT COUNT(*) as total FROM loans WHERE status='issued' AND due_date < CURDATE()");
    if ($row = $result->fetch_assoc()) {
        $stats['overdue_books'] = $row['total'] ?? 0;
    }
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> Library Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="books.php">
                            <i class="fas fa-book"></i> Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="members.php">
                            <i class="fas fa-users"></i> Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="issue.php">
                            <i class="fas fa-hand-holding"></i> Issue Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="return.php">
                            <i class="fas fa-undo"></i> Return Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h2>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Books Card -->
            <div class="col-md-4">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-uppercase">Total Books</h6>
                                <h2 class="mb-0"><?php echo $stats['total_books']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-book fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="books.php" class="text-white text-decoration-none">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Available Books Card -->
            <div class="col-md-4">
                <div class="card text-white bg-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-uppercase">Available Books</h6>
                                <h2 class="mb-0"><?php echo $stats['available_books']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="books.php" class="text-white text-decoration-none">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Total Members Card -->
            <div class="col-md-4">
                <div class="card text-white bg-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-uppercase">Active Members</h6>
                                <h2 class="mb-0"><?php echo $stats['total_members']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="members.php" class="text-white text-decoration-none">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Active Loans Card -->
            <div class="col-md-4">
                <div class="card text-white bg-warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-uppercase">Active Loans</h6>
                                <h2 class="mb-0"><?php echo $stats['active_loans']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="reports.php" class="text-white text-decoration-none">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Overdue Books Card -->
            <div class="col-md-4">
                <div class="card text-white bg-danger h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-uppercase">Overdue Books</h6>
                                <h2 class="mb-0"><?php echo $stats['overdue_books']; ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="reports.php" class="text-white text-decoration-none">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="col-md-4">
                <div class="card text-white bg-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="issue.php" class="btn btn-light btn-sm">
                                <i class="fas fa-hand-holding"></i> Issue Book
                            </a>
                            <a href="return.php" class="btn btn-light btn-sm">
                                <i class="fas fa-undo"></i> Return Book
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Member</th>
                                        <th>Book</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch recent 10 loan activities
                                    $sql = "SELECT l.*, m.full_name, b.title, 
                                            CASE 
                                                WHEN l.return_date IS NOT NULL THEN 'Returned'
                                                WHEN l.due_date < CURDATE() THEN 'Overdue'
                                                ELSE 'Active'
                                            END as activity_status
                                            FROM loans l
                                            JOIN members m ON l.member_id = m.member_id
                                            JOIN books b ON l.book_id = b.book_id
                                            ORDER BY l.created_at DESC
                                            LIMIT 10";
                                    
                                    $result = $conn->query($sql);
                                    
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $status_badge = '';
                                            if ($row['activity_status'] == 'Returned') {
                                                $status_badge = '<span class="badge bg-success">Returned</span>';
                                            } elseif ($row['activity_status'] == 'Overdue') {
                                                $status_badge = '<span class="badge bg-danger">Overdue</span>';
                                            } else {
                                                $status_badge = '<span class="badge bg-warning">Active</span>';
                                            }
                                            
                                            echo "<tr>
                                                    <td>" . date('M d, Y', strtotime($row['issue_date'])) . "</td>
                                                    <td>" . htmlspecialchars($row['full_name']) . "</td>
                                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                                    <td>" . ($row['return_date'] ? 'Book Returned' : 'Book Issued') . "</td>
                                                    <td>$status_badge</td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No recent activity</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Library Management System. All Rights Reserved.</p>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
