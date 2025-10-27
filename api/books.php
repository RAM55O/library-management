<?php
/**
 * Books Management Page
 * 
 * This page handles all book-related operations:
 * - View all books in a table
 * - Add new books
 * - Edit existing books
 * - Delete books
 * - Search books by title/author/ISBN
 */

require_once 'config.php';

// Handle form submissions
$message = '';
$message_type = '';

// ADD NEW BOOK
if (isset($_POST['add_book'])) {
    $title = clean_input($_POST['title']);
    $author = clean_input($_POST['author']);
    $isbn = clean_input($_POST['isbn']);
    $category = clean_input($_POST['category']);
    $quantity = (int)$_POST['quantity'];
    
    // Validate inputs
    if (empty($title) || empty($author)) {
        $message = "Title and Author are required!";
        $message_type = "error";
    } else {
        // Check if ISBN already exists
        $check_sql = "SELECT * FROM books WHERE isbn = '$isbn'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $message = "Book with this ISBN already exists!";
            $message_type = "error";
        } else {
            // Insert new book
            $sql = "INSERT INTO books (title, author, isbn, category, quantity, available) 
                    VALUES ('$title', '$author', '$isbn', '$category', $quantity, $quantity)";
            
            if ($conn->query($sql) === TRUE) {
                $message = "Book added successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}

// UPDATE BOOK
if (isset($_POST['update_book'])) {
    $book_id = (int)$_POST['book_id'];
    $title = clean_input($_POST['title']);
    $author = clean_input($_POST['author']);
    $isbn = clean_input($_POST['isbn']);
    $category = clean_input($_POST['category']);
    $quantity = (int)$_POST['quantity'];
    
    // Calculate available books (current available + difference in quantity)
    $sql_current = "SELECT quantity, available FROM books WHERE book_id = $book_id";
    $result_current = $conn->query($sql_current);
    $current = $result_current->fetch_assoc();
    
    $quantity_diff = $quantity - $current['quantity'];
    $new_available = $current['available'] + $quantity_diff;
    
    // Ensure available doesn't go negative
    if ($new_available < 0) {
        $message = "Cannot reduce quantity below issued books!";
        $message_type = "error";
    } else {
        $sql = "UPDATE books SET 
                title = '$title', 
                author = '$author', 
                isbn = '$isbn', 
                category = '$category', 
                quantity = $quantity,
                available = $new_available
                WHERE book_id = $book_id";
        
        if ($conn->query($sql) === TRUE) {
            $message = "Book updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
}

// DELETE BOOK
if (isset($_GET['delete'])) {
    $book_id = (int)$_GET['delete'];
    
    // Check if book has active loans
    $check_sql = "SELECT COUNT(*) as count FROM loans WHERE book_id = $book_id AND status = 'issued'";
    $check_result = $conn->query($check_sql);
    $check = $check_result->fetch_assoc();
    
    if ($check['count'] > 0) {
        $message = "Cannot delete book with active loans!";
        $message_type = "error";
    } else {
        $sql = "DELETE FROM books WHERE book_id = $book_id";
        
        if ($conn->query($sql) === TRUE) {
            $message = "Book deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
    }
}

// SEARCH FUNCTIONALITY
$search = '';
$where_clause = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $where_clause = "WHERE title LIKE '%$search%' OR author LIKE '%$search%' OR isbn LIKE '%$search%' OR category LIKE '%$search%'";
}

// Fetch all books
$sql = "SELECT * FROM books $where_clause ORDER BY book_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Management - Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <a class="nav-link active" href="books.php">
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
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book"></i> Books Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus"></i> Add New Book
            </button>
        </div>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <?php echo show_message($message, $message_type); ?>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="books.php" class="row g-3">
                    <div class="col-md-10">
                        <div class="search-box">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by title, author, ISBN, or category..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
                <?php if (!empty($search)): ?>
                    <div class="mt-2">
                        <a href="books.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Books Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Books</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $status_class = $row['available'] > 0 ? 'text-success' : 'text-danger';
                                    echo "<tr>
                                            <td>{$row['book_id']}</td>
                                            <td><strong>" . htmlspecialchars($row['title']) . "</strong></td>
                                            <td>" . htmlspecialchars($row['author']) . "</td>
                                            <td>" . htmlspecialchars($row['isbn']) . "</td>
                                            <td><span class='badge bg-info'>" . htmlspecialchars($row['category']) . "</span></td>
                                            <td>{$row['quantity']}</td>
                                            <td class='$status_class'><strong>{$row['available']}</strong></td>
                                            <td>
                                                <button class='btn btn-sm btn-warning edit-btn' 
                                                        data-id='{$row['book_id']}'
                                                        data-title='" . htmlspecialchars($row['title']) . "'
                                                        data-author='" . htmlspecialchars($row['author']) . "'
                                                        data-isbn='" . htmlspecialchars($row['isbn']) . "'
                                                        data-category='" . htmlspecialchars($row['category']) . "'
                                                        data-quantity='{$row['quantity']}'
                                                        data-bs-toggle='modal' 
                                                        data-bs-target='#editBookModal'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <a href='books.php?delete={$row['book_id']}' 
                                                   class='btn btn-sm btn-danger' 
                                                   onclick='return confirm(\"Are you sure you want to delete this book?\")'>
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No books found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="books.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author *</label>
                            <input type="text" name="author" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="Fiction">Fiction</option>
                                <option value="Non-Fiction">Non-Fiction</option>
                                <option value="Science">Science</option>
                                <option value="Technology">Technology</option>
                                <option value="Programming">Programming</option>
                                <option value="History">History</option>
                                <option value="Biography">Biography</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_book" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="books.php">
                    <input type="hidden" name="book_id" id="edit_book_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author *</label>
                            <input type="text" name="author" id="edit_author" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" id="edit_isbn" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" id="edit_category" class="form-select">
                                <option value="Fiction">Fiction</option>
                                <option value="Non-Fiction">Non-Fiction</option>
                                <option value="Science">Science</option>
                                <option value="Technology">Technology</option>
                                <option value="Programming">Programming</option>
                                <option value="History">History</option>
                                <option value="Biography">Biography</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_book" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Book
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
        // Populate edit modal with book data
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_book_id').value = this.dataset.id;
                document.getElementById('edit_title').value = this.dataset.title;
                document.getElementById('edit_author').value = this.dataset.author;
                document.getElementById('edit_isbn').value = this.dataset.isbn;
                document.getElementById('edit_category').value = this.dataset.category;
                document.getElementById('edit_quantity').value = this.dataset.quantity;
            });
        });
    </script>
</body>
</html>
