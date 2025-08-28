<?php
// Simple Guestbook with SQLite

// Debug mode flag - Set til true for udvikling, false for produktion
$debug_mode = false;

// Database konfiguration
$db_file = '../guestbook.sqlite';

// Start session for både cookie og CSRF håndtering
session_start();

// Opret forbindelse til SQLite databasen
try {
    $pdo = new PDO("sqlite:{$db_file}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Opret tabel hvis den ikke eksisterer (inkl. nye kolonner)
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            best TEXT,
            childhood TEXT,
            wish TEXT,
            food TEXT,
            cool TEXT,
            website TEXT,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
} catch (PDOException $e) {
    // Fejl ved databaseforbindelse
    if ($debug_mode) {
        die("Database fejl: {$e->getMessage()}");
    }
    error_log("Kritisk database fejl: {$e->getMessage()}");
    die('Database forbindelse fejl. Prøv igen senere.');
}

// Håndterer formular submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF beskyttelse
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error = 'Ugyldig formular indsendelse';
    } else {
        // Hent og rens input fra formularen
        $name      = trim($_POST['name'] ?? '');
        $best      = trim($_POST['best'] ?? '');
        $childhood = trim($_POST['childhood'] ?? '');
        $wish      = trim($_POST['wish'] ?? '');
        $food      = trim($_POST['food'] ?? '');
        $cool      = trim($_POST['cool'] ?? '');
        $website   = trim($_POST['website'] ?? '');
        $message   = trim($_POST['message'] ?? '');

        // Simpel validering
        if (empty($name) || empty($message)) {
            $error = 'Udfyld venligst både navn og besked.';
        } elseif (strlen($name) > 100) {
            $error = 'Navnet er for langt (max 100 tegn).';
        } elseif (strlen($message) > 500) {
            $error = 'Beskeden er for lang (max 500 tegn).';
        } elseif (!empty($website)) {
            // Valider website URL hvis udfyldt
            if (strlen($website) > 200) {
                $error = 'Website URL er for lang (max 200 tegn).';
            } elseif (!filter_var($website, FILTER_VALIDATE_URL)) {
                $error = 'Indtast en gyldig website URL eller lad feltet være tomt.';
            }
        }

        // Hvis validering er godkendt, indsæt i databasen
        if (!isset($error)) {
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO entries
                        (name, best, childhood, wish, food, cool, website, message)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$name, $best, $childhood, $wish, $food, $cool, $website, $message]);

                // Gem navn og website i cookies i 30 dage
                $expiry = time() + (30 * 24 * 60 * 60); // 30 dage
                $cookie_options = [
                    'expires' => $expiry,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => !$debug_mode,
                    'samesite' => $debug_mode ? 'Lax' : 'Strict'
                ];
                setcookie('guestbook_name', $name, $cookie_options);
                if (!empty($website)) {
                    setcookie('guestbook_website', $website, $cookie_options);
                }

                // Redirect for at forhindre gensendelse af formen
                header("Location: {$_SERVER['PHP_SELF']}");
                exit;
            } catch (PDOException $e) {
                // Database fejl under indsættelse
                if ($debug_mode) {
                    $error = "Database fejl: {$e->getMessage()}";
                } else {
                    $error = 'Fejl ved gemning af indlæg.';
                    error_log("Guestbook fejl: {$e->getMessage()}");
                }
            }
        }
    }
}

// Generér ny CSRF token hvis nødvendigt
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Hent alle indlæg fra databasen
try {
    $stmt = $pdo->query('SELECT * FROM entries ORDER BY created_at DESC');
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $entries = [];
    if ($debug_mode) {
        $error = "Database fejl ved indlæsning: {$e->getMessage()}";
    } else {
        $error = 'Fejl ved indlæsning af indlæg.';
        error_log("Guestbook fejl: {$e->getMessage()}");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <script src="cursor-effect.js" type="module"></script>
    <script src="js.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poiret+One&display=swap" rel="stylesheet">
    <title>My Guestbook</title>
</head>
<body>
    <div class="container">
        <marquee behavior="alternate" scrollamount="3">
            <h1>My Guestbook</h1>
        </marquee>
        <div class="welcome-message">
            <span style="color:#ff6699;">★</span>
            Welcome to my <blink>AWESOME</blink> Homepage!
            <span style="color:#ff6699;">★</span>
            <br>
            <span style="color:#6666cc; font-size:11px;">
                You are visitor #<?php echo random_int(10000, 99999); ?> since 03/14/2000
            </span>
            <br>
            <div style="margin-top:8px; font-size:13px;">
                This site is best viewed in Netscape Navigator 4.0 or Internet Explorer 5.0 at 800x600 resolution.
                <br>
                Please sign my guestbook to let me know you stopped by! <b>No</b> spam please! ^_^
            </div>
        </div>

        <div class="toggle-button-container">
            <button id="toggleFormButton" class="toggle-button" onclick="toggleGuestbookForm()">
                Click here to sign my guestbook!
            </button>
        </div>

        <div class="guestbook-form-container" id="guestbookForm" style="display: none;">
            <div class="close-button" id="closeFormButton" onclick="toggleGuestbookForm()">
                &times;
            </div>
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="name">Navn:</label>
                    <input type="text" id="name" name="name" aria-required="true" maxlength="100" value="<?php echo isset($_COOKIE['guestbook_name']) ? htmlspecialchars($_COOKIE['guestbook_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="best">Det er jeg bedst til:</label>
                    <input type="text" id="best" name="best" aria-required="true" maxlength="100" value="<?php echo isset($_COOKIE['guestbook_name']) ? htmlspecialchars($_COOKIE['guestbook_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="childhood">Hvad ville jeg være som barn:</label>
                    <input type="text" id="childhood" name="childhood" aria-required="true" maxlength="100" value="<?php echo isset($_COOKIE['guestbook_name']) ? htmlspecialchars($_COOKIE['guestbook_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="wish">Mit største ønske er:</label>
                    <input type="text" id="wish" name="wish" aria-required="true" maxlength="100" value="<?php echo isset($_COOKIE['guestbook_name']) ? htmlspecialchars($_COOKIE['guestbook_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="food">Min livret:</label>
                    <input type="text" id="food" name="food" aria-required="true" maxlength="100" value="<?php echo isset($_COOKIE['guestbook_name']) ? htmlspecialchars($_COOKIE['guestbook_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="cool">Det syntes jeg er sejt: </label>
                    <input type="text" id="cool" name="cool" aria-required="true" maxlength="100" value="<?php echo isset($_COOKIE['guestbook_name']) ? htmlspecialchars($_COOKIE['guestbook_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="message">Skriv en besked til du holder af:</label>
                    <textarea id="message" name="message" aria-required="true" maxlength="500" placeholder="Leave your message here..."></textarea>
                </div>

                <button type="submit">Send</button>
            </form>
        </div>

        <div class="entries">
            <h2><?php echo count($entries); ?> Entries</h2>

            <?php if (empty($entries)): ?>
                <div class="no-entries">
                    No entries yet. Be the first to sign my guestbook!
                </div>
            <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                    <div class="entry">
                        <div class="entry-header">
                            <?php echo htmlspecialchars($entry['name']); ?>
                            <?php if (!empty($entry['website'])): ?>
                                -
                                <a href="<?php echo htmlspecialchars($entry['website']); ?>" target="_blank" rel="noopener">
                                    Website
                                </a>
                            <?php endif; ?>
                            <span class="entry-date">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($entry['created_at'])); ?>
                            </span>
                        </div>
                        <div class="entry-message">
                            <?php echo nl2br(htmlspecialchars($entry['message'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <div style="margin:15px 0; font-size:11px;">
                <div style="margin-bottom:10px;">
                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwAgMAAAAqbBEUAAAACVBMVEUAAAD///8AAABzxoNxAAAAAnRSTlMAAHaTzTgAAAAtSURBVHicY2DAD1SxwADDqAIsQIZqFAwahoZGDShoZGRktMGgkZFxowZuAAAoXwEg9KnZcAAAAABJRU5ErkJggg==" alt="under construction" class="construction-image construction-image-left">
                    <span class="construction-text">
                        <blink>UNDER CONSTRUCTION</blink> - Please excuse our dust!
                    </span>
                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwAgMAAAAqbBEUAAAACVBMVEUAAAD///8AAABzxoNxAAAAAnRSTlMAAHaTzTgAAAAtSURBVHicY2DAD1SxwADDqAIsQIZqFAwahoZGDShoZGRktMGgkZFxowZuAAAoXwEg9KnZcAAAAABJRU5ErkJggg==" alt="under construction" class="construction-image construction-image-right">
                </div>
                <div style="margin:10px 0; color:#666699;">
                    Made with <span style="color:#ff0000;">&hearts;</span> on a Pentium III using Notepad
                </div>
                <div style="margin-top:10px;">
                    <a href="#" onclick="alert('Coming soon!');">Home</a> |
                    <a href="#" onclick="alert('My photos will be uploaded when I scan them!');">Photos</a> |
                    <a href="#" onclick="alert('My links page is under construction!');">Cool Links</a> |
                    <a href="#" onclick="alert('You are already here!');">Guestbook</a>
                </div>
                <div style="margin-top:15px; font-size:10px; color:#999;">
                    Copyright &copy; 2000-<?= date('Y') ?> | Last updated: 08/06/2025
                </div>
            </div>
        </div>
    </div>
</body>
</html>
