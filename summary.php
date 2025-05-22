<?php
include 'navbar.php';
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Monthly Summary</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="summary-page">

  <div class="overlay">
    <div class="container mt-5">
      <h2 class="text-center mb-4">Summary</h2>
      <form action="backend/view_summary.php" method="GET" class="mb-4">
        <div class="row">
          <div class="col-md-4">
            <label class="form-label">Month</label>
            <select name="month" class="form-select" required>
              <option value="">Select Month</option>
              <option value="01">January</option>
              <option value="02">February</option>
              <option value="03">March</option>
              <option value="04">April</option>
              <option value="05">May</option>
              <option value="06">June</option>
              <option value="07">July</option>
              <option value="08">August</option>
              <option value="09">September</option>
              <option value="10">October</option>
              <option value="11">November</option>
              <option value="12">December</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" required placeholder="e.g. 2025">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-info">View  Monthly Summary</button>
          </div>
        </div>
      </form>
      <form action="backend/yearly_summary.php" method="GET">
          <div class="row ">
              <div class="col-md-4">
                  <label class="form-label">Year (for yearly summary)</label>
                  <input type="number" name="year" class="form-control" required placeholder="e.g. 2025">
              </div>
              <div class="col-md-4 d-flex align-items-end">
                  <button type="submit" class="btn btn-success">View Yearly Summary</button>
              </div>
          </div>
      </form>

      <div id="summary-results">
        <!-- PHP script will populate remaining items and loss -->
      </div>
    </div>
  </div>
</body>
</html>
