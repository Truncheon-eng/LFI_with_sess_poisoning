<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('allow_url_include', '0');
ini_set('open_basedir', '/app:/tmp');
error_reporting(E_ALL);
?>
<?php
/*sessions are stored in /tmp*/
session_start();
$path_to_lang_file = "./lang/rus.php";
if(isset($_GET["lang"])) {
    $param_value = $_GET['lang'];
    while (strpos($param_value, '../') !== false) {
        $param_value = str_replace('../', '', $param_value);
    }
    if($param_value) {
        if($param_value == "rus") {
            $path_to_lang_file = "./lang/rus.php";
        } else if($param_value == "en") {
            $path_to_lang_file = "./lang/en.php";    
        } else {
            $path_to_lang_file = $param_value;
        }
    }
}
$_SESSION['language'] = $path_to_lang_file;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language Selection</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
    <div class="container">
        <h1>Выберите язык / Choose Language</h1>
        <div class="links">
            <a href="index.php?lang=rus">Русский</a>
            <a href="index.php?lang=en">English</a>
        </div>
        <div class="content">
            <?php
                include $path_to_lang_file;
                echo $text;
            ?>
        </div>
    </div>
</body>
</html>
