<?php
/**
 * Reports Page
 * 
 * This page displays reports and analytics:
 * - All loan records with filters
 * - Statistics (most borrowed books, active members)
 * - Export to CSV functionality
 * - Print reports
 */

require_once 'config.php';

// Filter variables
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : '';

// Build WHERE clause
$where_conditions = [];

if (!empty($status_filter)) {
    if ($status_filter == 'overdue') {
        $where_conditions[] = "l.status = 'issued' AND l.due_date < CURDATE()";
    } else {
        $where_conditions[] = "l.status = '$status_filter'";
    }
}

if (!empty($date_from)) {
    $where_conditions[] = "l.issue_date >= '$date_from'";
}

if (!empty($date_to)) {
    $where_conditions[] = "l.issue_date <= '$date_to'";
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Fetch loan records
$loans_sql = "SELECT l.*, m.full_name, m.email, b.title, b.author,
              DATEDIFF(COALESCE(l.return_date, CURDATE()), l.due_date) as days_diff
              FROM loans l
              JOIN members m ON l.member_id = m.member_id
              JOIN books b ON l.book_id = b.book_id
              $where_clause
              ORDER BY l.created_at DESC";
$loans_result = $conn->query($loans_sql);

// Statistics
$stats = [
    'total_loans' => 0,
    'total_fines' => 0,
    'most_borrowed_book' => 'N/A',
    'most_active_member' => 'N/A'
];

// Total loans and fines
$stats_sql = "SELECT COUNT(*) as total_loans, SUM(fine_amount) as total_fines FROM loans";
$stats_result = $conn->query($stats_sql);
if ($row = $stats_result->fetch_assoc()) {
    $stats['total_loans'] = $row['total_loans'];
    $stats['total_fines'] = $row['total_fines'] ?? 0;
}

// Most borrowed book
$book_sql = "SELECT b.title, COUNT(*) as borrow_count 
             FROM loans l
             JOIN books b ON l.book_id = b.book_id
             GROUP BY l.book_id
             ORDER BY borrow_count DESC
             LIMIT 1";
$book_result = $conn->query($book_sql);
if ($book_row = $book_result->fetch_assoc()) {
    $stats['most_borrowed_book'] = $book_row['title'] . " (" . $book_row['borrow_count'] . " times)";
}

// Most active member
$member_sql = "SELECT m.full_name, COUNT(*) as loan_count 
               FROM loans l
               JOIN members m ON l.member_id = m.member_id
               GROUP BY l.member_id
               ORDER BY loan_count DESC
               LIMIT 1";
$member_result = $conn->query($member_sql);
if ($member_row = $member_result->fetch_assoc()) {
    $stats['most_active_member'] = $member_row['full_name'] . " (" . $member_row['loan_count'] . " loans)";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none; }
            .navbar { display: none; }
            footer { display: none; }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
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
                        <a class="nav-link" href="index.php">
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
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
            <div>
                <button onclick="exportToCSV()" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6>Total Loans</h6>
                        <h2><?php echo $stats['total_loans']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6>Total Fines Collected</h6>
                        <h2>$<?php echo number_format($stats['total_fines'], 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6>Most Borrowed Book</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($stats['most_borrowed_book']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6>Most Active Member</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($stats['most_active_member']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4 no-print">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="reports.php" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="issued" <?php echo $status_filter == 'issued' ? 'selected' : ''; ?>>Issued</option>
                            <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                            <option value="overdue" <?php echo $status_filter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                    </div>
                </form>
                <?php if (!empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                    <div class="mt-2">
                        <a href="reports.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Loan Records Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Loan Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="reportTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Book</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Fine</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($loans_result->num_rows > 0) {
                                while ($row = $loans_result->fetch_assoc()) {
                                    $status_badge = '';
                                    if ($row['status'] == 'returned') {
                                        $status_badge = '<span class="badge bg-success">Returned</span>';
                                    } elseif ($row['days_diff'] > 0 && $row['status'] == 'issued') {
                                        $status_badge = '<span class="badge bg-danger">Overdue</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-warning">Issued</span>';
                                    }
                                    
                                    $return_date = $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '-';
                                    
                                    echo "<tr>
                                            <td>{$row['loan_id']}</td>
                                            <td>" . htmlspecialchars($row['full_name']) . "</td>
                                            <td>" . htmlspecialchars($row['title']) . "</td>
                                            <td>" . date('M d, Y', strtotime($row['issue_date'])) . "</td>
                                            <td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>
                                            <td>$return_date</td>
                                            <td>$" . number_format($row['fine_amount'], 2) . "</td>
                                            <td>$status_badge</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5 no-print">
        <p class="mb-0">&copy; 2025 Library Management System. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            let table = document.getElementById('reportTable');
            let rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/\s+/g, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                
                csv.push(row.join(','));
            }
            
            let csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            let downloadLink = document.createElement('a');
            downloadLink.download = 'library_report_' + new Date().toISOString().split('T')[0] + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
