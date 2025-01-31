<?php
require 'tohost.php';

if (!isset($_GET['animeId']) || empty($_GET['animeId'])) {
    die("Anime ID is required.");
}
include 'header.html';

$animeId = htmlspecialchars($_GET['animeId']);

// Endpoint for fetching episodes
$endpoint = '/api/v2/hianime/anime/' . $animeId . '/episodes';
$apiUrl = BASE_API_URL . $endpoint;

// Fetch data from API
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if (!$data || !$data['success']) {
    die("Failed to fetch episodes.");
}

$episodes = $data['data']['episodes'];
$totalEpisodes = $data['data']['totalEpisodes'];

// Verify the fetched data
if (empty($episodes)) {
    die("No episodes found.");
}

// Group episodes into sections of 100
$groupedEpisodes = [];
foreach ($episodes as $episode) {
    $groupIndex = floor(($episode['number'] - 1) / 100);
    $groupedEpisodes[$groupIndex][] = $episode;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anime Stream - <?= htmlspecialchars($animeId) ?></title>
    <style>
        /* General Variables */
        :root {
            --background-dark: #1b1e28;
            --background-medium: #232635;
            --background-light: #2e3245;
            --highlight-pink: #ffccdd;
            --text-color: #eaeaea;
            --text-muted: #aaa;
            --border-color: #444;
            --transition-duration: 0.3s;
        }

        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-dark);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            width: 90%;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        /* Navigation Styles */
        .episode-nav {
            background-color: var(--background-medium);
            padding: 15px;
            border-radius: 8px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--background-light) var(--background-dark);
            max-height: 100vh;
        }

        .episode-nav h2 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 18px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .episode-section h3 {
            cursor: pointer;
            padding: 8px;
            margin: 0;
            background-color: var(--background-light);
            color: var(--text-color);
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color var(--transition-duration);
        }

        .episode-section h3:hover {
            background-color: var(--highlight-pink);
            color: var(--background-dark);
        }

        .hidden {
            display: none;
        }

        /* Episode Card */
        .episode-card {
            background-color: var(--background-light);
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid transparent;
            transition: background-color var(--transition-duration), transform var(--transition-duration);
        }

        .episode-card:hover {
            background-color: var(--highlight-pink);
            transform: scale(1.02);
            color: var(--background-dark);
        }

        .episode-card.active {
            background-color: var(--highlight-pink);
            color: var(--background-dark);
        }

        .episode-number, .episode-title {
            font-size: 14px;
        }

        .filler-tag {
            background-color: #ff6b6b;
            color: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Player Styles */
        .player-container {
        background-color: var(--background-medium);
        border-radius: 8px;
        padding: 0; /* Removed extra padding */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        overflow: hidden; /* Ensures no extra space from overflowing content */
        }

        iframe {
        width: 100%;
        height: 500px;
        border: none;
        background-color: #000;
        margin: 0; /* Remove default margins */
        border-radius: 0; /* Adjust this if you don't need rounded corners */
        }


        .player-options {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: 10px;
            gap: 10px;
        }

        .player-options select {
            background-color: var(--background-light);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            iframe {
                height: 300px;
            }
        }

        @media (min-width: 1024px) {
            iframe {
                height: 700px;
            }
        }
        a {
    text-decoration: none; /* Remove underline */
    color: var(--text-color); /* Set text color to the defined variable */
}

a:hover {
    text-decoration: none; /* Ensure no underline on hover */
    color: var(--text-color); /* Keep text color the same on hover */
}
.episode-number, .episode-title {
    font-size: 14px;
    color: var(--text-color) !important; /* Ensures the color is white */
    text-decoration: none !important; /* Ensures no underline */
}

    </style>
</head>
<body>
    <main class="container">
        <!-- Episode Navigation -->
        <nav class="episode-nav">
            <h2>Episodes</h2>
            <?php foreach ($groupedEpisodes as $groupIndex => $group): ?>
                <section class="episode-section">
                    <h3 class="collapsible" data-target="group-<?= $groupIndex ?>">
                        Episodes <?= $groupIndex * 100 + 1 ?> - <?= min(($groupIndex + 1) * 100, $totalEpisodes) ?>
                        <span>+</span>
                    </h3>
                    <div id="group-<?= $groupIndex ?>" class="episode-grid hidden">
                        <?php foreach ($group as $episode): ?>
                            <a href="#" class="episode-card" 
                               data-episode-id="<?= $episode['episodeId'] ?>" 
                               onclick="loadEpisode(event, '<?= $episode['episodeId'] ?>')">
                                <div class="episode-number">Ep <?= $episode['number'] ?></div>
                                <div class="episode-title"><?= htmlspecialchars($episode['title']) ?></div>
                                <?php if ($episode['isFiller']): ?>
                                    <span class="filler-tag">Filler</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </nav>

        <!-- Player -->
        <section class="player-container">
    <iframe id="episode-player" src="<?= !empty($episodes[0]['episodeId']) ? 'player.php?episodeId=' . $episodes[0]['episodeId'] . '&category=sub' : '' ?>"></iframe>
    <div class="player-options">
        <div>
            <label for="language-select">Language:</label>
            <select id="language-select" onchange="updatePlayer()">
                <option value="sub">Sub</option>
                <option value="dub">Dub</option>
            </select>
        </div>
        <div>
            <label for="server-select">Server:</label>
            <select id="server-select" onchange="updatePlayer()">
                <option value="player">Server 1</option>
                <option value="player2">Server 2</option>
            </select>
        </div>
    </div>

</section>
    <?php include 'detailstream.php'; ?>
    </main>

    <script>
        // Toggle episode group visibility
        document.querySelectorAll('.collapsible').forEach(header => {
            header.addEventListener('click', () => {
                const targetId = header.getAttribute('data-target');
                const target = document.getElementById(targetId);
                target.classList.toggle('hidden');
                header.querySelector('span').textContent = target.classList.contains('hidden') ? '+' : '-';
            });
        });

        // Load and update player
        let currentEpisodeId = "<?= !empty($episodes[0]['episodeId']) ? $episodes[0]['episodeId'] : '' ?>";

        function loadEpisode(event, episodeId) {
            event.preventDefault();
            currentEpisodeId = episodeId;
            updatePlayer();
        }

        function updatePlayer() {
            const language = document.getElementById('language-select').value;
            const server = document.getElementById('server-select').value;
            const player = document.getElementById('episode-player');
            player.src = `${server}.php?episodeId=${currentEpisodeId}&category=${language}`;
        }
    </script>
</body>
</html>
