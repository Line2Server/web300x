<?php
session_start();
$config = require 'includes/config.php';
require_once 'ind/db.php';

$conn = getDbConnection();

// Definir idioma padrão
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'pt';
}

// Trocar idioma via GET
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: dashboard.php");
    exit;
}

// Carregar arquivo de idioma
$lang = include "lang/{$_SESSION['lang']}.php";

// Proteção de login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}


$saldo = 0; // valor padrão
$username = $_SESSION['username'] ?? '';

if ($username) {
    // Consulta do saldo
    $stmt = $conn->prepare("SELECT balance FROM account_balance WHERE login = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($balanceFromDb);
    
    if ($stmt->fetch()) {
        $saldo = floatval($balanceFromDb);
    }

    $stmt->close();

    // Consulta do accesslevel
    $stmt = $conn->prepare("SELECT access_level FROM accounts WHERE login = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['access_level'] = (int)$row['access_level'];
    }

    $stmt->close();
}


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	  <meta charset="UTF-8" />
	  <title>Painel UCP - Lineage II</title>
	  <link rel="stylesheet" href="templates/dark/assets/auth.css" />
	  <link rel="stylesheet" href="templates/dark/assets/dark.css" />
	  <link rel="shortcut icon" href="icon/favicon.png">
	  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
	  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	  <script src="admin_button.js"></script>

	</head>
	<body>
<script>
  window.USER_ACCESS_LEVEL = <?= isset($_SESSION['access_level']) ? (int)$_SESSION['access_level'] : 0 ?>;
</script>

	  <div class="container">
		<aside class="sidebar">
		  <div class="profile">
			<span class="user"><i class="fa fa-user"></i> <?= htmlspecialchars($username) ?></span>
			<span class="saldo"><?= $lang['saldo'] ?>: R$ <?= number_format($saldo, 2, ',', '.') ?></span>
			
		  </div>
			<nav class="menu">
			<a href="index.php"><i class="fa fa-home"></i> <?= $lang['menu']['home'] ?></a>
			<a href="index.php"><i class="fa fa-user-friends"></i> <?= $lang['menu']['referral'] ?></a>
			<a href="donate.php"><i class="fa fa-donate"></i> <?= $lang['menu']['donations'] ?></a>
			<a href="services.php"><i class="fa fa-cogs"></i> <?= $lang['menu']['services'] ?></a>
			<a href="shop.php"><i class="fa fa-shopping-cart"></i> <?= $lang['menu']['shop'] ?></a>
			<a href="gamestats.php"><i class="fa fa-chart-bar"></i> <?= $lang['menu']['stats'] ?></a>
			<a href="settings.php"><i class="fa fa-wrench"></i> <?= $lang['menu']['settings'] ?></a>
			<a href="<?= $config['site_url'] ?>"><i class="fa fa-globe"></i> <?= $lang['menu']['site'] ?></a>
			<a href="logout.php"><i class="fa fa-sign-out-alt"></i> <?= $lang['menu']['logout'] ?></a>
			</nav>
		</aside>

		<main class="main-panel">
		  <header class="header">
			<img src="templates/dark/assets/img-dark/logo-db.png" alt="Logo" class="logo-db" />
			<div class="lang-icons">
			<a href="?lang=pt"><img src="icon/pt.png" alt="Português" /></a>
			<a href="?lang=en"><img src="icon/us.png" alt="English" /></a>
			</div>
		  </header>

      <section class="content">
        <h2><?= $lang['characters'] ?></h2>
		<div class="characters-info">
		<div class="character-grid">
			<?php
			require_once 'ind/db.php';
			$conn = getDbConnection();
			$login = $conn->real_escape_string($_SESSION['username']);
		
			$sql = "SELECT char_name, level, title, sex, exp, sp, karma, pvpkills, pkkills, online, onlinetime, vip, nobless, race, classid FROM characters WHERE account_name = '$login' ORDER BY char_slot ASC LIMIT 7";

			$result = $conn->query($sql);
		
			// Mapeia raças para imagens
			$races = ['Humano', 'Elfo', 'DarkElf', 'Orc', 'Drawn'];
			$count = 0;
		
			if ($result->num_rows > 0) {
				while ($char = $result->fetch_assoc()) {
					$raceIndex = (int)$char['race'];
					$raceName = $races[$raceIndex] ?? 'Humano';
					$imgPath = "img/races/{$raceName}.jpg";
					echo "
					<div class='character-card filled'
						data-name='" . htmlspecialchars($char['char_name']) . "'
						data-level='{$char['level']}'
						data-title='" . htmlspecialchars($char['title']) . "'
						data-sex='{$char['sex']}'
						data-exp='{$char['exp']}'
						data-sp='{$char['sp']}'
						data-karma='{$char['karma']}'
						data-pvpkills='{$char['pvpkills']}'
						data-pkkills='{$char['pkkills']}'
						data-online='{$char['online']}'
						data-onlinetime='{$char['onlinetime']}'
						data-vip='{$char['vip']}'
						data-nobless='{$char['nobless']}'
						data-race='{$raceName}'
						data-classid='{$char['classid']}'>
						<img src='{$imgPath}' alt='{$raceName}' />
					</div>";
					$count++;
				}
			}
		
			// Preencher os slots restantes com cards vazios
			for ($i = $count; $i < 7; $i++) {
				echo "
				<div class='character-card empty'>
					<div class='placeholder'></div>
				</div>";
			}
			?>
		</div>
		</div>
		<div id="characterDetails" class="info-characters">
		<div><strong><?= $lang['char_info']['name'] ?>:</strong> <span id="charName">—</span></div>
		<div><strong><?= $lang['char_info']['level'] ?>:</strong> <span id="charLevel">—</span></div>
		<div><strong><?= $lang['char_info']['title'] ?>:</strong> <span id="charTitle">—</span></div>
		<div><strong><?= $lang['char_info']['sex'] ?>:</strong> <span id="charSex">—</span></div>
		<div><strong><?= $lang['char_info']['exp'] ?>:</strong> <span id="charExp">—</span></div>
		<div><strong><?= $lang['char_info']['sp'] ?>:</strong> <span id="charSp">—</span></div>
		<div><strong><?= $lang['char_info']['karma'] ?>:</strong> <span id="charKarma">—</span></div>
		<div><strong><?= $lang['char_info']['pvpkills'] ?>:</strong> <span id="charPvP">—</span></div>
		<div><strong><?= $lang['char_info']['pkkills'] ?>:</strong> <span id="charPk">—</span></div>
		<div><strong><?= $lang['char_info']['online'] ?>:</strong> <span id="charOnline">—</span></div>
		<div><strong><?= $lang['char_info']['onlinetime'] ?>:</strong> <span id="charOnlineTime">—</span></div>
		<div><strong><?= $lang['char_info']['vip'] ?>:</strong> <span id="charVip">—</span></div>
		<div><strong><?= $lang['char_info']['nobles'] ?>:</strong> <span id="charNobles">—</span></div>
		<div><strong><?= $lang['char_info']['race'] ?>:</strong> <span id="charRace">—</span></div>
		<div><strong><?= $lang['char_info']['class'] ?>:</strong> <span id="charClass">—</span></div>

		</div>

      </section>
    </main>
  </div>
  
<script id="l2-classes-map">
const classMap = {

    // HUMAN
    0: "Human Fighter",
    1: "Warrior",
    2: "Gladiator",
    3: "Warlord",
    4: "Human Knight",
    5: "Paladin",
    6: "Dark Avenger",
    7: "Rogue",
    8: "Treasure Hunter",
    9: "Hawkeye",

    10: "Human Mystic",
    11: "Human Wizard",
    12: "Sorcerer",
    13: "Necromancer",
    14: "Warlock",
    15: "Cleric",
    16: "Bishop",
    17: "Prophet",

    // ELF
    18: "Elven Fighter",
    19: "Elven Knight",
    20: "Temple Knight",
    21: "Sword Singer",
    22: "Elven Scout",
    23: "Plains Walker",
    24: "Silver Ranger",

    25: "Elven Mystic",
    26: "Elven Wizard",
    27: "Spellsinger",
    28: "Elemental Summoner",
    29: "Elven Oracle",
    30: "Elven Elder",

    // DARK ELF
    31: "Dark Fighter",
    32: "Palus Knight",
    33: "Shillien Knight",
    34: "Bladedancer",
    35: "Assassin",
    36: "Abyss Walker",
    37: "Phantom Ranger",

    38: "Dark Mystic",
    39: "Dark Wizard",
    40: "Spellhowler",
    41: "Phantom Summoner",
    42: "Shillien Oracle",
    43: "Shillien Elder",

    // ORC
    44: "Orc Fighter",
    45: "Orc Raider",
    46: "Destroyer",
    47: "Monk",
    48: "Tyrant",

    49: "Orc Mystic",
    50: "Orc Shaman",
    51: "Overlord",
    52: "Warcryer",

    // DWARF
    53: "Dwarven Fighter",
    54: "Scavenger",
    55: "Bounty Hunter",
    56: "Artisan",
    57: "Warsmith",

    // 3RD CLASS (INTERLUDE FINAL)
    88: "Duelist",
    89: "Dreadnought",
    90: "Phoenix Knight",
    91: "Hell Knight",
    92: "Sagittarius",
    93: "Adventurer",
    94: "Archmage",
    95: "Soultaker",
    96: "Arcana Lord",
    97: "Cardinal",
    98: "Hierophant",

    99: "Eva's Templar",
    100: "Sword Muse",
    101: "Wind Rider",
    102: "Moonlight Sentinel",
    103: "Mystic Muse",
    104: "Elemental Master",
    105: "Eva's Saint",

    106: "Shillien Templar",
    107: "Spectral Dancer",
    108: "Ghost Hunter",
    109: "Ghost Sentinel",
    110: "Storm Screamer",
    111: "Spectral Master",
    112: "Shillien Saint",

    113: "Titan",
    114: "Grand Khavatari",
    115: "Dominator",
    116: "Doomcryer",

    117: "Fortune Seeker",
    118: "Maestro"
};
</script>


 <script>
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".character-card.filled");

  const charName = document.getElementById("charName");
  const charLevel = document.getElementById("charLevel");
  const charTitle = document.getElementById("charTitle");
  const charSex = document.getElementById("charSex");
  const charExp = document.getElementById("charExp");
  const charSp = document.getElementById("charSp");
  const charKarma = document.getElementById("charKarma");
  const charPvP = document.getElementById("charPvP");
  const charPk = document.getElementById("charPk");
  const charOnline = document.getElementById("charOnline");
  const charOnlineTime = document.getElementById("charOnlineTime");
  const charHero = document.getElementById("charVip");
  const charNobles = document.getElementById("charNobles");
  const charRace = document.getElementById("charRace");



  function updateCharacterDetails(card) {
    cards.forEach(c => c.classList.remove("selected"));
    card.classList.add("selected");

    charName.textContent = card.dataset.name;
    charLevel.textContent = card.dataset.level;
    charTitle.textContent = card.dataset.title;
    charSex.textContent = card.dataset.sex === "1" ? "Feminino" : "Masculino"; // ou use tradução do lang[]
    charExp.textContent = card.dataset.exp;
    charSp.textContent = card.dataset.sp;
    charKarma.textContent = card.dataset.karma;
    charPvP.textContent = card.dataset.pvpkills;
    charPk.textContent = card.dataset.pkkills;
    charOnline.textContent = card.dataset.online === "1" ? "Online" : "Offline";
    charOnlineTime.textContent = formatTime(card.dataset.onlinetime);
    charVip.textContent = card.dataset.vip === "1" ? "Sim" : "Não";
    charNobles.textContent = card.dataset.nobless === "1" ? "Sim" : "Não";
    charRace.textContent = card.dataset.race;
 const classId = parseInt(card.dataset.classid);
charClass.textContent = classMap[classId] || "Desconhecido";

  }

  function formatTime(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return `${h}h ${m}min`;
  }

  cards.forEach(card => {
    card.addEventListener("click", function () {
      updateCharacterDetails(this);
    });
  });

  if (cards.length > 0) {
    updateCharacterDetails(cards[0]);
  }
});
</script>
</body>
</html>
