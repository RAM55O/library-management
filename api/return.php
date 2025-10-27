<?php
/**
 * Return Book Page
 * 
 * This page handles book return operations:
 * - Display all issued books
 * - Process book returns
 * - Calculate fines for overdue books
 * - Update book availability
 */

require_once 'config.php';

$message = '';
$message_type = '';

// Fine per day (in dollars)
define('FINE_PER_DAY', 1.00);

// RETURN BOOK
if (isset($_POST['return_book'])) {
    $loan_id = (int)$_POST['loan_id'];
    $return_date = clean_input($_POST['return_date']);
    
    if (empty($return_date)) {
        $message = "Return date is required!";
        $message_type = "error";
    } else {
        // Get loan details
        $loan_sql = "SELECT * FROM loans WHERE loan_id = $loan_id";
        $loan_result = $conn->query($loan_sql);
        $loan = $loan_result->fetch_assoc();
        
        // Calculate fine
        $due_date = strtotime($loan['due_date']);
        $return_timestamp = strtotime($return_date);
        $fine_amount = 0;
        
        if ($return_timestamp > $due_date) {
            $days_overdue = ceil(($return_timestamp - $due_date) / 86400);
            $fine_amount = $days_overdue * FINE_PER_DAY;
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update loan record
            $sql_return = "UPDATE loans 
                          SET return_date = '$return_date', 
                              fine_amount = $fine_amount, 
                              status = 'returned' 
                          WHERE loan_id = $loan_id";
            $conn->query($sql_return);
            
            // Update book availability
            $sql_update = "UPDATE books SET available = available + 1 WHERE book_id = {$loan['book_id']}";
            $conn->query($sql_update);
            
            // Commit transaction
            $conn->commit();
            
            if ($fine_amount > 0) {
                $message = "Book returned successfully! Fine charged: $" . number_format($fine_amount, 2);
                $message_type = "warning";
            } else {
                $message = "Book returned successfully!";
                $message_type = "success";
            }
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $message = "Error returning book: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Fetch all issued books
$issued_sql = "SELECT l.*, m.full_name, m.email, b.title, b.author,
               DATEDIFF(CURDATE(), l.due_date) as days_overdue,
               CASE 
                   WHEN CURDATE() > l.due_date THEN DATEDIFF(CURDATE(), l.due_date) * " . FINE_PER_DAY . "
                   ELSE 0
               END as potential_fine
               FROM loans l
               JOIN members m ON l.member_id = m.member_id
               JOIN books b ON l.book_id = b.book_id
               WHERE l.status = 'issued'
               ORDER BY l.due_date ASC";
$issued_result = $conn->query($issued_sql);

// Statistics
$stats = [
    'total_issued' => 0,
    'overdue' => 0,
    'total_fines' => 0
];

$stats_sql = "SELECT 
              COUNT(*) as total_issued,
              SUM(CASE WHEN due_date < CURDATE() THEN 1 ELSE 0 END) as overdue,
              SUM(CASE WHEN CURDATE() > due_date THEN DATEDIFF(CURDATE(), due_date) * " . FINE_PER_DAY . " ELSE 0 END) as total_fines
              FROM loans 
              WHERE status = 'issued'";
$stats_result = $conn->query($stats_sql);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats = $stats_row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
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
                        <a class="nav-link active" href="return.php">
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
        <h2 class="mb-4"><i class="fas fa-undo"></i> Return Book</h2>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <?php echo show_message($message, $message_type); ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6>Total Issued Books</h6>
                        <h2><?php echo $stats['total_issued'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h6>Overdue Books</h6>
                        <h2><?php echo $stats['overdue'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6>Pending Fines</h6>
                        <h2>$<?php echo number_format($stats['total_fines'] ?? 0, 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Issued Books Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Currently Issued Books</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Book</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Fine</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($issued_result->num_rows > 0) {
                                while ($row = $issued_result->fetch_assoc()) {
                                    $row_class = $row['days_overdue'] > 0 ? 'table-danger' : '';
                                    $overdue_text = $row['days_overdue'] > 0 
                                        ? '<span class="badge bg-danger">' . $row['days_overdue'] . ' days</span>' 
                                        : '<span class="badge bg-success">On Time</span>';
                                    
                                    echo "<tr class='$row_class'>
                                            <td>
                                                <strong>" . htmlspecialchars($row['full_name']) . "</strong><br>
                                                <small>" . htmlspecialchars($row['email']) . "</small>
                                            </td>
                                            <td>
                                                <strong>" . htmlspecialchars($row['title']) . "</strong><br>
                                                <small>by " . htmlspecialchars($row['author']) . "</small>
                                            </td>
                                            <td>" . date('M d, Y', strtotime($row['issue_date'])) . "</td>
                                            <td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>
                                            <td>$overdue_text</td>
                                            <td><strong>$" . number_format($row['potential_fine'], 2) . "</strong></td>
                                            <td>
                                                <button class='btn btn-sm btn-success return-btn' 
                                                        data-id='{$row['loan_id']}'
                                                        data-member='" . htmlspecialchars($row['full_name']) . "'
                                                        data-book='" . htmlspecialchars($row['title']) . "'
                                                        data-fine='" . number_format($row['potential_fine'], 2) . "'
                                                        data-bs-toggle='modal' 
                                                        data-bs-target='#returnModal'>
                                                    <i class='fas fa-check'></i> Return
                                                </button>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No issued books</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Book Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="return.php">
                    <input type="hidden" name="loan_id" id="return_loan_id">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-undo"></i> Return Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Member:</strong> <span id="return_member"></span><br>
                            <strong>Book:</strong> <span id="return_book"></span><br>
                            <strong>Estimated Fine:</strong> $<span id="return_fine"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Return Date *</label>
                            <input type="date" name="return_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Fine: $<?php echo FINE_PER_DAY; ?> per day after due date
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="return_book" class="btn btn-success">
                            <i class="fas fa-check"></i> Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">&copy; 2025 Library Management System. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate return modal
        document.querySelectorAll('.return-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('return_loan_id').value = this.dataset.id;
                document.getElementById('return_member').textContent = this.dataset.member;
                document.getElementById('return_book').textContent = this.dataset.book;
                document.getElementById('return_fine').textContent = this.dataset.fine;
            });
        });
    </script>
</body>
</html>
