<?php
session_start();

require_once __DIR__ . '/private/core/bootstrap.php';

$langCode = isset($_GET['lang']) ? $_GET['lang'] : $appConfig['app']['default_lang'];
$langFile = __DIR__ . '/private/lang/' . $langCode . '.php';

if (!file_exists($langFile)) {
    $langCode = 'pt-BR';
    $langFile = __DIR__ . '/private/lang/pt-BR.php';
}

require $langFile;

$appName = isset($appConfig['app']['name']) ? $appConfig['app']['name'] : 'L2J';
$authCfg = isset($appConfig['auth']) ? $appConfig['auth'] : array();

$currentFlag = 'br';

if ($langCode === 'en') {
    $currentFlag = 'us';
} elseif ($langCode === 'es') {
    $currentFlag = 'es';
} elseif ($langCode === 'ru') {
    $currentFlag = 'ru';
} elseif ($langCode === 'pt-BR') {
    $currentFlag = 'br';
}

$message = '';
$messageType = '';
$openModal = '';

function db_fetch_one_assoc($db, $sql, $params = array())
{
    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    if ($db instanceof mysqli) {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        if (!empty($params)) {
            $types = '';
            $bindValues = array();

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
                $bindValues[] = $param;
            }

            $refs = array();
            $refs[] = $types;
            foreach ($bindValues as $k => $v) {
                $refs[] = &$bindValues[$k];
            }

            call_user_func_array(array($stmt, 'bind_param'), $refs);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return null;
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? $row : null;
    }

    return null;
}

function db_execute($db, $sql, $params = array())
{
    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    if ($db instanceof mysqli) {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        if (!empty($params)) {
            $types = '';
            $bindValues = array();

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
                $bindValues[] = $param;
            }

            $refs = array();
            $refs[] = $types;
            foreach ($bindValues as $k => $v) {
                $refs[] = &$bindValues[$k];
            }

            call_user_func_array(array($stmt, 'bind_param'), $refs);
        }

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    return false;
}

function current_lang_query($langCode)
{
    return '?lang=' . urlencode($langCode);
}

if (isset($_GET['logout'])) {
    unset($_SESSION['site_user']);
    header('Location: index.php?lang=' . urlencode($langCode));
    exit;
}

function auth_make_password($plain, $mode)
{
    switch ($mode) {

        case 'md5':
            return md5($plain);

        case 'sha1':
            return sha1($plain);

        case 'sha1_base64':
            return base64_encode(sha1($plain, true));

        case 'plain':
        default:
            return $plain;
    }
}

function auth_verify_password($plain, $stored, $mode)
{
    $generated = auth_make_password($plain, $mode);

    return hash_equals((string)$stored, (string)$generated);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    $accountsTable = isset($authCfg['accounts_table']) ? $authCfg['accounts_table'] : 'accounts';
    $charactersTable = isset($authCfg['characters_table']) ? $authCfg['characters_table'] : 'characters';
    $loginColumn = isset($authCfg['login_column']) ? $authCfg['login_column'] : 'login';
    $passwordColumn = isset($authCfg['password_column']) ? $authCfg['password_column'] : 'password';
    $emailColumn = isset($authCfg['email_column']) ? $authCfg['email_column'] : 'email';
    $charactersAccountColumn = isset($authCfg['characters_account_column']) ? $authCfg['characters_account_column'] : 'account_name';
    $passwordMode = isset($authCfg['password_mode']) ? $authCfg['password_mode'] : 'plain';
    $useEmail = !empty($authCfg['use_email']);
    $defaultAccessLevel = isset($authCfg['default_access_level']) ? (int) $authCfg['default_access_level'] : 0;

    if ($action === 'login') {
        $openModal = 'login';

        $login = isset($_POST['login']) ? trim($_POST['login']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        if ($login === '' || $password === '') {
            $message = lang(4021);
            $messageType = 'error';
        } else {
            $sql = "SELECT * FROM `{$accountsTable}` WHERE `{$loginColumn}` = ? LIMIT 1";
            $account = db_fetch_one_assoc($db, $sql, array($login));

            if (!$account || !isset($account[$passwordColumn]) || !auth_verify_password($password, $account[$passwordColumn], $passwordMode)) {
                $message = lang(4019);
                $messageType = 'error';
            } else {
                $charsCount = 0;
                $sqlChars = "SELECT COUNT(*) AS total FROM `{$charactersTable}` WHERE `{$charactersAccountColumn}` = ?";
                $charRow = db_fetch_one_assoc($db, $sqlChars, array($login));
                if ($charRow && isset($charRow['total'])) {
                    $charsCount = (int) $charRow['total'];
                }

                $_SESSION['site_user'] = array(
                    'login' => $login,
                    'characters_count' => $charsCount
                );

                $message = lang(4018);
                $messageType = 'success';
                $openModal = '';
            }
        }
    }

    if ($action === 'register') {
        $openModal = 'register';

        $login = isset($_POST['reg_login']) ? trim($_POST['reg_login']) : '';
        $email = isset($_POST['reg_email']) ? trim($_POST['reg_email']) : '';
        $password = isset($_POST['reg_password']) ? (string) $_POST['reg_password'] : '';
        $password2 = isset($_POST['reg_password2']) ? (string) $_POST['reg_password2'] : '';

        if ($login === '' || $password === '' || $password2 === '' || ($useEmail && $email === '')) {
            $message = lang(4021);
            $messageType = 'error';
        } elseif (!preg_match('/^[A-Za-z0-9_]{4,16}$/', $login)) {
            $message = lang(4024);
            $messageType = 'error';
        } elseif (strlen($password) < 6 || strlen($password) > 32) {
            $message = lang(4025);
            $messageType = 'error';
        } elseif ($password !== $password2) {
            $message = lang(4020);
            $messageType = 'error';
        } elseif ($useEmail && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = lang(4034);
            $messageType = 'error';
        } else {
            $exists = db_fetch_one_assoc($db, "SELECT `{$loginColumn}` FROM `{$accountsTable}` WHERE `{$loginColumn}` = ? LIMIT 1", array($login));

            if ($exists) {
                $message = lang(4022);
                $messageType = 'error';
            } else {
                if ($useEmail) {
                    $mailExists = db_fetch_one_assoc($db, "SELECT `{$emailColumn}` FROM `{$accountsTable}` WHERE `{$emailColumn}` = ? LIMIT 1", array($email));
                    if ($mailExists) {
                        $message = lang(4023);
                        $messageType = 'error';
                    }
                }

                if ($messageType !== 'error') {
                    $hashedPassword = auth_make_password($password, $passwordMode);

                    if ($useEmail) {
                        $insertSql = "INSERT INTO `{$accountsTable}` (`{$loginColumn}`, `{$passwordColumn}`, `{$emailColumn}`, `access_level`) VALUES (?, ?, ?, ?)";
                        $ok = db_execute($db, $insertSql, array($login, $hashedPassword, $email, $defaultAccessLevel));
                    } else {
                        $insertSql = "INSERT INTO `{$accountsTable}` (`{$loginColumn}`, `{$passwordColumn}`, `access_level`) VALUES (?, ?, ?)";
                        $ok = db_execute($db, $insertSql, array($login, $hashedPassword, $defaultAccessLevel));
                    }

                    if ($ok) {
                        $message = lang(4017);
                        $messageType = 'success';
                        $openModal = 'login';
                    } else {
                        $message = lang(4033);
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

$isLogged = isset($_SESSION['site_user']);
$currentUser = $isLogged ? $_SESSION['site_user'] : null;
$dbOnline = Database::isConnected($db);

$gameServerRow = db_fetch_one_assoc(
	$db,
	"SELECT status, last_heartbeat FROM server_status WHERE server_type = ? LIMIT 1",
	array('GAME')
);

$gameServerOnline = $gameServerRow && strtoupper($gameServerRow['status']) === 'ONLINE';

$gameServerText = $gameServerOnline ? lang(4040) : lang(4041); 
$gameServerClass = $gameServerOnline ? 'server-card-value--online' : 'server-card-value--orange';

$loginServerRow = db_fetch_one_assoc(
	$db,
	"SELECT status, last_heartbeat FROM server_status WHERE server_type = ? LIMIT 1",
	array('LOGIN')
);

$loginServerOnline = $loginServerRow && strtoupper($loginServerRow['status']) === 'ONLINE';

$loginServerText = $loginServerOnline ? lang(4040) : lang(4041);
$loginServerClass = $loginServerOnline ? 'server-card-value--online' : 'server-card-value--orange';

?>
<!DOCTYPE html>
<html lang="<?php echo e($langCode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo lang(4057); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
	<link rel="icon" href="/assets/img/favicon.ico" sizes="any" />
</head>
<body class="is-loading">

<div id="site-loader" class="site-loader">
    <div class="loader-box">
        <div class="loader-ring"></div>
        <div class="loader-logo"><?php echo lang(4057); ?></div>
        <div class="loader-text">Loading world...</div>
    </div>
</div>

<div class="page-shell">
    <header class="topbar">
        <div class="topbar-left">
            <a href="index.php<?php echo current_lang_query($langCode); ?>" class="brand js-loader-link">
                <div class="brand-mark">L2</div>
                <div class="brand-texts">
                    <strong><?php echo lang(4057); ?></strong>
                    <span><?php echo lang(4058); ?></span>
                </div>
            </a>
        </div>

        <nav class="topbar-nav">
            <a href="index.php<?php echo current_lang_query($langCode); ?>" class="js-loader-link"><?php echo lang(4030); ?></a>
			<?php if ($isLogged): ?>
			<a href="<?php echo e($appConfig['app']['panel_url']); ?>"><?php echo lang(4005); ?></a>
			<?php else: ?>
			<a href="#" data-open-modal="register"><?php echo lang(4002); ?></a>
			<?php endif; ?>
           <a href="#" id="openServerCard"><?php echo lang(4031); ?></a>
            <a href="#community"><?php echo lang(4032); ?></a>
        </nav>

<div class="topbar-right">
    <div class="lang-switch" aria-label="<?php echo lang(4036); ?>">
       <button type="button" class="lang-main">
		<img src="/assets/img/flags/<?php echo e($currentFlag); ?>.png" alt="<?php echo e($langCode); ?>" class="lang-flag-main">
	
		<img src="/assets/img/icons/nav_arrow_icon.png" alt="▼" class="lang-arrow">
		</button>

        <div class="lang-dropdown">
            <?php if ($langCode !== 'pt-BR'): ?>
                <a class="lang-item js-loader-link" href="index.php?lang=pt-BR" title="Português">
                    <img src="/assets/img/flags/br.png" alt="Português" class="lang-flag">
                </a>
            <?php endif; ?>

            <?php if ($langCode !== 'en'): ?>
                <a class="lang-item js-loader-link" href="index.php?lang=en" title="English">
                    <img src="/assets/img/flags/us.png" alt="English" class="lang-flag">
                </a>
            <?php endif; ?>

            <?php if ($langCode !== 'es'): ?>
                <a class="lang-item js-loader-link" href="index.php?lang=es" title="Español">
                    <img src="/assets/img/flags/es.png" alt="Español" class="lang-flag">
                </a>
            <?php endif; ?>
            <?php if ($langCode !== 'ru'): ?>
                <a class="lang-item js-loader-link" href="index.php?lang=ru" title="Russo">
                    <img src="/assets/img/flags/ru.png" alt="Russo" class="lang-flag">
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isLogged): ?>
        <div class="user-chip">
            <span><?php echo lang(4027); ?>, <?php echo e($currentUser['login']); ?></span>
            <small><?php echo lang(4028); ?>: <?php echo (int) $currentUser['characters_count']; ?></small>
        </div>
        <a class="btn btn-ghost js-loader-link" href="index.php?lang=<?php echo urlencode($langCode); ?>&logout=1"><?php echo lang(4026); ?></a>
    <?php else: ?>
	
	<button class="l2-btn" type="button"data-open-modal="login">
    <span class="l2-btn-left"></span>
    <span class="l2-btn-middle">
        <span class="l2-btn-middle-text"><?php echo lang(4001); ?></span>
    </span>
    <span class="l2-btn-right"></span>
	</button>

		 
    
    <?php endif; ?>
</div>
    </header>

    <main>
	<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>

   <div class="hero-inner">
    <div class="hero-left">
        <div class="hero-content">
            <div class="hero-logo-wrap">
                <img src="/assets/img/logo.png" alt="Logo" class="hero-logo">
            </div>

            <h1><?php echo lang(4000); ?></h1>

            <p class="hero-badge-text"><?php echo lang(4006); ?></p>
			<div class="hero-actions">
				<a href="/download" class="l2-download">
				<span class="l2-download__bg"></span>
				<span class="l2-download__text">
				<?php echo lang(4056); ?>
				</span>
				</a>
			</div>

        </div>
    </div>

    <div class="hero-right">
	
        <div class="hero-server-panel">
		
		
            <div class="server-card server-card--dropdown" id="serverInfoCard">
		
                <button type="button" class="server-card-main" id="serverInfoToggle" aria-expanded="false">
                    <div class="server-card-icon">
                        <img src="/assets/img/icons/dual.png" alt="Dual" class="server-card-icon-img">
                    </div>

                    <div class="server-card-info">
                        <div class="server-card-line">
                            <span class="server-card-label"><?php echo lang(4037); ?>:</span>
                            <span class="server-card-value server-card-value--gold"><?php echo lang(4038); ?></span>
                        </div>


						<div class="server-card-line">
							<span class="server-card-label"><?php echo lang(4059); ?>:</span>
							<span class="server-card-value <?php echo $loginServerClass; ?>">
								<?php echo $loginServerText; ?>
							</span>
						</div>
						
						<div class="server-card-line">
							<span class="server-card-label"><?php echo lang(4060); ?>:</span>
							<span class="server-card-value <?php echo $gameServerClass; ?>">
								<?php echo $gameServerText; ?>
							</span>
						</div>
                    </div>

                    <div class="server-card-arrow-wrap">
                        <img src="/assets/img/icons/header__server-item-arrow.png" alt="" class="server-card-arrow">
                    </div>
                </button>

                <div class="server-card-dropdown">
                    <div class="server-dropdown-item">
                        <span><?php echo lang(4042); ?></span>
                        <strong><?php echo lang(4043); ?></strong>
                    </div>
                    <div class="server-dropdown-item">
                        <span><?php echo lang(4044); ?></span>
                        <strong><?php echo lang(4045); ?></strong>
                    </div>
                    <div class="server-dropdown-item">
                        <span><?php echo lang(4046); ?></span>
                        <strong><?php echo lang(4047); ?></strong>
                    </div>
                    <div class="server-dropdown-item">
                         <span><?php echo lang(4048); ?></span>
                        <strong><?php echo lang(4049); ?></strong>
                   </div>
                </div>
				
							
				<?php if ($message !== ''): ?>
							<section class="flash-wrap">
								<div class="flash flash-<?php echo e($messageType); ?>">
									<?php echo e($message); ?>
								</div>
							</section>
						<?php endif; ?>
							</div>
		</div>
		</div>

   
</div>


</section>

       

<section class="home-news">
    <div class="home-news-inner">

        <article class="news-card">
            <div class="news-card-top">
                <img src="/assets/img/news/border-icon-top.png" alt="" class="news-card-top-icon">
            </div>

            <div class="news-card-frame">
                <img src="/assets/img/news/village.jpg" alt="Village" class="news-card-bg">
                <img src="/assets/img/news/news-item-shadow-1.png" alt="" class="news-card-shadow">
                <div class="news-card-overlay"></div>

                <div class="news-card-content">
                    <span class="news-date">09.05.2025</span>

                    <h3 class="news-title">
                        <?php echo lang(4050); ?>
                    </h3>

                    <p class="news-text">
						<?php echo lang(4051); ?>
                    </p>

                    <div class="news-actions">
                        <button class="l2-btn" type="button">
                            <span class="l2-btn-left"></span>
                            <span class="l2-btn-middle">
                                <span class="l2-btn-middle-text"><?php echo lang(4052); ?></span>
                            </span>
                            <span class="l2-btn-right"></span>
                        </button>

                        <button class="l2-btn" type="button">
                            <span class="l2-btn-left"></span>
                            <span class="l2-btn-middle">
                                <span class="l2-btn-middle-text"><?php echo lang(4053); ?></span>
                            </span>
                            <span class="l2-btn-right"></span>
                        </button>
                    </div>
                </div>

                <img src="/assets/img/news/border_bg.png" alt="" class="news-card-border">
            </div>
        </article>

        <article class="news-card">
            <div class="news-card-top">
                <img src="/assets/img/news/border-icon-top.png" alt="" class="news-card-top-icon">
            </div>

            <div class="news-card-frame">
                <img src="/assets/img/news/village_night.jpg" alt="Village Night" class="news-card-bg">
                <img src="/assets/img/news/news-item-shadow-1.png" alt="" class="news-card-shadow">
                <div class="news-card-overlay"></div>

                <div class="news-card-content">
                    <span class="news-date">10.05.2025</span>

                    <h3 class="news-title">
                        <?php echo lang(4054); ?>
                    </h3>

                    <p class="news-text">
						<?php echo lang(4055); ?>
                    </p>

                    <div class="news-actions">
                        <button class="l2-btn" type="button">
                            <span class="l2-btn-left"></span>
                            <span class="l2-btn-middle">
                                <span class="l2-btn-middle-text"><?php echo lang(4052); ?></span>
                            </span>
                            <span class="l2-btn-right"></span>
                        </button>

                        <button class="l2-btn" type="button">
                            <span class="l2-btn-left"></span>
                            <span class="l2-btn-middle">
                                <span class="l2-btn-middle-text"><?php echo lang(4053); ?></span>
                            </span>
                            <span class="l2-btn-right"></span>
                        </button>
                    </div>
                </div>

                <img src="/assets/img/news/border_bg.png" alt="" class="news-card-border">
            </div>
        </article>

    </div>
</section>
<a href="<?php echo e($appConfig['app']['discord_link']); ?>" target="_blank" class="discord-float">
    <img src="/assets/img/discord.png" alt="Discord">
</a>
    </main>
</div>

<div class="modal-backdrop <?php echo ($openModal !== '') ? 'is-visible' : ''; ?>" id="modal-backdrop"></div>

<div class="modal <?php echo ($openModal === 'login') ? 'is-visible' : ''; ?>" id="login-modal">
    <div class="modal-card">
        <button type="button" class="modal-close" data-close-modal>&times;</button>
        <h3><?php echo lang(4011); ?></h3>

        <form method="post" class="auth-form js-loader-form js-skip-loader">
            <input type="hidden" name="form_action" value="login">

            <div class="field">
                <label><?php echo lang(4007); ?></label>
                <input type="text" name="login" maxlength="16" required>
            </div>

            <div class="field">
                <label><?php echo lang(4008); ?></label>
                <input type="password" name="password" maxlength="32" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo lang(4011); ?></button>
                <button type="button" class="btn btn-ghost" data-switch-modal="register"><?php echo lang(4002); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal <?php echo ($openModal === 'register') ? 'is-visible' : ''; ?>" id="register-modal">
    <div class="modal-card">
        <button type="button" class="modal-close" data-close-modal></button>
        <h3><?php echo lang(4012); ?></h3>

        <form method="post" class="auth-form js-loader-form js-skip-loader">
            <input type="hidden" name="form_action" value="register">

            <div class="field">
                <label><?php echo lang(4007); ?></label>
                <input type="text" name="reg_login" maxlength="16" required>
            </div>

            <?php if (!empty($authCfg['use_email'])): ?>
            <div class="field">
                <label><?php echo lang(4009); ?></label>
                <input type="email" name="reg_email" maxlength="120" required>
            </div>
            <?php endif; ?>

            <div class="field">
                <label><?php echo lang(4008); ?></label>
                <input type="password" name="reg_password" maxlength="32" required>
            </div>

            <div class="field">
                <label><?php echo lang(4010); ?></label>
                <input type="password" name="reg_password2" maxlength="32" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo lang(4012); ?></button>
                <button type="button" class="btn btn-ghost" data-switch-modal="login"><?php echo lang(4001); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
window.APP_LANG = "<?php echo e($langCode); ?>";
</script>
<script src="/assets/js/app.js"></script>
</body>
</html>