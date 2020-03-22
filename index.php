<?php
require('conf/setup.php');
$mysqli = mysqli_connect('p:' . DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

$unlock_state = false;
$mycfg = array();

//Alle Settings holen
if ($stmt = $mysqli->prepare("SELECT * FROM " . TABLE_SETTINGS)) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $mycfg[$row['name']] = $row;
    }
    $fontsize = $mycfg['fontsize']['value'];
    $stmt->close();
}
//Wir haben den Unlock-Status
if ($mycfg['unlocked']['value'] > 0) {
    $unlock_state = true;
    if ($stmt = $mysqli->prepare("SELECT * FROM " . TABLE_UNLOCKS . " WHERE id =?;")) {
        $stmt->bind_param('i', intval($mycfg['unlocked']['value']));
        $stmt->execute();
        $result = $stmt->get_result();
        $unlocked = $result->fetch_assoc();
        $stmt->close();
    }
    if ($stmt = $mysqli->prepare("SELECT COUNT(DISTINCT(visitor_ip)) as participants FROM " . TABLE_VISITS )) {
        $stmt->execute();
        $result = $stmt->get_result();
        $visitor_row = $result->fetch_assoc();
        $participants = $visitor_row['participants'];
        $stmt->close();
    }

    //Cooldown berechnen
    $time = new DateTime(date("Y/m/d H:i", strtotime($mycfg['unlocked']['updated'])));
    $time->add(new DateInterval('PT' . $mycfg['paused']['value'] . 'M'));
    $time->modify('+ 1 hour');
    $stamp = $time->format('Y/m/d H:i');
    //for JS

    $time->modify('- 1 hour');
    $time_now = new DateTime("now");

    //Cooldown
    if($time_now > $time){
        if ($stmt = $mysqli->prepare("UPDATE ".TABLE_SETTINGS." SET value=0 WHERE name ='unlocked';")) {
            $stmt->execute();
            $stmt->close();
        }
        if ($stmt = $mysqli->prepare("UPDATE ".TABLE_SETTINGS." SET value=? WHERE name ='fontsize';")) {
            $stmt->bind_param('s', $mycfg['min']['value']);
            $stmt->execute();
            $stmt->close();
        }
        if ($stmt = $mysqli->prepare("TRUNCATE TABLE ".TABLE_VISITS)) {
            $stmt->execute();
            $stmt->close();
        }
        $fontsize = $mycfg['min']['value'];
        $unlock_state = false;

    }

} else {
    //Wir haben das Herz.
    $total = $mycfg['max']['value'];
    $percentage = intval(($fontsize * 100) / $total);

    if (isset($_POST['fontsize'])) {
        $newfontsize = intval($_POST['fontsize']);

        if ($newfontsize > $fontsize && $fontsize < $mycfg['max']['value']) {
            //Wir sind noch im Rennen. Das Herz wird größer.
            $sql = "UPDATE " . TABLE_SETTINGS . " SET value=? WHERE name ='fontsize'";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('i', $newfontsize);
            $stmt->execute();
            $stmt->close();
            $fontsize = $newfontsize;

            if ($stmt = $mysqli->prepare("INSERT INTO " . TABLE_VISITS . " (visitor_ip) VALUES (?)")) {
                $stmt->bind_param('s', $_SERVER['REMOTE_ADDR'] );
                $stmt->execute();
                $stmt->close();
            }


        } elseif ($newfontsize > $fontsize && $fontsize >= $mycfg['max']['value']) {

            if (intval($mycfg['unlocked']['value']) > 0) {
                // es hat schon jemand den Unlock aktiviert.
                if ($stmt = $mysqli->prepare("SELECT * FROM " . TABLE_UNLOCKS . " WHERE id =?;")) {
                    $stmt->bind_param('i', intval($mycfg['unlocked']['value']));
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $unlocked = $result->fetch_assoc();
                    $stmt->close();
                }
            } else {
                // wir bestimmen den Unlock, weil es noch keinen gibt.
                if ($stmt = $mysqli->prepare("SELECT * FROM " . TABLE_UNLOCKS . " ORDER BY RAND() LIMIT 1;")) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $unlocked = $result->fetch_assoc();
                    $stmt->close();

                    if ($stmt = $mysqli->prepare("UPDATE ".TABLE_SETTINGS." SET value=? WHERE name ='unlocked';")) {
                        $stmt->bind_param('i', $unlocked['id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $unlock_state = true;

        } else {
            $fontsize = $mycfg['fontsize']['value'];
        }
    }
}
?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta name="HandheldFriendly" content="true">
        <title>My Digital Heart | #stayhealthy #stayhome</title>
        <script type="text/javascript" src="js/anime.min.js"></script>
        <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
        <script type="text/javascript" src="js/jquery.countdown.min.js"></script>

        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/style.css">
        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/particles.css">
        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/fontawesome.min.css">
        <link rel="stylesheet" type="text/css" title="stylesheet" href="css/solid.min.css">
        <link href="https://fonts.googleapis.com/css?family=Roboto:300&display=swap" rel="stylesheet">


        <style type="text/css">
            #heart i {
                font-size: <?php echo $fontsize;?>px;
            }

            <?php
            if($unlock_state){
            ?>
            .background {
                background-image: url('unlocks/<?php echo $unlocked['image'];?>');
                background-size: 300px;
                background-repeat: no-repeat;
            }

            #heart {
                display: none;
                visibility: hidden;
            }

            <?php
            }
            ?>
        </style>
    </head>
    <body>
    <div id="wrapper">
        <h2>SPREAD LOVE NOT CORONA!</h2>
        <?php
        if ($unlock_state) {
            ?>
            <p>Super! Ihr habt <a href="<?php echo $unlocked['link']; ?>"><?php echo $unlocked['name']; ?></a> gefunden!
                Es haben insgesamt <?php echo $participants;?> Personen mitgemacht.
            </p>
            <?php
        }else{
            ?>
            <p>Drückt das Herz, lasst es wachsen und erfahrt spannende Geschichten, die Ihr mit Euren Freunden teilen könnt.</p>
            <?php
        }
        ?>
        <div id="heart" class="fixed">
            <button href="#" class="press">
                <i class="fas fa-heart"></i>
            </button>
        </div>
        <?php
        if ($unlock_state) {
            ?>
            <div class="background unlocked">
                <a href="<?php echo $unlocked['link']; ?>"></a>
            </div>
            <div id="description">
                <p>
                    <?php echo($unlocked['description']); ?>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="background fixed"></div>
            <?php
        }
        ?>
    </div>
    <?php
    if ($unlock_state) {
        ?>
        <div id="countdown">
            <hr/>
            <p>
                In <span class="timer"></span> geht eine neue Runde los!
            </p>
        </div>
        <script type="text/javascript">
            $('#countdown').countdown('<?php echo $stamp; ?>', function (event) {
                $('.timer').text(event.strftime('%M:%S'));

            }).on('finish.countdown', function() {
                    location.reload();
                  });
        </script>
        <?php
    } else {
        ?>
        <div id="progress">
            <progress class="progress-bar" max="<?php echo $mycfg['max']['value']; ?>>" value="<?php echo $fontsize; ?>"></progress>
        </div>
        <?php
    }
    ?>
    <script src="js/particles.js"></script>
    <script src="js/heart.js"></script>
    </body>
    </html>
<?php
mysqli_close($mysqli);
?>