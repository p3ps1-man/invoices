<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices</title>
</head>

<body>
    <h1>Generate invoice</h1>

    <form action="./action.php" method="POST">
        <input type="text" name="hours" placeholder="Hours" required>
        <input type="number" name="rate" placeholder="Rate">
        <button type="submit">Generate Invoice</button>
    </form>
</body>

</html>