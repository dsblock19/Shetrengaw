<?php
/**
 * Plugin Name: Shetrengaw Portal
 * Description: Standalone Shetrengaw strategy game portal with embedded game, rules guide, and court set piece gallery.
 * Version: 1.0.0
 * Author: Antigravity
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue Stylesheet
 */
add_action('wp_enqueue_scripts', 'shetrengaw_enqueue_assets');
function shetrengaw_enqueue_assets() {
    wp_enqueue_style('shetrengaw-style', plugins_url('assets/css/style.css', __FILE__));
}

/**
 * Activation Hook: Programmatically create Shetrengaw pages and inject card to homepage
 */
register_activation_hook(__FILE__, 'shetrengaw_activate');
function shetrengaw_activate() {
    // 1. Create Parent "Shetrengaw Portal" Page
    $parent_page_id = shetrengaw_create_page('Shetrengaw Portal', 'shetrengaw-portal', '[shetrengaw_home]', 0);

    if ($parent_page_id) {
        // 2. Create Child Pages
        shetrengaw_create_page('Rules & Guide', 'rules', '[shetrengaw_rules]', $parent_page_id);
        shetrengaw_create_page('Play Shetrengaw', 'game', '[shetrengaw_game]', $parent_page_id);
        shetrengaw_create_page('Court Set Gallery', 'gallery', '[shetrengaw_gallery]', $parent_page_id);
    }

    // Refresh homepage to show/hide this card dynamically
    if (file_exists('/home/sg/BlockMania/scripts/refresh_homepage.php')) {
        include_once '/home/sg/BlockMania/scripts/refresh_homepage.php';
        if (function_exists('blockmania_refresh_homepage')) {
            blockmania_refresh_homepage();
        }
    }
}

/**
 * Deactivation Hook: Clean up pages and restore homepage
 */
register_deactivation_hook(__FILE__, 'shetrengaw_deactivate');
function shetrengaw_deactivate() {
    // 1. Delete Pages
    $parent_page = get_page_by_path('shetrengaw-portal');
    if ($parent_page) {
        // Delete child pages first
        $children = get_pages(array('child_of' => $parent_page->ID));
        foreach ($children as $child) {
            wp_delete_post($child->ID, true);
        }
        // Delete parent page
        wp_delete_post($parent_page->ID, true);
    }

    // Refresh homepage to show/hide this card dynamically
    if (file_exists('/home/sg/BlockMania/scripts/refresh_homepage.php')) {
        include_once '/home/sg/BlockMania/scripts/refresh_homepage.php';
        if (function_exists('blockmania_refresh_homepage')) {
            blockmania_refresh_homepage();
        }
    }
}

/**
 * Helper: Create page programmatically
 */
function shetrengaw_create_page($title, $slug, $content, $parent_id = 0) {
    $page_check = get_page_by_path($parent_id ? 'shetrengaw-portal/' . $slug : $slug);
    
    if (!isset($page_check->ID)) {
        $new_page = array(
            'post_type'      => 'page',
            'post_title'     => $title,
            'post_name'      => $slug,
            'post_content'   => $content,
            'post_status'    => 'publish',
            'post_author'    => 1,
            'post_parent'    => $parent_id,
        );
        return wp_insert_post($new_page);
    }
    return $page_check->ID;
}

/**
 * Helper: Inject Card into Homepage (Page ID 21)
 */
function shetrengaw_inject_homepage_card($parent_id) {
    $homepage = get_post(21);
    if (!$homepage) return;

    $content = $homepage->post_content;

    // Check if shetrengaw card is already injected
    if (strpos($content, 'shetrengaw-portal') !== false || strpos($content, 'page_id=' . $parent_id) !== false) {
        return;
    }

    // Prepare card HTML
    $card_html = "\n\n  <a href=\"/?page_id=" . intval($parent_id) . "\" style=\"text-decoration: none;color: #333;background: #fff;border: 1px solid #eaeaea;border-radius: 16px;padding: 40px 30px;display: flex;flex-direction: column;justify-content: space-between;border-top: 6px solid #c9a84c;min-height: 280px\">\n    <div>\n      <div style=\"font-size: 40px;margin-bottom: 15px\">👑</div>\n      <h2 style=\"margin: 0 0 10px 0;color: #c9a84c;font-size: 24px;font-weight: 800;line-height:1.2\">Shetrengaw</h2>\n      <p style=\"color: #666;font-size: 15px;line-height: 1.5;margin: 0 0 20px 0\">Play the ancient asymmetric strategy game of the Sto and Mawdige, explore its rules, and browse the gallery of bronze and jade court set pieces.</p>\n    </div>\n    <span style=" . '"display: inline-block;background: #c9a84c;color: #fff;padding: 12px 24px;border-radius: 8px;font-weight: 700;font-size: 15px;text-align: center;margin-top: auto"' . ">Play Shetrengaw &rarr;</span>\n  </a>\n";

    // Target the end of the HTML block/grid
    $target_patterns = array(
        "</div>\n<!-- /wp:html -->",
        "</div>\r\n<!-- /wp:html -->"
    );

    $replaced = false;
    foreach ($target_patterns as $pattern) {
        if (strpos($content, $pattern) !== false) {
            $content = str_replace($pattern, $card_html . $pattern, $content);
            $replaced = true;
            break;
        }
    }

    if ($replaced) {
        wp_update_post(array(
            'ID'           => 21,
            'post_content' => $content
        ));
    }
}

/**
 * Helper: Remove Card from Homepage (Page ID 21)
 */
function shetrengaw_remove_homepage_card() {
    $homepage = get_post(21);
    if (!$homepage) return;

    $content = $homepage->post_content;

    // Pattern matching the shetrengaw card regex
    $pattern = '/\s*<a href="\/\?page_id=\d+"[^>]*>.*?Shetrengaw.*?<\/a>/s';
    
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, '', $content);
        wp_update_post(array(
            'ID'           => 21,
            'post_content' => $content
        ));
    }
}

/**
 * Helper: Render Navigation Tabs
 */
function shetrengaw_get_nav($active_tab) {
    $parent = get_page_by_path('shetrengaw-portal');
    $rules = get_page_by_path('shetrengaw-portal/rules');
    $game = get_page_by_path('shetrengaw-portal/game');
    $gallery = get_page_by_path('shetrengaw-portal/gallery');

    $parent_url = $parent ? get_permalink($parent->ID) : home_url('/shetrengaw-portal/');
    $rules_url = $rules ? get_permalink($rules->ID) : home_url('/shetrengaw-portal/rules/');
    $game_url = $game ? get_permalink($game->ID) : home_url('/shetrengaw-portal/game/');
    $gallery_url = $gallery ? get_permalink($gallery->ID) : home_url('/shetrengaw-portal/gallery/');

    $html = '<div class="shtr-nav">';
    $html .= sprintf('<a href="%s" class="shtr-nav-item %s">Shetrengaw Home</a>', esc_url($parent_url), $active_tab === 'home' ? 'active' : '');
    $html .= sprintf('<a href="%s" class="shtr-nav-item %s">Rules & Guide</a>', esc_url($rules_url), $active_tab === 'rules' ? 'active' : '');
    $html .= sprintf('<a href="%s" class="shtr-nav-item %s">Play Game</a>', esc_url($game_url), $active_tab === 'game' ? 'active' : '');
    $html .= sprintf('<a href="%s" class="shtr-nav-item %s">Court Set Gallery</a>', esc_url($gallery_url), $active_tab === 'gallery' ? 'active' : '');
    $html .= '</div>';

    return $html;
}

/**
 * Shortcode: [shetrengaw_home]
 */
add_shortcode('shetrengaw_home', 'shetrengaw_home_shortcode');
function shetrengaw_home_shortcode() {
    ob_start();
    ?>
    <div class="shtr-portal-container">
        <?php echo shetrengaw_get_nav('home'); ?>
        
        <div class="shtr-hero">
            <h1 class="shtr-title">SHETRENGAW</h1>
            <p class="shtr-subtitle">The Game of the Two Peoples</p>
        </div>

        <div class="shtr-card main-intro">
            <p class="shtr-intro-text">
                <strong>SHETRENGAW</strong> is an asymmetric, two-player strategy game descended from the ancient Indian game of <em>Chaturanga</em>. 
                Invented by the nomadic <strong>Sto</strong> people, the game encodes the conflict between their own nomadic identity (represented by the <strong>Igto</strong>) and their settled rivals (represented by the <strong>Mawdige</strong>).
            </p>
            <p class="shtr-intro-text">
                To play a full session of SHETRENGAW, each player must experience both sides. A winner is determined by combined scores across both rounds — meaning a player who loses both rounds can still win the session by losing well.
            </p>
        </div>

        <div class="shtr-menu-grid">
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('shetrengaw-portal/rules')->ID)); ?>" class="shtr-menu-card card-rules">
                <span class="shtr-menu-icon">📜</span>
                <h3>Rules & Guide</h3>
                <p>Read about piece movements, asymmetric win conditions, check counters, and turn structures.</p>
                <span class="shtr-card-btn">Learn Rules &rarr;</span>
            </a>

            <a href="<?php echo esc_url(get_permalink(get_page_by_path('shetrengaw-portal/game')->ID)); ?>" class="shtr-menu-card card-game">
                <span class="shtr-menu-icon">⚔️</span>
                <h3>Play Shetrengaw</h3>
                <p>Launch a live multiplayer or local match connected to your private database.</p>
                <span class="shtr-card-btn">Play Now &rarr;</span>
            </a>

            <a href="<?php echo esc_url(get_permalink(get_page_by_path('shetrengaw-portal/gallery')->ID)); ?>" class="shtr-menu-card card-gallery">
                <span class="shtr-menu-icon">🏛️</span>
                <h3>Piece Gallery</h3>
                <p>Explore the court pieces crafted from cast bronze and warm amber (Igto) or jade and lapis (Mawdige).</p>
                <span class="shtr-card-btn">View Gallery &rarr;</span>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [shetrengaw_rules]
 */
add_shortcode('shetrengaw_rules', 'shetrengaw_rules_shortcode');
function shetrengaw_rules_shortcode() {
    ob_start();
    ?>
    <div class="shtr-portal-container">
        <?php echo shetrengaw_get_nav('rules'); ?>

        <div class="shtr-card">
            <h2 class="shtr-section-title">Core Gameplay & Rules</h2>
            <p style="line-height: 1.6; margin-bottom: 20px;">
                SHETRENGAW is played on an 8x8 grid. The mountain ranks (ranks 4 and 5) run through the center of the board, dividing the territories of the Igto and Mawdige.
            </p>

            <h3 class="shtr-sub-title">The Five Actions</h3>
            <table class="shtr-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Sto Term</th>
                        <th>Effect</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Move</strong></td>
                        <td>Vedux</td>
                        <td>Move a piece to a legal empty square.</td>
                    </tr>
                    <tr>
                        <td><strong>Topple</strong></td>
                        <td>Oniawxt</td>
                        <td>Capture an enemy piece and send it to your jail.</td>
                    </tr>
                    <tr>
                        <td><strong>Kill</strong></td>
                        <td>Nineut</td>
                        <td>Remove an enemy piece permanently from the board.</td>
                    </tr>
                    <tr>
                        <td><strong>Shelter</strong></td>
                        <td>Dawminia</td>
                        <td>Move a piece into a mountain safe space.</td>
                    </tr>
                    <tr>
                        <td><strong>Drop</strong></td>
                        <td>Pawrawt</td>
                        <td>Return a jailed piece to your side of the board.</td>
                    </tr>
                </tbody>
            </table>

            <h3 class="shtr-sub-title" style="margin-top: 30px;">Win Conditions</h3>
            <ul class="shtr-list">
                <li><strong>Igto Wins</strong> by capturing the Mawdige <strong>RAJA</strong>, or by keeping it in check for 3 consecutive turns (Persistent Pursuit).</li>
                <li><strong>Mawdige Wins</strong> by capturing the Igto <strong>RAWDAW</strong> and then capturing 2 additional pieces.</li>
                <li><strong>Jail Break Loss</strong>: If your pieces on the board fall below the count of your pieces held your jail, you lose immediately.</li>
                <li><strong>Mountain Siege</strong>: A piece in a mountain safe space is eliminated if surrounded by 2 or more enemies for 2 consecutive turns.</li>
            </ul>
        </div>

        <div class="shtr-card">
            <h2 class="shtr-section-title">Pieces & Movements</h2>
            <p style="line-height: 1.6; margin-bottom: 20px;">
                The two peoples field distinct pieces with unique values, constraints, and movement styles. Below is the official reference sheet from the rulebook:
            </p>
            <table class="shtr-table">
                <thead>
                    <tr>
                        <th>Igto (Nomad)</th>
                        <th>Mawdige (Kingdom)</th>
                        <th>Code</th>
                        <th>Value</th>
                        <th>Movement Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>RAWDAW</strong> (Chieftain)</td>
                        <td><strong>RAJA</strong> (King)</td>
                        <td><code>RD</code></td>
                        <td>—</td>
                        <td>Moves 1 square forward, forward-diagonal, or sideways. The Igto <strong>RAWDAW</strong> can also retreat backward-diagonal, while the Mawdige <strong>RAJA</strong> has no backward movement (cannot retreat).</td>
                    </tr>
                    <tr>
                        <td><strong>PORLE</strong> (Tactician)</td>
                        <td><strong>STENAPTI</strong> (Tactician)</td>
                        <td><code>PO</code></td>
                        <td>6</td>
                        <td>Moves any number of squares diagonally. Cannot leap over other pieces. Color-bound to its starting squares.</td>
                    </tr>
                    <tr>
                        <td><strong>RATHAW</strong> (Chariot)</td>
                        <td><strong>RATHAW</strong> (Chariot)</td>
                        <td><code>RT</code></td>
                        <td>7</td>
                        <td>Moves any number of squares orthogonally (horizontal or vertical). Cannot leap over other pieces.</td>
                    </tr>
                    <tr>
                        <td><strong>GAWJA</strong> (Elephant)</td>
                        <td><strong>THASTIN</strong> (Elephant)</td>
                        <td><code>GW</code></td>
                        <td>3</td>
                        <td>Moves up to 2 squares in any direction (orthogonal or diagonal). Cannot leap. <strong>Crucial constraint:</strong> The Elephant cannot cross the central mountain ranks (ranks 4–5) under any circumstances.</td>
                    </tr>
                    <tr>
                        <td><strong>ASHUAW</strong> (Horse)</td>
                        <td><strong>ASHUAW</strong> (Horse)</td>
                        <td><code>AS</code></td>
                        <td>4</td>
                        <td>Moves in an L-shape (2 squares in one direction, 1 square perpendicular). Can leap over intervening pieces. Ignores central mountain terrain restrictions.</td>
                    </tr>
                    <tr>
                        <td><strong>STENICA</strong> (Infantry)</td>
                        <td><strong>NPAWTI</strong> (Infantry)</td>
                        <td><code>TI</code></td>
                        <td>1 / 2</td>
                        <td>Moves 1 square forward or sidesteps diagonally. Captures straight ahead. Promotes on the back two opponent ranks (ranks 7-8 for Igto, 1-2 for Mawdige) to gain backward movement and capture.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [shetrengaw_game]
 */
add_shortcode('shetrengaw_game', 'shetrengaw_game_shortcode');
function shetrengaw_game_shortcode() {
    ob_start();
    ?>
    <div class="shtr-portal-container">
        <?php echo shetrengaw_get_nav('game'); ?>

        <div class="shtr-game-wrapper">
            <iframe src="/shetrengaw/" class="shtr-game-iframe" title="Shetrengaw Game Board"></iframe>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [shetrengaw_gallery]
 */
add_shortcode('shetrengaw_gallery', 'shetrengaw_gallery_shortcode');
function shetrengaw_gallery_shortcode() {
    ob_start();
    ?>
    <div class="shtr-portal-container">
        <?php echo shetrengaw_get_nav('gallery'); ?>

        <div class="shtr-card">
            <h2 class="shtr-section-title">The Court Set Pieces</h2>
            <p style="margin-bottom: 30px; line-height: 1.6;">
                The court set represents two distinct traditions. The **Igto** pieces are cast bronze with a warm gold patina, inlaid with polished amber. The **Mawdige** pieces are carved dark jade with silver wire inlay, set with lapis lazuli.
            </p>

            <div class="shtr-gallery-grid">
                
                <!-- Piece 1: Tishle -->
                <div class="shtr-piece-card">
                    <div class="shtr-piece-images">
                        <img src="/shetrengaw/images/Igto_TISHLE.png" alt="Stenica (Igto)">
                        <img src="/shetrengaw/images/Mawdige_TISHLE.png" alt="Npawti (Mawdige)">
                    </div>
                    <h4>TISHLE (Stenica / Npawti)</h4>
                    <p class="role">Infantry (Patrols)</p>
                    <p class="desc">Advances forward one square, capturing straight ahead. Can promote on ranks 7 (Igto) or 2 (Mawdige) to move and capture backward.</p>
                </div>

                <!-- Piece 2: Ashuaw -->
                <div class="shtr-piece-card">
                    <div class="shtr-piece-images">
                        <img src="/shetrengaw/images/Igto_ASHUAW.png" alt="Ashuaw (Igto)">
                        <img src="/shetrengaw/images/Mawdige_ASHUAW.png" alt="Ashuaw (Mawdige)">
                    </div>
                    <h4>ASHUAW (Horse)</h4>
                    <p class="role">Cavalry</p>
                    <p class="desc">Moves in a standard L-shape (2 squares in one direction, 1 square perpendicular). Can leap over intervening pieces.</p>
                </div>

                <!-- Piece 3: Gawja -->
                <div class="shtr-piece-card">
                    <div class="shtr-piece-images">
                        <img src="/shetrengaw/images/Igto_GAWJA.png" alt="Gawja (Igto)">
                        <img src="/shetrengaw/images/Mawdige_THASTIN.png" alt="Thastin (Mawdige)">
                    </div>
                    <h4>GAWJA / THASTIN (Elephant)</h4>
                    <p class="role">Heavy Cavalry</p>
                    <p class="desc">Moves up to 2 steps orthogonally or diagonally. Important restriction: The GAWJA/THASTIN cannot cross the mountain ranks (ranks 4 & 5).</p>
                </div>

                <!-- Piece 4: Por -->
                <div class="shtr-piece-card">
                    <div class="shtr-piece-images">
                        <img src="/shetrengaw/images/Igto_POR.png" alt="Porle (Igto)">
                        <img src="/shetrengaw/images/Mawdige_POR.png" alt="Stenapti (Mawdige)">
                    </div>
                    <h4>POR (Porle / Stenapti)</h4>
                    <p class="role">Tactician</p>
                    <p class="desc">Moves diagonally any number of squares. Color-bound to its starting squares.</p>
                </div>

                <!-- Piece 5: Rawdaw -->
                <div class="shtr-piece-card">
                    <div class="shtr-piece-images">
                        <img src="/shetrengaw/images/Igto_RAWDAW.png" alt="Rawdaw (Igto)">
                        <img src="/shetrengaw/images/Mawdige_RAJA.png" alt="Raja (Mawdige)">
                    </div>
                    <h4>RAWDAW / RAJA (Chieftain / King)</h4>
                    <p class="role">Leader</p>
                    <p class="desc">The Igto **RAWDAW** moves 1 square in any direction except backward-diagonal. The Mawdige **RAJA** is similar but cannot retreat (no backward movement at all).</p>
                </div>

                <!-- Piece 6: Rathaw -->
                <div class="shtr-piece-card">
                    <div class="shtr-piece-images">
                        <img src="/shetrengaw/images/Igto_RATHAW.png" alt="Rathaw (Igto)">
                        <img src="/shetrengaw/images/Mawdige_RATHAW.png" alt="Rathaw (Mawdige)">
                    </div>
                    <h4>RATHAW (Chariot)</h4>
                    <p class="role">War Chariot</p>
                    <p class="desc">Moves orthogonally (rank or file) any number of squares. Cannot leap over other pieces.</p>
                </div>

            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
