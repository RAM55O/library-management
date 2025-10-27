<?php
/**
 * Issue Book Page
 * 
 * This page handles book issuing operations:
 * - Display available books and active members
 * - Issue books to members
 * - Validate availability before issuing
 * - Update book availability count
 */

require_once 'config.php';

$message = '';
$message_type = '';

// ISSUE BOOK
if (isset($_POST['issue_book'])) {
    $book_id = (int)$_POST['book_id'];
    $member_id = (int)$_POST['member_id'];
    $issue_date = clean_input($_POST['issue_date']);
    $due_date = clean_input($_POST['due_date']);
    
    // Validate inputs
    if (empty($book_id) || empty($member_id) || empty($issue_date) || empty($due_date)) {
        $message = "All fields are required!";
        $message_type = "error";
    } elseif (strtotime($due_date) <= strtotime($issue_date)) {
        $message = "Due date must be after issue date!";
        $message_type = "error";
    } else {
        // Check if member is active
        $check_member = $conn->query("SELECT status FROM members WHERE member_id = $member_id");
        $member = $check_member->fetch_assoc();
        
        if ($member['status'] != 'active') {
            $message = "Cannot issue book to inactive member!";
            $message_type = "error";
        } else {
            // Check book availability
            $check_book = $conn->query("SELECT available FROM books WHERE book_id = $book_id");
            $book = $check_book->fetch_assoc();
            
            if ($book['available'] <= 0) {
                $message = "Book is not available!";
                $message_type = "error";
            } else {
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Insert loan record
                    $sql_loan = "INSERT INTO loans (book_id, member_id, issue_date, due_date, status) 
                                VALUES ($book_id, $member_id, '$issue_date', '$due_date', 'issued')";
                    $conn->query($sql_loan);
                    
                    // Update book availability
                    $sql_update = "UPDATE books SET available = available - 1 WHERE book_id = $book_id";
                    $conn->query($sql_update);
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $message = "Book issued successfully!";
                    $message_type = "success";
                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollback();
                    $message = "Error issuing book: " . $e->getMessage();
                    $message_type = "error";
                }
            }
        }
    }
}

// Fetch available books
$books_sql = "SELECT * FROM books WHERE available > 0 ORDER BY title ASC";
$books_result = $conn->query($books_sql);

// Fetch active members
$members_sql = "SELECT * FROM members WHERE status = 'active' ORDER BY full_name ASC";
$members_result = $conn->query($members_sql);

// Fetch recent issues
$recent_sql = "SELECT l.*, m.full_name, b.title 
               FROM loans l
               JOIN members m ON l.member_id = m.member_id
               JOIN books b ON l.book_id = b.book_id
               WHERE l.status = 'issued'
               ORDER BY l.created_at DESC
               LIMIT 10";
$recent_result = $conn->query($recent_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - Library System</title>
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
                        <a class="nav-link active" href="issue.php">
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
        <h2 class="mb-4"><i class="fas fa-hand-holding"></i> Issue Book</h2>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <?php echo show_message($message, $message_type); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Issue Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Issue New Book</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="issue.php">
                            <div class="mb-3">
                                <label class="form-label">Select Member *</label>
                                <select name="member_id" class="form-select" required>
                                    <option value="">-- Choose Member --</option>
                                    <?php
                                    if ($members_result->num_rows > 0) {
                                        while ($member = $members_result->fetch_assoc()) {
                                            echo "<option value='{$member['member_id']}'>" 
                                                . htmlspecialchars($member['full_name']) 
                                                . " (" . htmlspecialchars($member['email']) . ")</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Select Book *</label>
                                <select name="book_id" class="form-select" required>
                                    <option value="">-- Choose Book --</option>
                                    <?php
                                    if ($books_result->num_rows > 0) {
                                        while ($book = $books_result->fetch_assoc()) {
                                            echo "<option value='{$book['book_id']}'>" 
                                                . htmlspecialchars($book['title']) 
                                                . " by " . htmlspecialchars($book['author']) 
                                                . " (Available: {$book['available']})</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Issue Date *</label>
                                <input type="date" name="issue_date" id="issue_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Due Date *</label>
                                <input type="date" name="due_date" id="due_date" class="form-control" 
                                       value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Default loan period is 14 days. Fine: $1/day after due date.
                            </div>

                            <button type="submit" name="issue_book" class="btn btn-primary w-100">
                                <i class="fas fa-check"></i> Issue Book
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Issues -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Issues</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Book</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($recent_result->num_rows > 0) {
                                        while ($row = $recent_result->fetch_assoc()) {
                                            $due_date = strtotime($row['due_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $days_left = ($due_date - $today) / 86400;
                                            
                                            $badge_class = 'bg-success';
                                            if ($days_left < 0) {
                                                $badge_class = 'bg-danger';
                                            } elseif ($days_left <= 3) {
                                                $badge_class = 'bg-warning';
                                            }
                                            
                                            echo "<tr>
                                                    <td>" . htmlspecialchars($row['full_name']) . "</td>
                                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                                    <td><span class='badge $badge_class'>" 
                                                        . date('M d', strtotime($row['due_date'])) 
                                                        . "</span></td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center'>No recent issues</td></tr>";
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate due date (14 days from issue date)
        document.getElementById('issue_date').addEventListener('change', function() {
            let issueDate = new Date(this.value);
            let dueDate = new Date(issueDate);
            dueDate.setDate(dueDate.getDate() + 14);
            
            let year = dueDate.getFullYear();
            let month = String(dueDate.getMonth() + 1).padStart(2, '0');
            let day = String(dueDate.getDate()).padStart(2, '0');
            
            document.getElementById('due_date').value = year + '-' + month + '-' + day;
        });
    </script>
</body>
</html>
