<?php
require('conf/setup.php');
$mysqli = mysqli_connect('p:' . DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
$fontsize = 120;

if ($stmt = $mysqli->prepare("SELECT value,updated FROM settings WHERE name = 'fontsize' LIMIT 1;")) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $fontsize = $row['value'];
    $stmt->close();
}
if (isset($_POST['fontsize'])) {
    $newfontsize = intval($_POST['fontsize']);
    if ($newfontsize > $fontsize && $fontsize < 400) {
        $sql = "UPDATE settings SET value=? WHERE name ='fontsize'";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $newfontsize);
        $stmt->execute();
        $stmt->close();
        $fontsize = $newfontsize;
    }
}

$x = $fontsize;
$total = 400;
$percentage = intval(($x * 100) / $total);
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta name="HandheldFriendly" content="true">
        <title>My Digital Heart</title>
        <script type="text/javascript" src="js/anime.min.js"></script>
        <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>

        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/style.css">
        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/particles.css">
        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/fontawesome.min.css">
        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/solid.min.css">

        <style type="text/css">
            #heart i {
                font-size: <?php echo $fontsize;?>px;
            }
        </style>
    </head>
    <body>
    <div id="wrapper">
        <h2>SPREAD LOVE NOT CORONA!</h2>
        <p>schaltet</p>

        <div id="heart" class="fixed">
            <button href="#" class="press">
                <i class="fas fa-heart"></i>
            </button>
        </div>
        <div class="background fixed"></div>

    </div>
    <div id="progress">
        <progress class="progress-bar" max="400" value="<?php echo $fontsize; ?>"></progress>
    </div>
    <script src="js/particles.js"></script>
    <script src="js/heart.js"></script>
    </body>
    </html>
<?php

mysqli_close($mysqli);
?>