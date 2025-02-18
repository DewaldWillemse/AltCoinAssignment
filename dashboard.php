<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Database connection settings
$host = '127.0.0.1';
$dbname = 'crypto_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Retrieve data from the crypto_prices table
$sql = "SELECT * FROM crypto_prices ORDER BY price DESC";
$stmt = $pdo->query($sql);
$cryptoData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the chart (top 5 coins)
$chartLabels = [];
$chartPrices = [];
$limit = min(5, count($cryptoData));
for ($i = 0; $i < $limit; $i++) {
    $chartLabels[] = $cryptoData[$i]['name'];
    $chartPrices[] = $cryptoData[$i]['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crypto Dashboard</title>
  <!-- Bootstrap CSS (via CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js via CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
    }
    .dashboard-header {
      margin-bottom: 20px;
      position: relative;
    }
    .user-label {
      position: absolute;
      top: 0;
      right: 0;
    }
    .chart-container {
      margin-bottom: 30px;
    }
  </style>
</head>
<body>
  <div class="container my-4">
    <div class="dashboard-header text-center">
      <h1>Crypto Dashboard</h1>
      <p class="lead">View the latest cryptocurrency data</p>
      <!-- User label and Logout -->
      <div class="user-label">
        <span class="badge bg-secondary">
          Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?>
        </span>
        <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
      </div>
      <!-- Refresh Data Button -->
      <div class="mt-3">
        <button id="refreshButton" class="btn btn-success">Refresh Data</button>
      </div>
    </div>

    <!-- Chart Section -->
    <div class="chart-container">
      <canvas id="priceChart"></canvas>
    </div>

    <!-- Data Table Section -->
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Coin ID</th>
            <th>Name</th>
            <th>Symbol</th>
            <th>Price (ZAR)</th>
            <th>Market Cap</th>
            <th>24h Volume</th>
            <th>Change 1h (%)</th>
            <th>Change 24h (%)</th>
            <th>Change 7d (%)</th>
            <th>Last Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($cryptoData): ?>
            <?php foreach ($cryptoData as $index => $coin): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($coin['coin_id']); ?></td>
                <td><?php echo htmlspecialchars($coin['name']); ?></td>
                <td><?php echo htmlspecialchars($coin['symbol']); ?></td>
                <td><?php echo number_format($coin['price'], 2); ?></td>
                <td><?php echo number_format($coin['market_cap'], 2); ?></td>
                <td><?php echo number_format($coin['volume_24h'], 2); ?></td>
                <td><?php echo number_format($coin['percent_change_1h'], 2); ?></td>
                <td><?php echo number_format($coin['percent_change_24h'], 2); ?></td>
                <td><?php echo number_format($coin['percent_change_7d'], 2); ?></td>
                <td><?php echo htmlspecialchars($coin['last_updated']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="11" class="text-center">No data available</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- JavaScript to render the chart -->
  <script>
    // Get chart data from PHP
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartPrices = <?php echo json_encode($chartPrices); ?>;

    const ctx = document.getElementById('priceChart').getContext('2d');
    const priceChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Price (ZAR)',
          data: chartPrices,
          backgroundColor: 'rgba(54, 162, 235, 0.5)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: false
          }
        },
        plugins: {
          legend: {
            display: true,
            position: 'top'
          }
        }
      }
    });

    // Refresh Data Button: Call fetch_crypto.php via AJAX and reload dashboard
    document.getElementById('refreshButton').addEventListener('click', function() {
      const btn = this;
      btn.disabled = true;
      btn.innerText = 'Refreshing...';

      fetch('fetch_crypto.php')
        .then(response => response.text())
        .then(data => {
          console.log(data); // Logging the response 
          window.location.reload();
        })
        .catch(error => {
          console.error('Error refreshing data:', error);
          btn.disabled = false;
          btn.innerText = 'Refresh Data';
        });
    });
  </script>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
