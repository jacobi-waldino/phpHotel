<?php
session_start();
include '../includes/validator.php';
include '../includes/fileHandler.php';

$fileHandler = new fileHandler('../data/roomdata.json');

function addNewQuery($query) {
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $request_uri = $_SERVER['REQUEST_URI'];
    $current_url = $scheme . '://' . $host . $request_uri;
    if (strpos($current_url, '?') !== false) {
        $new_url = $current_url . '&' . $query;
    } else {
        $new_url = $current_url . '?' . $query;
    }
    return $new_url;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The PHP Grand Hotel</title>
    <!-- Boostrap Stylesheet downloaded from website. Link: https://getbootstrap.com/docs/5.3/getting-started/download/ - Accessed March 2, 2025. -->
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <?php include_once("../templates/header.php") ?>

    <?php
    if (isset($_SESSION['error_message'])) {
        echo "<script> alert('" . addslashes($_SESSION['error_message']) . "'); </script>";
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="p-4 d-flex justify-content-center align-items-center">
        <form action="" method="get">
            <div class="mb-3 text-center">
                <label for="rooms" class="form-label">
                    <h4 class="text-center">Search for Open Rooms</h4>
                </label>
                <select name="rooms[]" class="form-select text-center pt-4" id="rooms" multiple>
                    <option value="single">Single</option>
                    <option value="double">Double</option>
                    <option value="suite">Suite</option>
                </select>
            </div>
            <div class="d-flex justify-content-center">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Custom Room Display -->
    <?php
    if (isset($_GET['displayRoom'])) {
        displayRoom($_GET['displayRoom'], $fileHandler);
    }
    
    function displayRoom($i, $fileHandler) {
        echo '<div class="container justify-content-center align-items-center text-center mt-3 mb-5">';
        
        $data = $fileHandler->readJson();
        $room = $data[$i];
        
        echo "<h4>Room {$room['num']}</h4>";
        
        $images = [
            'single' => '../images/single.png',
            'double' => '../images/double.jpg',
            'suite' => '../images/suite.jpg'
        ];
        
        $prices = ['single' => 149.98, 'double' => 199.98, 'suite' => 249.98];
        
        $imgPath = $images[$room['type']];
        $roomPrice = $prices[$room['type']];
        $roomType = $room['type'];

        echo "<figure class='figure w-25'>";
        echo "<img src='$imgPath' class='figure-img img-thumbnail'>";
        echo "<figcaption class='figure-caption text-center text-capitalize'>$roomType Room</figcaption>";
        echo "</figure>";
        
        echo "<script>var roomPrice = " . $roomPrice . ";</script>";

        if ($data[$i]["booked"] == 0) {
            echo "<h5 id='room-price'>Price: $". number_format($roomPrice, 2) ." per Night</h5>";
            echo "<h5>Available for Booking</h5>";

            echo "<form method='post' class='mt-3'>";
            echo "<label for='datein' class='form-label'>Select a Check-in Date:</label>";
            echo "<input id='datein' name='datein' type='date' class='form-control mb-3 w-25 mx-auto' placeholder='DD-MM-YYYY' pattern='\d{2}-\d{2}-\d{4}'>";
            echo "<label for='dateout' class='form-label'>Select a Check-Out Date:</label>";
            echo "<input id='dateout' name='dateout' type='date' class='form-control mb-3 w-25 mx-auto' placeholder='DD-MM-YYYY' pattern='\d{2}-\d{2}-\d{4}'>";
            echo "<label for='name' class='form-label'>Booking For:</label>";
            echo "<input id='name' name='name' type='text' class='form-control mb-3 w-25 mx-auto' placeholder='Your Name'>";

            echo "<div class='form-check mb-3 d-flex justify-content-center align-items-center text-center'>";
            echo "<input type='checkbox' class='form-check-input me-2' id='giftbasket' name='giftbasket' value='yes'>";
            echo "<label class='form-check-label' for='giftbasket'>Gift Basket Upon Arrival ($19.99)</label>";
            echo "</div>";

            echo "<button class='btn btn-success' type='submit' name='book'>Book</button>";
            echo "</form>";

            
        } else {
            $name = $data[$i]['name'];
            $checkIn = $data[$i]['checkin'];
            $checkOut = $data[$i]['checkout'];
            $totalPrice = $data[$i]['totalPrice'];

            echo "<h5>Booked by $name</h5>";
            echo "<h6>From $checkIn To $checkOut</h6>";

            echo "<h6>Guest will be billed $$totalPrice</h6>";

            echo "<form method='post' class='mt-3'>";
            echo "<button class='btn btn-danger' type='submit' name='cancel'>Cancel Booking</button>";
            echo "</form>";
        }

        echo '</div>';
    }

    

    if (isset($_POST['cancel'])) {
        $i = $_GET['displayRoom'];
        $fileHandler->updateJson($i, [
            'booked' => 0,
            'name' => '',
            'checkin' => '',
            'checkout' => '',
            'totalPrice' => 0
        ]);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if (isset($_POST['book'])) {
        $i = $_GET['displayRoom'];
        $checkIn = $_POST['datein'];
        $checkOut = $_POST['dateout'];
        $name = $_POST['name'];

        // Data validation
        // - name must be filled out
        // - dates must be selected
        // - check-in date must be before check-out date
        if(!empty(trim($name))) {
            if(!empty($checkIn) && !empty($checkOut)){
                if (validateDate($checkIn, $checkOut)) {
                    $data = $fileHandler->readJson();
                    $roomType = $data[$i]['type'];
                    $prices = ['single' => 149.98, 'double' => 199.98, 'suite' => 249.98];
                    $roomPrice = $prices[$roomType];
                    
                    $daysBooked = (new DateTime($checkIn))->diff(new DateTime($checkOut))->days;
                    $totalPrice = ($roomPrice * $daysBooked) * 1.15;
                    
                    if (isset($_POST['giftbasket']) && $_POST['giftbasket'] === 'yes') {
                        $totalPrice += (19.99 * 1.15);
                    }
                    
                    $fileHandler->updateJson($i, [
                        'booked' => 1,
                        'name' => $name,
                        'checkin' => $checkIn,
                        'checkout' => $checkOut,
                        'totalPrice' => round($totalPrice, 2)
                    ]);
                    
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                } else {
                    $_SESSION['error_message'] = "Check-out dates need to be after check-in dates, otherwise you'll break the space-time continuum.";
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Please select both check-in and check-out dates.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Hotel rooms need to be booked under a name.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
        
    }
    ?>

    <!-- Open Rooms List -->
    <div class="container mt-5 mb-5">
        <table class="table table-striped table-bordered"
        <?php
        if (!isset($_GET['rooms'])) {
            echo 'style="display:none;"';
        };
        ?>>
            <thead class="thead-dark">
                <tr>
                    <th scope="col">Room Number</th>
                    <th scope="col">Room Type</th>
                    <th scope="col">Price</th>
                    <th scope="col">-</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $data = $fileHandler->readJson();

                if (isset($_GET['rooms']) && is_array($_GET['rooms'])) {
                    $selectedOptions = $_GET['rooms'];

                    for ($i = 0; $i < count($data); $i++) {
                        if (in_array($data[$i]["type"], $selectedOptions) && $data[$i]["booked"] == 0) {
                            echo "<tr class='text-center'>";
                            $roomNum = $data[$i]["num"];
                            echo "<td>$roomNum</td>";
                            $roomType = $data[$i]["type"];
                            echo "<td class='text-capitalize'>$roomType</td>";
                            $roomPrice = ($roomType == "single") ? 149 : (($roomType == "double") ? 199 : 249);
                            echo "<td>$$roomPrice</td>";

                            $url = addNewQuery("displayRoom=$i");

                            echo "<td> <a href='$url' class='btn btn-primary'>More Info</a> </td>";
                            echo "</tr>";
                        }
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php include_once("../templates/footer.php") ?>
</body>

</html>