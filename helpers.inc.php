<?php
/**
 * gDesk - Helpdesk Ticketing Software
 * @author Gökhan Kaya <0x90kh4n@gmail.com>
 */

defined('gDesk') or die();

// XSS filter
function xss_clean($data) {
    if (is_array($data)) {
        return array_map('xss_clean', $data);
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);

    return $data;
}

// Database connection
function db_connection() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
            DB_USER,
            DB_PASS
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        exit('Error: ' . $e->getMessage());
    }

    return $pdo;
}

// Load module
function load_module($module = null) {
    $module = $module ?: DEFAULT_MODULE;

    $allowedModules = str_replace(
        ['modules/', '.php'], '', glob('modules/*/*.php')
    );

    if (!in_array($module, $allowedModules)) {
        show_404();
    }

    $module = 'modules/' . $module . '.php';

    if (!file_exists($module)) {
        show_404();
    }

    include $module;
}

// 404 error page
function show_404() {
    exit(http_response_code(404));
}

// Csrf token
function csrf_token($key = 'csrf_token') {
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

// Csrf protection
function csrf_protection($key = 'csrf_token') {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        return false;
    }

    if (empty($_POST[$key]) || empty($_SESSION[$key]) ||
        $_POST[$key] !== $_SESSION[$key]
    ) {
        exit(http_response_code(403));
    }
}

function time_ago($date) {
    $time = time() - strtotime($date);
    $time = ($time < 1) ? 1 : $time;

    $tokens = array(
        31536000 => 'yıl',
        2592000 => 'ay',
        604800 => 'hafta',
        86400 => 'gün',
        3600 => 'saat',
        60 => 'dakika',
        1 => 'saniye'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit)
            continue;

        $numberOfUnits = floor($time / $unit);
        break;
    }

    return "{$numberOfUnits} {$text} önce";
}

function number_shorten($number, $precision = 1) {
    $divisors = array(
        pow(1000, 0) => '', // 1000^0 == 1
        pow(1000, 1) => 'K', // Thousand
        pow(1000, 2) => 'M', // Million
        pow(1000, 3) => 'B', // Billion
        pow(1000, 4) => 'T', // Trillion
    );

    foreach ($divisors as $divisor => $shorthand) {
        if (abs($number) < ($divisor * 1000)) {
            break;
        }
    }

    $number = number_format($number / $divisor, $precision) . $shorthand;

    if ($precision > 0) {
        $dotzero = '.' . str_repeat('0', $precision);
        $number = str_replace($dotzero, '', $number);
    }

    return $number;
}

function validate_recaptcha() {
    if (empty($_POST['g-recaptcha-response'])) {
        return false;
    }

    $url = sprintf('https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s&remoteip=%s',
        RECAPTCHA_SECRET_KEY,
        $_POST['g-recaptcha-response'],
        $_SERVER['REMOTE_ADDR']
    );

    $context = stream_context_create([
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    ]);

    $verify = file_get_contents($url, false, $context);
    $verify = json_decode($verify);

    return $verify->success;
}

function curl_http_request($url, array $post = []) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // curl_setopt($ch, CURLOPT_USERAGENT, 'gDesk');
    // curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com');

    // curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    // curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    // curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($http_code !== 200) {
        return false;
    }

    return $response;
}

function image_resize($file, int $width, int $height, $crop = false, $save = null, $quality = 75) {
    if (!is_file($file)) {
        return false;
    }

    if (($info = getimagesize($file)) === false) {
        return false;
    }

    list($image_width, $image_height, $image_type) = $info;

    if ($image_type == IMAGETYPE_JPEG) {
        $image = imagecreatefromjpeg($file);
    } elseif ($image_type == IMAGETYPE_PNG) {
        $image = imagecreatefrompng($file);
    } elseif ($image_type == IMAGETYPE_GIF) {
        $image = imagecreatefromgif($file);
    } elseif ($image_type == IMAGETYPE_WEBP) {
        $image = imagecreatefromwebp($file);
    } else {
        return false;
    }

    if ($width <= 0) $width = $image_width;
    if ($height <= 0) $height = $image_height;

    $x = $y = 0;

    if ($crop) {

        $w = $image_height * $width / $height;
        $h = $image_width * $height / $width;

        if ($w > $image_width) {
            $y = ($image_height - $h) / 2;
            $image_height = $h;
        } else {
            $x = ($image_width - $w) / 2;
            $image_width = $w;
        }

    } else {

        $ratio = $image_width / $image_height;

        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

    }

    $image_old = $image;
    $image = imagecreatetruecolor($width, $height);

    // if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_WEBP) {
    //     imagealphablending($image, false);
    //     imagesavealpha($image, true);
    //     $background = imagecolorallocatealpha($image, 255, 255, 255, 127);
    //     imagecolortransparent($image, $background);
    // }

    $background = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);

    imagecopyresampled($image, $image_old, 0, 0, $x, $y, $width, $height, $image_width, $image_height);
    imagedestroy($image_old);

    if ($save) {

        $extension = pathinfo($save, PATHINFO_EXTENSION);
        $extension = strtolower($extension);

        if ($extension == 'jpg' || $extension == 'jpeg') {
            imagejpeg($image, $save, $quality);
        } elseif ($extension == 'png') {
            imagepng($image, $save);
        } elseif ($extension == 'gif') {
            imagegif($image, $save);
        } elseif ($extension == 'webp') {
            imagewebp($image, $save);
        }

    } else {

        header('Content-Type: ' . image_type_to_mime_type($image_type));

        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($image, null, $quality);
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($image);
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($image);
        } elseif ($image_type == IMAGETYPE_WEBP) {
            imagewebp($image);
        }

    }

    imagedestroy($image);
    return true;
}