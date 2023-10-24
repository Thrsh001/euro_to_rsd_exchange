
<h3>Median exchange rate EUR to RSD</h3>
<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div>
        <button type="submit" name="pullRate">Pull Rate</button>
    </div>
</form>

<?php

// Set your database credentials
$servername = "localhost:3307";
$username = "misto";
$password = "misto";
$database = "exchange_rates";

// Connect to the database
$mysqli = new mysqli($servername, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// URL to scrape
$url = "https://www.google.com/search?q=eur+rsd";

// Function to scrape exchange rate from Google search results
function scrapeExchangeRate($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);   // Entire webpage result
    curl_close($ch);

    // Create a DOMDocument and load the HTML string
    $dom = new DOMDocument;
    $dom->loadHTML($result, LIBXML_NOERROR);

    // Create a DOMXPath object to query the DOMDocument
    $xpath = new DOMXPath($dom);

    // Use XPath to find the second input element
    $query = '//div[@class=\'BNeawe iBp4i AP7Wnd\']';

    $element = $xpath->query($query)->item(0);

    // Get the value of the second input element
    if ($element) {
        $value = $element->nodeValue;
        echo "Value: ";
        
        preg_match('/(\d+[,.]\d+)/', $value, $matches);
        if(isset($matches[0])){
            $matches[0] = str_replace(",", ".", $matches[0]);
        }
        echo $matches[0];
        return floatval($matches[0]);
    } else {
        echo "Search result not found.\n";
    }
    
    return null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pullRate"])) {
    // Get the exchange rate
    $exchangeRate = scrapeExchangeRate($url);
    if ($exchangeRate !== null) {
        // Insert the exchange rate into the database
        $sql = "INSERT INTO euro_to_rsd_rates (rate) VALUES (?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("d", $exchangeRate);
        $stmt->execute();
        $stmt->close();
        echo "Exchange rate saved: $exchangeRate <br>";
    } else {
        echo "Failed to retrieve the exchange rate.";
    }
}
/*

// Define the start and end dates for the time range
// $startDate = "2023-01-01";
// $endDate = "2023-12-31";

*/
// Or input from a html form bellow 
?>

<p>Enter start and end date:</p>
<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <div>
        <label for="startDate">Start date: </label>
        <input id="startDate" type="date" size="50" name="startDate" placeholder="Enter start date" value="<?php echo $startDate; ?>">
    </div>
    <div>
        <label for="endDate">End date: </label>
        <input id="endDate" type="date" size="50" name="endDate" placeholder="Enter end date" value="<?php echo $endDate; ?>">
    </div>
    <div>
        <button type="submit" name="calculate">Calculate</button>
    </div>
</form>
<?php
// Declare start and end date
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["calculate"])) {
    $startDate = $_POST["startDate"];  
    $endDate = $_POST["endDate"]; 
    // Retrieve the median rate for a given time range
    
    // Raw query to be used in MySQL console
    /*
        SET @index := -1;
        SELECT AVG(m.rates) AS median 
        FROM ( 
            SELECT @index := @index + 1 AS i,
                euro_to_rsd_rates.rate AS rates 
            FROM euro_to_rsd_rates 
            WHERE euro_to_rsd_rates.timestamp BETWEEN <start_date> AND <end_date>
            ORDER BY euro_to_rsd_rates.rate 
        ) AS m 
        WHERE m.i IN (FLOOR(@index / 2), CEIL(@index / 2));
    */
    

    $sql = "SELECT AVG(m.rates) AS median 
            FROM ( 
                SELECT @index := ? + 1 AS i,
                euro_to_rsd_rates.rate AS rates 
                FROM euro_to_rsd_rates 
                WHERE euro_to_rsd_rates.timestamp BETWEEN ? AND ?
                ORDER BY euro_to_rsd_rates.rate 
            ) AS m 
            WHERE m.i IN (FLOOR(@index / 2), CEIL(@index / 2));";
    $stmt = $mysqli->prepare($sql);
    $index = -1;
    $stmt->bind_param("iss", $index, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row) {
        $median_rate = $row['median'];
        echo "Median rate for the time range ($startDate to $endDate): $median_rate";
    } else {
        echo "No data found for the specified time range.";
    }

    // Close the database connection
    $mysqli->close();
}

?>
